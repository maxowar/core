<?php

namespace Core\Routing\Route;

class Template extends Route
{
    public function __construct($name, $parameters = array())
    {
        $parameters['params']['_controller'] = 'Core\\Controller\\Template';

        parent::__construct($name, $parameters);
    }
}