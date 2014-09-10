<?php

$loader = require '../../vendor/autoload.php';
$loader->addPsr4('Frontend\\', dirname(dirname(__DIR__)) . '/project/apps');


//$configuration = Core\Configuration\Project::getApplicationConfiguration('frontend', dirname(dirname(__FILE__)));

$configuration = new \Frontend\Configuration\Configuration('prod');
Core\Core::createInstance($configuration)->dispatch();

