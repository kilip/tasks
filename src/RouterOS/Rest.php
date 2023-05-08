<?php

namespace Rest;

class Rest
{
    public function __construct(
        #[Autowire('%tasks.mikrotik.config%')]
        array $config
    )
    {

    }
}