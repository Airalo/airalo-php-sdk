<?php

namespace Airalo;

class Airalo
{
    private Config $config;

    /**
     * @param mixed $config
     */
    public function __construct($config)
    {
        $this->config = new Config($config);
    }

    // TODO: implement
}
