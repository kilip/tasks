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

class GridMonitorCommand extends Command
{
    private array $managedServers = [];
    private string $emAddress;
    private string $lockFile;
    private string $shutdownLock;
    private LoggerInterface $logger;
    private bool $dryRun;

    private bool $serversUp = true;

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

        parent::__construct('tasks:grid-monitor');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        while(true){
            if(!$this->doRequest()){
                if($this->serversUp){
                    $this->shutdownServers();
                }
                $this->serversUp = false;
            }else{
                $this->serversUp = true;
            }
        }

        return 0;
    }

    private function doRequest(): bool
    {
        $address = 'http://'.$this->emAddress;
        $logger = $this->logger;
        $client = new Client([
            'base_uri' => $address,
            'timeout' => '5'
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
            $this->logger->alert('Timeout', [$this->emAddress]);
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