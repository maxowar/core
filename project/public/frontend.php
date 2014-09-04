<?php

require_once '../../vendor/autoload.php';

try
{
    $configuration = Core\Configuration\Project::getApplicationConfiguration('frontend', dirname(dirname(__FILE__)));
    Core\Core::createInstance($configuration)->dispatch();
}
catch (Core\Exception $e)
{
    $e->printStackTrace();
}
catch (\Exception $e)
{
    Core\Exception\Exception::createFromException($e)->printStackTrace();
}

