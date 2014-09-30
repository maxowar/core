<?php

namespace Core\Filter;
use Core\Http\Response;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Execution filter esegue la logica di back-end dell'applicazione (controller execution)
 *
 * @author Massimo Naccari <massimonaccari@wdmn.it>
 * @copyright Massimo Naccari
 * @package core
 * @subpackage filter
 */
class Execution extends Filter
{
  public function execute(Manager $filterManager)
  {
    $context = $filterManager->getContext();
    
    // pre-execute @todo spostare in Core. Decidere se usare hook o eventi o basta Controller::configure()
    if(method_exists($context->getController(), 'preExecute'))
    {
      $context->getController()->preExecute();
    }
    
    //Logger::info(sprintf('ExecutionFilter | execute | Execute controller "%s/%s"', $dispatcher->getModuleName(), $dispatcher->getActionName()));
    
    // esecuzione della logica business del controller
    $response = $context->handle();

      // eseguo un eventuale altro filtro
      $filterManager->execute($filterManager);


      $response = $context->getEventDispatcher()->dispatch('execution.filter', $event = new FilterResponse($response))->getResponse();

      if(!($response instanceof Response))
      {
          throw new \InvalidArgumentException('Controller must return a Response instance type');
      }
    
    // post-execute
    if(method_exists($context->getController(), 'postExecute'))
    {
      $context->getController()->postExecute();
    }

      while(ob_get_level() > 0)
      {
          ob_end_clean();
      }

      $response->send();

      return;
  }


}
