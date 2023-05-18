<?php

namespace Tasks\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Process\Process;

class InstallServiceCommand extends Command
{
    private string $projectDir;
    private string $serviceFile = '/etc/systemd/system/homelab.service';

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        string $projectDir
    )
    {
        $this->projectDir = $projectDir;
        parent::__construct('tasks:service');
    }

    protected function configure()
    {
        $this->addOption('install', 'i', InputOption::VALUE_NONE, 'Install service');
        $this->addOption('uninstall', 'u', InputOption::VALUE_NONE, 'Uninstall service');    
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if($input->getOption('install')){
            $output->writeln('<info>Installing service</info>');
            $this->install($output);
        }elseif($input->getOption('uninstall')){
            $output->writeln('<info>Uninstalling service</info>');
            $this->uninstall($output);
        }else{
            $output->writeln('<error>You must define install or uninstall option</error>');
            $help = new HelpCommand();
            $help->setCommand($this);
            return $help->run($input, $output);
        }

        return 0;
    }

    protected function install(OutputInterface $output): void
    {
        $definition = $this->getServiceDefinition();
        
        file_put_contents($this->serviceFile, $definition, LOCK_EX);

        $this->execCommand($output, 'systemctl daemon-reload');
        $this->execCommand($output, 'systemctl enable homelab');
        $this->execCommand($output, 'service homelab start');
    }

    protected function uninstall(OutputInterface $output): void
    {
        $this->execCommand($output, 'service homelab stop');
        $this->execCommand($output, 'systemctl disable homelab');
        $this->execCommand($output, 'systemctl daemon-reload');
        if(is_file($this->serviceFile)){
            unlink($this->serviceFile);
        }
    }

    private function execCommand(OutputInterface $output, string $command): void
    {
        $commands = explode(' ', $command);
        $process = new Process($commands);
        
        $process->run(function ($type, $buffer) use ($output) {
            if (Process::ERR === $type) {
                $output->write('ERR > '.$buffer);
            } else {
                $output->write('OUT > '.$buffer);
            }
        });
    }

    private function getServiceDefinition(): string
    {
        $projectDir = $this->projectDir;
        return <<<EOC
[Unit]
Description = Olympus Homelab Tasks Service
After=network.target

[Service]
Type = simple
ExecStart = $projectDir/rr serve -c $projectDir/.rr.yaml
Restart = always
RestartSec = 30
User=toni
Group=toni

[Install]
WantedBy = default.target 
EOC;
    }
}