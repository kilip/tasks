<?php

namespace Tasks\RouterOS;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

class Config 
{
    private string $host;

    private string $port;

    private string $username;

    private string $password;

    public function __construct(
        #[Autowire('%tasks.mikrotik.config%')]
        $config
    )
    {
        $this->host = $config['host'];
        $this->port = $config['port'];
        $this->username = $config['username'];
        $this->password = $config['password'];
    }
}