<?php

namespace Frontend\Controller;

use Core\Controller\Controller;
use Core\Http\Request;
use Core\Http\Response;

class Homepage extends Controller
{
    protected function configure()
    {
        $this->context->getView()->decorate('Layouts/layout');
    }

    public function index(Request $request, Response $response)
    {
        $name = 'Massimo';

        $this->lastname = 'Naccari';

        return array('name' => $name);
    }
}