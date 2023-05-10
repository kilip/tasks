<?php

namespace Tasks\Tests\RouterOS;

use PHPUnit\Framework\TestCase;
use Tasks\RouterOS\RestClient;
use Tasks\RouterOS\StaticDNS;

class StaticDNSTest extends TestCase
{
    public function testSync()
    {
        $restClient = $this->createMock(RestClient::class);
        $restClient->expects($this->once())
            ->method('request')
            ->with('/ip/dns/static')
            ->willReturn(file_get_contents(__DIR__.'/fixtures/response.json'));
        
        $dns = new StaticDNS($restClient);
        $dns->sync();
    }
}