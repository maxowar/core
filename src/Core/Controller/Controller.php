<?php

namespace Core\Controller;

use Core\Core;
use Core\Http\Response;
use Core\Util\Utility;

/**
 * Rappreseta il controller della pagina
 *
 *
 * @author Massimo Naccari
 * @package core
 * @subpackage controller
 */
abstract class Controller
{

    private $viewVariables;

	/**
	 *
	 * @var Core
	 */
	protected $context;

  /**
   *
   * @param Core  $context
   */
	public function __construct(Core $context)
	{
        $this->context = $context;
        $this->viewVariables = array();

        $this->configure();
	}

	protected  function configure()
	{

	}

	public function log($msg, $level = 7)
	{
		$this->context->logger($this->class_name." | ".$msg,$level);
	}

	/**
	 * Aggiunge una variabile alla vista
	 *
	 * @param mixed $d
	 * @param string $room
	 * @return void
	 */
	public function addVar($key, $value)
	{
        $this->context->getView()->addVariable($key, $value);
	}

  /**
   * Sets a variable for the template.
   *
   * This is usefull for shortcut for:
   *
   * <code>$this->setVar('name', 'value')</code>
   * <code>$this->addData('d', 'room')</code>
   *
   * @param string $key   The variable name
   * @param string $value The variable value
   *
   * @return boolean always true
   *
   * @see setVar()
   */
  public function __set($key, $value)
  {
    $this->viewVariables[$key] = $value;
  }

  /**
   * Gets a variable for the template.
   *
   * This is a shortcut for:
   *
   * <code>$this->getVar('name')</code>
   *
   * @param string $key The variable name
   *
   * @return mixed The variable value
   *
   * @see getVar()
   */
  public function & __get($key)
  {
    return $this->viewVariables[$key];
  }

	public function getContext()
	{
		return $this->context;
	}


	public function setView($name="")
	{
		$this->context->setView($name);
	}

  /**
   * (proxy-method) Core::getRequestParameter()
   *
   * @author Massimo Naccari
   *
   * @param unknown_type $param
   * @param unknown_type $default
   *
   */
  public function getRequestParameter($param, $default = null)
  {
    return $this->context->getRequestParameter($param, $default);
  }

  /**
   * (proxy-method) Core::setRequestParameter()
   *
   *
   * @param string $param
   * @param unknown_type $value
   *
   */
  public function setRequestParameter($param, $value = null)
  {
      return $this->context->setRequestParameter($param, $value);
  }

  /**
   * Inizializza il controller di un componente web
   *
   * @author Massimo Naccari
   *
   * @param string $controllerName
   * @return BaseBlockController
   */
  public function initComponent($controllerName)
  {
    Core::loadController($controllerName);
    return new $controllerName($this->context, $this->getConnection(), $this->contentIni[$page]['COD']);
  }

  /**
   * Proxy-method per {@link Core::forward()}
   *
   * @author Massimo Naccari
   *
   * @param string $action
   * @param string $module
   */
  public function forward($controller, $action)
  {
    $this->context->forward($controller, $action);
  }


  /**
   * Proxy-method per {@link Core::forward()}
   *
   *
   * @param string|\Core\Routing\Route\Route $route
   * @param string cod
   */
  public function redirect($uri , $cod = 302)
  {
      $uri = '/';
      // route name
      if(is_string($uri) && !Utility::isAbsolutePath($uri) && !Utility::isValidUri($uri))
      {
          $route = $this->context->getRouting()->get($uri);
      }

      // route object
      if($uri instanceof Route)
      {
          $uri = $uri->createUrl();
      }
      // nothing good
      else
      {
          throw new \Exception('$route must be a valid url, string route name or Route obj');
      }
      $this->context->getResponse()->redirect($uri, $cod);
  }

  /**
   * Vai alla pagina d'errore "Page not found" se non il test non è verificato
   *
   * Note:
   * Attualmente la pagina di errore non viene gestita attraverso un forward esplicito
   * ma viene lanciano l'eccezione {@link PageNotFoundException} che contiene il codice
   * che si occupa di caricare e renderizzare i template relativi.
   * Per questo è obbligatorio anche inserire un messaggio d'errore.
   *
   * @author Massimo Naccari
   *
   * @param mixed $test
   * @param string $message Il messaggio dell'eccezione
   */
  public function forward404Unless($test, $message = 'Pagina non trovata')
  {
    if(!$test)
    {
      throw new PageNotFoundException($message);
    }
  }

  public function forward404If($test, $message = 'Pagina non trovata')
  {
    if($test)
    {
      throw new PageNotFoundException($message);
    }
  }

  /**
   * Forward a page not found
   *
   * @author Massimo Naccari
   *
   * @param string $message
   */
  public function forward404($message = 'Pagina non trovata')
  {
    throw new PageNotFoundException($message);
  }

    public function getView()
    {

    }

    public function render($response)
    {

    }
}
