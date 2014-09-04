<?php

namespace Core\Controller;

/**
 * Rappreseta il controller della pagina
 *
 * Qui avviene la logica di business che lega il Model (che in questa applicazione si è perso)
 * e la View del paradigma MVC
 *
 * @method View    getRenderer()
 * @method Session getSession()
 * @method string  getActionName() Ritorna il nome della action attuale
 * @method string  getModuleName() Ritorna il nome del modulo attuale
 *
 * @author Massimo Naccari
 * @package core
 * @subpackage controller
 */
class Controller
{

  /**
   * il dispatcher delle request per retrocompatibilità con codice vecchio scritto negli oggetti Controller
   *
   * @var Core
   */
	protected $index;
	
	/**
	 *
	 * @var Core
	 */
	protected $dispatcher;

	/**
	 * Il nome del tipo dell'istanza corrente
	 *
	 * @var string
	 */
	protected $class_name;

  /**
   *
   * @param Core  $idx Referenza al dispatcher
   * @param resource    $dbh Connessione db
   * @param string      $t
   * @param string      $k
   * @param string      $d
   */
	public function __construct($idx = null, $dbh = null, $t = null, $k = null, $d = null)
	{
    $this->index = $this->dispatcher = Core::getCurrentInstance();

		$this->class_name = get_class($this);

    $this->table_name   = $t;
    $this->data_record  = $d;
    $this->key_name     = $k;

    $this->configure();
	}

	public function configure()
	{

	}

	/**
	 * I metodi non definiti negli oggetti Controller vengono verificati ed eventualmente eseguiti
	 * nell'oggetto dispatcher Core
	 *
	 * @author Massimo Naccari
	 *
	 * @param string $method
	 * @param array $args
	 */
	public function __call($method, $args)
	{
	  if(method_exists($this->dispatcher, $method))
	  {
	    return call_user_func_array(array($this->dispatcher, $method), $args);
	  }
	  throw new CoreException(sprintf('Impossibile risolvere il metodo "%s::%s()"', get_class($this), $method));
	}

	public function execute()
	{
		throw new CoreException('Il metodo Controller::execute() non è definito');
	}

	public function logger($msg, $level = 7)
	{
		$this->dispatcher->logger($this->class_name." | ".$msg,$level);
	}

	public function getIniAttributes($room,$key)
	{
		return Config::get($room . '/' . $key);
	}

	/**
	 * Aggiunge una variabile alla vista
	 *
	 * @param mixed $d
	 * @param string $room
	 * @return void
	 */
	public function addData($d, $room = null)
	{
		if($room == null)
		{
		  $room = $this->class_name;
		}
		$this->dispatcher->data[$room] = $d;

    $this->addVar($room, $d);
	}

	public function addVar($key, $value)
	{
    $this->dispatcher->getRenderer()->addVariable($key, $value);
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
    $this->$key = $value;
    $this->dispatcher->getEventDispatcher()->notify(new sfEvent($this, 'controller.unknown_field', array($key, $value)));
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
    return $this->dispatcher->getRenderer()->getVariable($key);
  }

	public function clearData($room=null)
	{
		if ($room==null) $room=$this->class_name;
		unset($this->dispatcher->data[$room]);
	}

	public function getData($room=null)
	{
		if ($room==null) $room=$this->class_name;
		if (isset($this->dispatcher->data[$room]))
			return ($this->dispatcher->data[$room]);
	}

	public function getIndex()
	{
	  $e = new Exception();
	  $trace = $e->getTrace();
	  Logger::log(sprintf('Controller | getIndex | Deprecated called in File: %s(%d) %s::%s', $trace[1]['file'], $trace[1]['line'], $trace[1]['class'], $trace[1]['function']), Logger::WARNING);
	  
		return $this->dispatcher;
	}
	
	public function getDispatcher()
	{
	  return $this->dispatcher;
	}

	public function setView($name="")
	{
		$this->dispatcher->setView($name);
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
    return $this->dispatcher->getRequestParameter($param, $default);
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
      return $this->dispatcher->setRequestParameter($param, $value);
  }

  /**
   * Ritorna l'istanza del frontcontroller
   *
   * @author Massimo Naccari
   *
   * @return Core
   */
  public function getFrontController()
  {
    return $this->dispatcher;
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
    return new $controllerName($this->dispatcher, $this->getConnection(), $this->contentIni[$page]['COD']);
  }

  /**
   * Pre-esegue tutti i componenti web considerati necessari a priori e definiti nel file content.ini
   *
   * La funzione deve essere chiamata esplicitamente all'interno dei controller di pagina
   *
   * @author Massimo Naccari
   *
   * @return void
   */
  public function executeContentControllers()
  {
    $nbContentControllers = count($this->contentIni[$this->pagecode]['BLOCKLIST']);
    for($i = 0; $i < $nbContentControllers; $i ++)
    {
      $this->getFrontController()->initializeController($this->contentIni[$this->pagecode]['BLOCKLIST'][$i])->execute();
    }
  }

  /**
   * Proxy-method per {@link Core::forward()}
   *
   * @author Massimo Naccari
   *
   * @param string $action
   * @param string $module
   */
  public function forward($action, $module = null)
  {
    $this->getFrontController()->forward($action, $module);
  }


  /**
   * Proxy-method per {@link Core::forward()}
   *
   *
   * @param mixed $route
   * @param string cod
   */
  public function redirect($route , $cod = 302)
  {
      $this->getFrontController()->redirect($route , $cod );
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
   * Controlla l'azione HTTP corrente
   */
  public function isPost()
  {
    return Routing::getRequestMethod() == Routing::POST;
  }

}
