<?php

namespace Tasks\RouterOS;

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Tasks\RouterOS\DNS\Record;

class StaticDNS
{
    private RestClient $restClient;
    
    public function __construct(
        RestClient $restClient
    )
    {
        $this->restClient = $restClient;
    }

    public function sync()
    {
        $restClient = $this->restClient;

        $response = $restClient->request('/ip/dns/static');

        $nameConverter = new NameConverter();
        $encoders = [new JsonEncoder()];
        $normalizers = [
            new ArrayDenormalizer(),
            new ObjectNormalizer(null, $nameConverter)
        ];
        $serializer = new Serializer($normalizers, $encoders);
        
        $persons = $serializer->deserialize($response, 'Tasks\\RouterOS\\DNS\\Record[]', 'json');

        print_r($persons);
    }
}