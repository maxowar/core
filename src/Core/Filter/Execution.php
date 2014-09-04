<?php

namespace Core\Filter;

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
    // eseguo un eventuale altro filtro
    $filterManager->execute();
    
    $dispatcher = $filterManager->getContext();
    
    // pre-execute
    if(method_exists($dispatcher->getController(), 'preExecute'))
    {
      $dispatcher->getController()->preExecute();
    }
    
    //Logger::info(sprintf('ExecutionFilter | execute | Execute controller "%s/%s"', $dispatcher->getModuleName(), $dispatcher->getActionName()));
    
    // esecuzione della logica business del controller
    $res = $dispatcher->getController()->execute();
    
    // post-execute
    if(method_exists($dispatcher->getController(), 'postExecute'))
    {
      $dispatcher->getController()->postExecute();
    }

    if($res === false)
    {
      $dispatcher->renderWithoutView();
    }
  }
}
