<?php

namespace Core\Controller;

/**
 * Rappresenta il gestore delle actions di un modulo
 *
 * Note:
 * Un modulo mi consente di rappresentare più pagine (actions) che possono avere una connessione
 * logica, come una sezione di un sito, in un unico file (modulo).
 * In questo modo si tiene pulito il filesystem e codice comune come funzioni ausiliarie
 * può essere facilmente riutilizzato.
 *
 * @author Massimo Naccari <massimo.naccari@wdmn.it>
 * @copyright Massimo Naccari
 * @package core
 * @subpackage controller
 */
abstract class Controllers extends Controller
{
  protected $restfull = false;

  protected $restActionMethod = array('index' => 'get', 'new' => 'get', 'edit' => 'get', 'update' => 'put', 'insert' => 'post', 'delete' => 'delete');

  /**
   * Esegue l'action di un determinato modulo
   */
  public function execute()
  {
    $action = $this->getFrontController()->getActionName();

    $actionMethod = 'execute' . ucfirst($action);

    if(!method_exists($this, $actionMethod))
    {
      throw new CoreException(sprintf('L\'action "%s" per il modulo "%s" non esiste', $action, $this->index->getRequestParameter('module')));
    }

    if(Config::get('application.debug', false))
    {
      Logger::info(sprintf('Controller | Esecuzione action "%s" del modulo "%s"', Core::getCurrentInstance()->getActionName(), Core::getCurrentInstance()->getModuleName()));
    }

    // controllo metodi HTTP @todo sarebbe di competenza di Routing
    if($this->restfull)
    {
      if(array_key_exists(strtolower($action), $this->restActionMethod))
      {
        if(Routing::getRequestMethod() != $this->restActionMethod[strtolower($action)])
        {
          $this->forward404('Pagina non trovata');
        }
      }
    }

    return $this->$actionMethod();
  }

}