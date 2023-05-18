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
use Symfony\Component\Console\Input\InputOption;

class CheckPowerGridCommand extends Command
{
    private array $managedServers = [];
    private string $emAddress;
    private string $lockFile;
    private string $shutdownLock;
    private LoggerInterface $logger;
    private bool $dryRun;

    public function __construct(
        #[Autowire('%env(TASKS_ENERGY_MONITOR_IP)%')]
        string $emAddress,
        
        #[Autowire('%env(TASKS_MANAGED_SERVERS)%')]
        string $managedServers,
        
        #[Autowire('%kernel.project_dir%/var/check-grid.lck')]
        string $lockFile,

        #[Autowire('%kernel.project_dir%/var/shutdown.lck')]
        string $shutdownLock,

        #[Autowire('%env(TASKS_DRY_RUN)%')]
        bool $dryRun,

        LoggerInterface $logger
    )
    {
        $this->managedServers = explode(' ', $managedServers);
        $this->emAddress = $emAddress;
        $this->lockFile = $lockFile;
        $this->shutdownLock = $shutdownLock;
        $this->logger = $logger;
        $this->dryRun = $dryRun;

        parent::__construct('tasks:check-grid');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // $process = new Process(['ping', '-w', '1', '192.168.10.23']);
        $shutdownLock = $this->shutdownLock;
        $lockFile = $this->lockFile;

        pcntl_signal(SIGINT, function (int $signal, $info) use ($shutdownLock, $lockFile) {
            $this->logger->info('Received shutdown signal');
            $this->removeLockFile($shutdownLock);
            $this->removeLockFile($lockFile);
        });

        $this->removeLockFile($shutdownLock);

        if(!is_file($this->lockFile)){
            touch($this->lockFile);
            $this->doExecute($input, $output);
        }else{
            $this->logger->info('Tasks already running');
            $output->writeln('tasks already running');
            return 0;
        }

        
        $this->removeLockFile($this->lockFile);
        return 0;
    }

    private function removeLockFile(string $file): void
    {
        if(is_file($file)){
            unlink($file);
        }
    }

    private function doExecute(): void
    {
        if(!$this->doRequest()){
            $this->shutdownServers();
        }
    }

    private function doRequest(): bool
    {
        $address = 'http://'.$this->emAddress;
        $logger = $this->logger;
        $client = new Client([
            'base_uri' => $address,
            'timeout' => '3'
        ]);
        
        try{
            $response = $client->request('GET', '/cm',[
                'query' => [
                    'cmnd' => 'POWER',
                ],
                'auth' => ['admin', 'ajengcintaku']
            ]);
    
            if(200 === $response->getStatusCode()){
                $context = json_decode($response->getBody(), true);
                $logger->info('Successfully connect to grid monitor', $context);
                return true;
            }
        }catch(\Exception $e){
            $this->logger->alert($e->getMessage());
        }
        return false;
    }

    private function shutdownServers(): void
    {
        $this->logger->alert('shuttingdown all servers');
        foreach($this->managedServers as $server){
            $this->shutdown($server);
        }
        touch($this->shutdownLock);
    }

    private function shutdown(string $server): void
    {
        $this->logger->alert('Shutting down node', [$server]);

        $process = new Process([
            'ssh',
            'toni@'.$server,
            'systemctl',
            'suspend',
        ]);

        if(!$this->dryRun){
            $process->start();
            foreach($process as $type => $data){
                $this->logger->alert("<info>Output</info> <comment>{0}</comment>", [$data]);
            }
        }else{
            $this->logger->alert('Executing ssh command', [$process->getCommandLine()]);
        }
    }
}