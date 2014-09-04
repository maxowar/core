<?php

namespace Core\Http;

class Request
{
    public function __construct($headers)
    {
        $this->initializeFromEnvironment();
    }

    private function initializeFromEnvironment()
    {

    }

    public function getMethod()
    {

    }

    public function get()
    {

    }

    public function getQueryString()
    {

    }
}