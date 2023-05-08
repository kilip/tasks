<?php

namespace Tasks\Command;

use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Process\Process;
use Psr\Log\LoggerInterface;

class CheckPowerGridCommand extends Command
{
    private array $managedServers = [];
    private string $emAddress;
    private string $lockFile;
    private string $shutdownLock;
    private LoggerInterface $logger;

    public function __construct(
        #[Autowire('%env(TASKS_ENERGY_MONITOR_IP)%')]
        string $emAddress,
        
        #[Autowire('%env(TASKS_MANAGED_SERVERS)%')]
        string $managedServers,
        
        #[Autowire('%kernel.project_dir%/var/check-grid.lck')]
        string $lockFile,

        #[Autowire('%kernel.project_dir%/var/shutdown.lck')]
        string $shutdownLock,

        LoggerInterface $logger
    )
    {
        $this->managedServers = explode(' ', $managedServers);
        $this->emAddress = $emAddress;
        $this->lockFile = $lockFile;
        $this->shutdownLock = $shutdownLock;
        $this->logger = $logger;

        parent::__construct('tasks:check-grid');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // $process = new Process(['ping', '-w', '1', '192.168.10.23']);
        $shutdownLock = $this->shutdownLock;
        $lockFile = $this->lockFile;

        pcntl_signal(SIGINT, function (int $signal, $info) use ($shutdownLock, $lockFile) {
            $this->logger->info('Received shutdown signal');

            if(is_file($shutdownLock)){
                unlink($shutdownLock);
            }
            if(is_file($lockFile)){
                unlink($lockFile);
            }
        });

        if(is_file($this->shutdownLock)){
            unlink($this->shutdownLock);
        }

        if(!is_file($this->lockFile)){
            touch($this->lockFile);
            $this->doExecute($input, $output);
        }else{
            $this->logger->info('Tasks already running');
            $output->writeln('tasks already running');
            return 0;
        }

        unlink($this->lockFile);
        return 0;
    }

    private function doExecute(InputInterface $input, OutputInterface $output)
    {
        $process = new Process(['ping', '-w', '1', '192.168.10.23']);
        $process->run();

        if(!$process->isSuccessful()){
            $this->logger->info("Failed to ping, shutting down servers");
            $this->shutdownServers($output);
        }else{
            $this->logger->info('Successfully ping to server {0}', [$this->emAddress]);
        }
    
        return 0;
    }

    private function shutdownServers(OutputInterface $output): void
    {
        $this->logger->info('shuttingdown all servers');
        foreach($this->managedServers as $server){
            $this->shutdown($output, $server);
        }
        touch($this->shutdownLock);
    }

    private function shutdown(OutputInterface $output, string $server): void
    {
        $this->logger->info('Shutting down {0}', [$server]);

        $process = new Process([
            'ssh',
            'toni@'.$server,
            'sudo',
            'systemctl',
            'suspend',
        ]);

        $process->start();
        foreach($process as $type => $data){
            $output->writeln($data);
        }
    }
}