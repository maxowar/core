<?php

namespace Core\Controller;

use Core\Core;
use Core\Http\Event\FilterResponse;
use Core\Http\Response;
use Core\Util\Utility;
use Core\View\View;
use Symfony\Component\EventDispatcher\Event;

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
	public final function __construct(Core $context)
	{
        $this->context = $context;
        $this->viewVariables = array();

        //$context->getEventDispatcher()->addListener('response.filter', array($this, 'listenToExecutionFilter'));

        $this->configure();
	}

    /**
     * Always good old style View variables passing
     *
     * @param $key
     * @param $value
     */
    public function __set($key, $value)
    {
        $this->getView()->addVariable($key, $value);
    }

	protected  function configure()
	{

	}

    public function listenToExecutionFilter(FilterResponse $event)
    {
        if(($response = $event->getResult()) instanceof Response)
        {
            return;
        }
        // text
        if(is_string($response))
        {
            $event->getResponse()->setContent($response);
        }
        // view parameters
        else if(is_array($response))
        {
            $event->getResponse()->setContent($this->context->getView()->render($response));
        }
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


	public function setTemplate($template)
	{
		$this->getView()->setTemplate($template);
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

    /**
     * Nota: futuro uso diverso di Core->getView()
     *
     * @return \Core\View\View
     */
    public function getView()
    {
         return $this->context->getView();
    }

    public function render($template = null, $variables = array())
    {
        $response = $this->context->getResponse();

        $view = $this->getView();
        if(!$template)
        {
            $template = $this->context->getControllerName() . '/' . $this->context->getActionName();
        }
        $view->setTemplate($template);
        $response->setContent($view->render($variables));
        return $response;
    }

    /**
     * Interrompe il buffer dello per stampare a video un messaggio
     *
     * @param string $text La stringa di testo da stampare
     * @return void
     */
    public static function renderText($text)
    {
        $buffer = '';
        while(ob_get_level() > 0)
        {
            $buffer .= ob_get_contents();
            ob_end_clean();
        }
        echo $text;
        ob_start();
        echo $buffer;
    }

    /**
     * @return \Core\Routing\Route\Route
     */
    public function getRoute()
    {
        return $this->getContext()->getRouting()->getMatchedRoute();
    }
}
