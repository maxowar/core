<?php

namespace Frontend\Controller;

use Core\Controller\Controller;
use Core\Http\Request;
use Core\Http\Response;

class Homepage extends Controller
{
    protected function configure()
    {
        //$this->getView()->getAsset()->addCss();
        //$this->getView()->getAsset()->addCss();
    }

    public function execute(Request $request, Response $response)
    {
        $name = 'Massimo';

        $this->lastname = 'Naccari';

        return array('name' => $name);
    }
}