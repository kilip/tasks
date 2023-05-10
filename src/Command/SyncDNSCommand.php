<?php

namespace Tasks\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tasks\RouterOS\RestClient;
use Tasks\RouterOS\StaticDNS;

class SyncDNSCommand extends Command
{
    private StaticDNS $dns;

    public function __construct(
        StaticDNS $staticDNS
    ){
        $this->dns = $staticDNS;
        parent::__construct('tasks:routeros:sync-dns');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return 0;
    }
}