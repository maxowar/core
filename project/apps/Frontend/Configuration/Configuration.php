<?php

namespace Frontend\Configuration;

use Core\Configuration\Application;
use Symfony\Component\EventDispatcher\Event;

class Configuration extends Application
{
    public function getApplicationName()
    {
        return 'frontend';
    }

    public function configure()
    {
        // conf db

        // listeners

        // aggiungiamo stylesheet globalmente

        $this->eventDispatcher->addListener('filter.rendering', array($this, 'listenToFilterRendering'));

    }

    public function listenToFilterRendering(Event $event)
    {
        $event->getSubject()->getContext()->addStylesheet('main');
    }
}
