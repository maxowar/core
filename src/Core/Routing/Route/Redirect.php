<?php

namespace Core\Routing\Route;

/**
 * RedirectRoute
 * 
 * Si tratta di una Route particolare che quando l'url della Request combacia con il pattern
 * effettua il redirect ad un'altra url di destinazione
 * 
 * utile per gestire vecchie sezioni di un sito
 * 
 * @author Massimo Naccari <massimo.naccari@wdmn.it>
 * @copyright Massimo Naccari
 * @package core
 * @subpackage routing
 * 
 */
class Redirect extends Route
{
  /**
   * 
   * @var string The absolute URI oppure il nome di una Route
   */
  private $target;
  
  /**
   * 
   * @var integer
   */
  private $code;
  
  /**
   * parametri:
   *   - target : può essere sia un URI che il nome di una Route
   *   - code   : codice redirect (opzionale, default 301)
   * 
   * (non-PHPdoc)
   * @see Route::initialize()
   */
  public function initialize(array $parameters)
  {
    $parameters['params']['p'] = '_redirect_';
    
    parent::initialize($parameters);
    
    if(!isset($parameters['target']))
    {
      throw new RoutingException('RedirectRoute need a "target" option');
    }

    $this->target = $parameters['target'];
    $this->code = isset($parameters['code']) ? $parameters['code'] : 301;
  }

  /**
   * In caso di match effettua il redirect inizializzando la Route di destinazione con i parametri della Route corrente
   * 
   * (non-PHPdoc)
   * @see Route::compile()
   */
  public function matchesUrl($url)
  {
    if(parent::matchesUrl($url))
    {
      // redirect a URI
      if(Utility::isValidUri($this->target))
      {
        Routing::redirect($this->target, $this->code);
      }
      
      // redirect a Route
      Routing::redirect(Routing::get($this->target)->createUrlAndGo($this->params, $this->code));
    }
    
    return false;
  }
  
  public function getParamsForCache()
  {
    return array('target' => $this->target, 'code' => $this->code);
  }
}
