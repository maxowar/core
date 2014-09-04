<?php

use Core\Routing\Routing;
use Core\Routing\Route\Route;

return array(

    new Route('home', array(
        'url' => '/',
        'params' => array('_controller' => 'Homepage'),
    )),

    new Route('static_content', array(
        'url' => '/:_action',
        'params' => array('_controller' => 'StaticContent'),
    )),

    new Route('security_signin', array(
        'url' => '/signin',
        'params' => array('_controller' => 'Security', '_action' => 'signin'),
    )),

    new Route('security_signin', array(
        'url' => '/signout',
        'params' => array('_controller' => 'Security', '_action' => 'signout'),
    )),

    new Route(Routing::ROUTE_NAME_MODULE_DEFAULT, array(
              'url'     => '/:_controller/:_action/*'
    )),

    new Route(Routing::ROUTE_NAME_DEFAULT, array(
              'url'     => '/:_controller/*'
    ))
);


