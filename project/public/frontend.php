<?php

$loader = require '../../vendor/autoload.php';
$loader->addPsr4('Frontend\\', dirname(dirname(__DIR__)) . '/project/apps');

$configuration = new \Frontend\Configuration\Configuration('prod');
Core\Core::createInstance($configuration)->dispatch();

