<?php

namespace Frontend\Configuration;

use Core\Configuration\Application;
use Core\View\Helper\Asset;
use Core\View\Helper\Html;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\GenericEvent;

class Configuration extends Application
{
    public function getApplicationName()
    {
        return 'frontend';
    }

    public function configure()
    {
        // conf db

        $asset = new Asset();

        // view helper
        $this->eventDispatcher->addListener('view.load_helpers', function(GenericEvent $event) use ($asset) {
            $event->getSubject()->addHelper('asset', $asset);
            $event->getSubject()->addHelper('html', new Html());
        });

        // aggiungiamo stylesheet globalmente

        $this->eventDispatcher->addListener('filter.rendering', array($this, 'listenToFilterRendering'));

    }

    public function listenToFilterRendering(Event $event)
    {
        //$event->getSubject()->getContext()->addStylesheet('main');
    }
}
