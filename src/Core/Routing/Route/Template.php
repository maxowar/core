<?php

namespace Core\Routing\Route;

class Template extends Route
{
    protected $template;

    public function __construct($name, $parameters = array())
    {
        $parameters['params']['_controller'] = '\\Core\\Controller\\Template';

        if(!isset($parameters['template']))
        {
            throw new \InvalidArgumentException("Parameter 'view' is mandatory");
        }
        $this->template = $parameters['template'];

        parent::__construct($name, $parameters);
    }

    public function getTemplate()
    {
        return $this->template;
    }
}