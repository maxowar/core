<?php

namespace Core\Filter;
use Symfony\Component\EventDispatcher\Event;

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
    //$filterManager->getContext()->getEventDispatcher()->connect('view.cache_enable', array($this, 'listenToViewCacheEnable'));
    //$filterManager->getContext()->getEventDispatcher()->connect('view.filter_id', array($this, 'listenToFilterViewId'));
    
    $filterManager->execute();

    // è il momento propizio per inviare gli headers ... hahaha non sono gestiti! hahah
    
    $filterManager->dispatcher->getEventDispatcher()->dispatch('filter.rendering', new Event( array('filterManager' => $filterManager)));

    if($filterManager->getContext()->shallRender())
    {
      $this->send($filterManager);
    }
    
    exit(0);
  }
  
  /**
   * Quando viene abilitata la cache della View e c'è un cache hit invia l'output e blocca l'esecuzione
   * 
   * @param Event $event
   */
  public function listenToViewCacheEnable(Event $event)
  {
    $cache = $event->getSubject();

    if($cache->has(Core::getCurrentInstance()->getRenderer()->getId()))
    {
      $this->send();
      
      exit(0);
    }
  }
  
  /**
   * Invia l'output al client
   * 
   * @param unknown_type $filterManager
   */
  public function send($filterManager = null)
  {
    if(!$filterManager)
    {
      $renderer = Core::getCurrentInstance()->getRenderer();
    }
    else
    {
      $renderer = $filterManager->getContext()->getRenderer();
    }
    
    // inizializzo automaticamente il template se questo non è stato inizializzato manualmente
    if(!$renderer->getTemplate())
    {
      $renderer->setTemplate($filterManager->getContext()->getActionTemplate());
    }
    
    $output = $renderer->render();
    
    while(ob_get_level() > 0)
    {
      ob_end_clean();
    }
    
    echo $output;
  }
  
  /**
   * Modifica l'id di un oggetto View inserendo un hash dei parametri della Request
   * 
   * @param Event $event
   */
  public function listenToFilterViewId(Event $event, $id)
  {    
    $hashParameters = '';
    $hashValues = '';
    foreach(Routing::getCurrentRequestParameters() as $parameter => $value)
    {
      $hashParameters .= $parameter;
      $hashValues .= $value;
    }

    return md5($id . $hashParameters) . '.' . md5($id . $hashValues);
  }

}