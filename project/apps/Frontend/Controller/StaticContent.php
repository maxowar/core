<?php

namespace Frontend\Controller;

use Core\Controller\Controller;
use Core\Http\Request;
use Core\Http\Response;

class StaticContent extends Controller
{
    public function configure()
    {
        $this->getView()->decorate('Layouts/layout');
    }

    public function who(Request $request, Response $response)
    {
        $this->name = $request->getQuery('name');
        $this->surname = $request->getQuery('surname');
        $this->extra  = $this->context->getRouting()->getMatchedRoute()->getParam('extra');
        $this->another  = $request->getQuery('another', 'null');

        $this->render();
    }

    public function whereis()
    {

    }

    public function contact(Request $request, Response $response)
    {
        return $this->render($response);
    }
}