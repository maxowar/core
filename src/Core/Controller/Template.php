<?php

namespace Core\Controller;

class Template extends Controller
{
    public function index()
    {
        $route = $this->getRoute();

        $this->render($route->getParam('_view'));
    }

    public function getRoute()
    {
        if(!($route = parent::getRoute() instanceof \Core\Routing\Route\Template))
        {
            throw new \RuntimeException('Expected Template Route object type');
        }
        return $route;
    }
}