<?php

namespace Core\Controller;

class Template extends Controller
{
    public function index()
    {
        $route = $this->getRoute();

        $this->render($route->getTemplate());
    }

    public function getRoute()
    {
        $route = parent::getRoute();
        if(!($route instanceof \Core\Routing\Route\Template))
        {
            throw new \RuntimeException('Expected Template Route object type');
        }
        return $route;
    }
}