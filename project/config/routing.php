<?php

use Core\Routing\Routing;
use Core\Routing\Route\Route;

return array(

    new Route('security_signin', array(
        'url' => '/signin',
        'params' => array('_controller' => 'Security', '_action' => 'signin'),
    )),

    new Route('security_signout', array(
        'url' => '/signout',
        'params' => array('_controller' => 'Security', '_action' => 'signout'),
    )),

    new Route('static_content', array(
        'url' => '/:_action',
        'params' => array('_controller' => 'StaticContent'),
    )),

    new Route('home', array(
        'url' => '/',
        'params' => array('_controller' => 'Homepage'),
    )),
/*
    new Route(Routing::GENERIC_ROUTE_NAME, array(
              'url'     => '/:_controller/:_action/*'
    ))*/
);


