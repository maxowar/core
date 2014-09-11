<?php

namespace Core\Plugin;

use Core\Core;

interface PluginInterface
{
    public function initialize(Core $context);

    public function install();
}