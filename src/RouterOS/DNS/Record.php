<?php

namespace Tasks\RouterOS\DNS;

class Record
{
    private ?string $id;
    private string $name;
    private string $disabled;
    private string $dynamic;
    private string $ttl;
    private ?string $address;
    private ?string $cname;
    private ?string $type;

    public function getId(): string
    {
        return $this->id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}