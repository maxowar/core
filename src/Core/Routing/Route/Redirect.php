<?php

namespace Core\Routing\Route;
use Core\Core;
use Core\Http\Response;
use Core\Util\Utility;

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
    $parameters['params']['_action'] = null;
    
    parent::initialize($parameters);
    
    if(!isset($parameters['target']))
    {
      throw new \InvalidArgumentException('Missin mandatory "target" option');
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
      $response = new Response();
    if(parent::matchesUrl($url))
    {
      // route name
      if(!Utility::isValidUri($this->target))
      {
          $this->target = Core::getInstance()->getRouting()->get($this->target)->createUrl($this->params);
      }
        $response->redirect($this->target, $this->code);

    }
    
    return false;
  }

}
