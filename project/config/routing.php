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

    new Route('who', array(
        'url' => '/who/:name',
        'params' => array('_controller' => 'StaticContent', '_action' => 'who', 'extra' => 'val'),
    )),
/*
    new Route('welcome', array(
        'url' => '/:_action',
        'view' => 'Info/welcome.phtml',
    )),
*/
    new Route('features', array(
        'url' => '/features/:_action',
        'params' => array('_controller' => 'Features'),
    )),

    new Route('static_content', array(
        'url' => '/:_action',
        'params' => array('_controller' => 'StaticContent'),
    )),

    new Route('home', array(
        'url' => '/',
        'params' => array('_controller' => 'Homepage'),
    ))
);


