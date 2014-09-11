<?php

namespace Frontend\Controller;

use Core\Controller\Controller;
use Core\Http\Request;

class StaticContent extends Controller
{
    public function configure()
    {
        $this->context->getView()->decorate('Layouts/layout');
    }

    public function who(Request $request)
    {
        return array(
            'name' => $request->getQuery('name'),
            'surname' => $request->getQuery('surname'),
            'extra' => $this->context->getRouting()->getMatchedRoute()->getParam('extra')
        );
    }

    public function whereis()
    {

    }

    public function contact()
    {

    }
}