<?php

namespace Tasks\RouterOS;

use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class NameConverter implements NameConverterInterface
{
    public function normalize(string $propertyName): string
    {
        if('id' === $propertyName){
            return '.id';
        }
        return $propertyName;
    }

    public function denormalize(string $propertyName): string
    {
        if('.id' === $propertyName){
            return 'id'; 
        }
        return $propertyName;
    }
}