<?php

namespace Core\View\Asset;

class Asset
{
    protected $filename;

    public function __construct($filename, $options = array())
    {
        $this->filename = $filename;
    }
}