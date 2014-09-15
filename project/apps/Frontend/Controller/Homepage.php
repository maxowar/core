<?php

namespace Frontend\Controller;

use Core\Core;
use Core\Http\Request;
use Core\Http\Response;

/**
 * The homepage controller logic business
 *
 * @package Frontend\Controller
 */
class Homepage
{
    public function index(Request $request, Response $response)
    {
        $name = 'Massimo';

        $view = Core::getInstance()->getView();
        //$view->attachNamespace('Frontend', Config::get('application.path') . '/apps/Frontend/View');
        $view->decorate('Layouts/layout');
        $view->setTemplate('Homepage/index');
        $view->getAsset()->addStylesheet('main.css');

        $response->setContent($view->render(array('name' => $name)), 'text/html');
        return $response;
    }
}