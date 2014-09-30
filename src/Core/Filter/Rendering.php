<?php

namespace Core\Filter;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Rendering filter esegue la creazione e l'invio dell'output
 *
 * @author Massimo Naccari <massimonaccari@wdmn.it>
 * @copyright Massimo Naccari
 * @package core
 * @subpackage filter
 */
class Rendering extends Filter
{
  public function execute(Manager $filterManager)
  {
    $filterManager->execute();

    if($filterManager->getContext()->shallRender())
    {
        $view = $filterManager->getContext()->getView();

        $filterManager->context->getEventDispatcher()->dispatch('filter.rendering', new GenericEvent($view));


        // inizializzo automaticamente il template se questo non è stato inizializzato manualmente
        if(!$view->getTemplate())
        {
            $view->setTemplate($filterManager->getContext()->getActionTemplate());
        }

        $output = $view->render();
    }
  }
}