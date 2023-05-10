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

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setAddress(string $address): void
    {
        $this->address = $address;
    }

    public function setCname(string $cname): void
    {
        $this->cname = $cname;
    }

    public function getCname(): string
    {
        return $this->cname;
    }
}