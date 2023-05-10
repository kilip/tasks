<?php

namespace Tasks\RouterOS;

use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Tasks\RouterOS\Config;

class RestClient
{
    private Config $config;
    private Client $guzzle;

    public function __construct(
        Config $config
    )
    {
        $this->config = $config;
        $this->guzzle = new Client();
    }

    public function request(string $command): string
    {

    }
}