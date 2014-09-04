<?php

namespace Core;

use Core\Configuration\Project;
use Core\Exception\PageNotFound;
use Core\Filter\Manager;
use Core\Routing\Route\Route;
use Core\Routing\Routing;
use Core\Util\Config;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Contiene le routine per l'inizializzazione iniziale di ogni applicazione
 * e rappresenta il frontcontroller generico dell'applicazione ossia il dispatcher
 *
 * Ogni front-controller estenderà questa classe ed implementerà le proprie
 * routines a proprio piacimento sovrascrivendo in particolare le funzioni
 * di inizializzazione delle varie entità del sistema
 *
 * L'inizializzazione del dispatcher &egrave; molto semplice ed avviene attraverso il factory-method {@link Core::createInstance()}
 * passandogli l'oggetto ProjectConfiguration che gestisce le configurazioni necessarie all'applicazione
 *
 * <code>
 * <?php
 * $configuration = ProjectConfiguration::getApplicationConfiguration('backend');
 * Core::createInstance($configuration)->dispatch();
 * ?>
 * </code>
 *
 * @author Massimo Naccari
 * @package core
 * @subpackage controller
 */
class Core
{
  /**
   * Roba vecchia
   * 
   * @var string
   */
  public $zone, $page, $oper;

  /**
   * Inizio esecuzione request dispatcher
   *
   * @var integer
   */
  private $startTime;

  private $renderView;

  /**
   *
   * @var EventDispatcher
   */
  private $event_dispatcher;

  /**
   * Lista delle voci del dizionario per la lingua corrente
   *
   * @var array
   */
  public $labels;

  /**
   * id della lingua corrente
   *
   * @var string
   */
  public $lang;

  /**
   * Variabili della vista
   * Roba vecchia
   *
   * @var array
   */
  public $data = array();

  /**
   * Nome della vista corrente
   *
   * @var string
   */
  public $view;

 	/**
 	 * contiene la lista degli HTTP headers da inviare nel response
 	 *
 	 * @var array
 	 */
 	private $httpHeaders;

  /**
   * Riferimento al DB per effettuare le query
   *
   * @var DataController
   */
  public $dataController;

  /**
   * Contiene il riferimento all'istanza correntemente istanziata del
   * request dispatcher
   *
   * @var Core
   */
  public static $instance;

  /**
   *
   * @var Controller
   */
  private $controller_obj;


  /**
   *
   * @var ProjectConfiguration
   */
  private $configuration;


  /**
   * Lista dei javascript della response
   *
   * @var array
   */
  private $javascript;

  /**
   * Lista dei fogli di stile della response
   *
   * @var array
   */
  private $stylesheet;
  
  /**
   * 
   * @var array
   */
  private $headTags;

  /**
   * @var $filterManager FilterManager
   */
  protected $filterManager;

  /**
   * Lista di Meta tags HTML
   *
   * @var unknown_type
   */
  public $meta = array();

  /**
   * Controller vector
   *
   * buffer che contiene tutti gli oggetti di tipo Controller inizializzati
   *
   * @var array
   */
  private $controllers = array();

  /**
   *
   * @var Session
   */
  private $session;

  private $controller;

  private $action;

  protected $nbForwards = 1;

  protected $isViewInitialized;

  protected $renderer;

  /**
   * inizializza le variabili di sistema, imposta i path dell'applicazione
   *
   */
	public function __construct(Project $projectConfiguration)
	{
	  $this->startTime = microtime(true);

	  //sfTimerManager::getTimer('Total execution');

    header("Content-Type: text/html; charset=UTF-8"); // @todo non è di conpetenza del front controller. spostare nel response

    $this->configuration = $projectConfiguration;

    $this->httpHeaders = array(); // @todo non è di conpetenza del front controller. spostare nel response
    
    $this->javascript  = array(); // @todo non è di conpetenza del front controller. spostare nel response
    $this->stylesheet  = array(); // @todo non è di conpetenza del front controller. spostare nel response
    $this->headTags  = array(); // @todo non è di conpetenza del front controller. spostare nel response

    $this->renderView = true;

    self::$instance = $this;

    $this->event_dispatcher = new EventDispatcher();

    register_shutdown_function(array($this, 'shutdown'));

    $this->initialize();
	}

	/**
	 * Questa funzione si occupa di inizializzare le principali entità
	 * dell'applicazione
	 *
	 */
	public function initialize()
	{

	}

	/**
	 * Crea un'istanza del dispatcher
	 *
	 * Factory-method per oggetti di tipo Core iniettando la configurazione
	 *
	 * {@source }
	 *
	 * @param ProjectConfiguration $projectConfiguration
	 * @param string $application
	 * @return Core
	 */
	public static function createInstance(Project $projectConfiguration)
	{
	  $controllerName = ucfirst($projectConfiguration->getApplicationName()) . 'Controller';

        return new self($projectConfiguration);


	  foreach($projectConfiguration->getControllersDir() as $path)
	  {
	    if(file_exists($filename = $path . DIRECTORY_SEPARATOR . self::parseControllerFileName($controllerName) . '.php'))
	    {
	      include_once $filename;

	      //Config::get('LOG/debug') && Logger::info(sprintf('Core | loadController | Inclusa la classe "%s" per il dispatcher "%s"', $filename, $controllerName));

	      return new self($projectConfiguration);
	    }
	  }

	  throw new PageNotFound(sprintf('Impossibile trovare il controller "%s" con path "%s"',
  	  $controllerName,
  	  $filename));
	}

	/**
	 * Eseguire qui tutte le funzioni di shutdown dell'applicazione
	 *
	 * @return void
	 */
	public function shutdown()
	{
	  // shutdown del database manager
	  if($this->dataController && method_exists($this->dataController, 'shutdown'))
	  {
	    $this->dataController->shutdown();
	  }

	  // shutdown sessione
    if($this->getSession() && method_exists($this->getSession(), 'shutdown'))
    {
      $this->getSession()->shutdown();
    }
    
    // @todo sistema autonomo (non nativo di PHP si intende) di registrazione degli "shutdown" per controllarne l'ordine di esecuzione

    // stop di tutti i cronometri
	  if(Config::get('LOG/debug'))
    {
      //foreach (sfTimerManager::getTimers() as $name => $timer)
      //{
      //  $this->logger(sprintf('Core | shutdown | %s %.2f ms (%d)', $name, $timer->getElapsedTime() * 1000, $timer->getCalls()), 7);
      //}

      //$this->logger(sprintf("Core | shutdown | Total Execution time: %f Memory peak: %f Bytes", sfTimerManager::getTimer('Total execution')->getElapsedTime(), number_format(memory_get_peak_usage(true))), 7);
    }

    // shutdown logging
	 // Logger::shutdown();
	}

  /**
   * Routine per inizializzare la lingua dell'applicazione
   *
   * @return void
   */
  protected function initializeLanguage()
  {
    $this->lang = Config::get('LANG/default', 'it');

    if (isset($_SESSION['lang']))
    {
      $this->logger("Core | initializeLanguage | lingua in sessione : ".$_SESSION['lang'],9);
      $this->lang = $_SESSION['lang'];
    }

    if (isset($_REQUEST['lang']))
    {
      $this->logger("Core | initializeLanguage | lingua URL : ".$_REQUEST['lang'],9);
      $this->lang = $_REQUEST['lang'];
    }

    $r = Config::get('LANG/languages');

    if(strpos($r, $this->lang) === false)
    {
      $this->lang = Config::get('LANG/default', 'it');
    }

    $this->logger("Core | initializeLanguage | lingua corrente : {$this->lang} ($r)",9);

    $_SESSION['lang'] = $this->lang;
  }

  /**
   * Si occupa di inizializzare la sessione utente
   *
   * factory-method per oggetti di tipo Session
   *
   * La sessione può essere inizializzata solo quando il processo PHP è eseguito come cgi, httpd
   */
  protected function initializeSession()
  {
    if(!$this->configuration->isCli())
    {
      $sessionClass = Config::get('SESSION/class', 'PhpSession');

      $params = array('name'   => Config::get('SESSION/name', 'PHPSESSID'),
                      'path'   => Config::get('SESSION/path', '/'),
                      'domain' => Config::get('SESSION/domain', $_SERVER['HTTP_HOST']),
                      'ttl'    => Config::get('SESSION/ttl', 3600));

      //var_dump(Autoload::getInstance()->loadClass($sessionClass));
      $this->session = new $sessionClass($params);

      $this->event_dispatcher->notify(new Event($this->session, 'session.initialize'));

      if(!($this->session instanceof Session))
      {
        throw new CoreException(sprintf('L\'oggetto "%s" non è di tipo Session', $sessionClass));
      }
    }
  }

  /**
   * @return DataController
   */
  public function getDataController()
  {
    return $this->dataController;
  }

	/**
	 * Proxy method per inviare un messaggio al logger
	 *
	 * @param string  $msg
	 * @param integer $level
	 */
	public function logger($msg,$level)
	{
	  Logger::log($msg, $level);
	}

	/**
	 * Include nell'esecuzione il file contenente la classe del controller di pagina
	 *
	 * @throws PageNotFoundException File del controller non trovato
	 * @param string $controllerName
	 */
	public static function loadController($controllerName)
	{
        $namespace = ucfirst($this->configuration->getApplicationName()) . '\\' . $controllerName;



	  foreach(self::getCurrentInstance()->configuration->getControllersDir() as $path)
	  {
	    if(file_exists($filename = $path . DIRECTORY_SEPARATOR . self::parseControllerFileName($controllerName) . '.php'))
	    {
	      include_once $filename;

	      //Config::get('LOG/debug') && Logger::debug(sprintf('Core | loadController | Inclusa la classe "%s" per il controller "%s"', $filename, $controllerName));

	      return;
	    }
	  }

	  throw new PageNotFound(sprintf('Impossibile trovare il controller "%s" con path "%s"',
	    $controllerName,
	    $filename));
	}

	/**
	 * Ritorna l'istanza Session corrente
	 *
	 * @return Session
	 */
	public function getSession()
	{
	  return $this->session;
	}

	/**
	 * Questa funzione si occupa di gestire il nome del controller di pagina definito come parametro
	 * e trasformare il valore della stringa in un nome valido di file di controller di pagina
	 *
	 * @param string $name
	 * @return string
	 */
	public static function parseControllerFileName(&$name)
	{
	  preg_match_all('/([^-_.]*)/', $name, $result, PREG_PATTERN_ORDER);
    $result = $result[0];

    $name = '';
    foreach($result as $token)
    {
      if(empty($token))
      {
        continue;
      }
      $name .= ucfirst($token);
    }
    return $name;
	}



  /**
   * Ritorna la corrente istanza per il corrente contesto applicativo
   *
   * @return Core
   */
  public static function getCurrentInstance()
  {
    return self::$instance;
  }

  /**
   * Interrompe il buffer dello stdout per stampare a video un messaggio
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
   *
   */
  public function getZone()
  {
    return $this->zone;
  }

  public function getPage()
  {
    return $this->page;
  }

  public function getOperation()
  {
    return $this->oper;
  }

  public function getActionName()
  {
    return $this->action;
  }

  public function getControllerName()
  {
    return $this->controller;
  }

  /**
   * Ritorna la lingua corrente
   *
   * @return string
   */
  public function getLang()
  {
    return $this->lang;
  }

  /**
   * Imposta il nome del layout da caricare
   *
   * @param string $name
   * @return void
   */
  public function setView($name = "")
  {
    $this->view = $name;

    if(empty($name))
    {
      $name = $this->getActionTemplate();
    }

    $this->getRenderer()->setTemplate($name);
  }

  /**
   * Proxy-method per Route::getParam();
   *
   * @param $param
   * @param $default
   * @return mixed
   */
  public function getRequestParameter($param, $default = null)
  {
    return Routing::getCurrentRequestRoute()->getParam($param, $default);
  }

  /**
   * Proxy-method per Route::setParameter();
   *
   * @param $param
   * @param $default
   * @return mixed
   */
  public function setRequestParameter($param, $value = null)
  {
      return Routing::getCurrentRequestRoute()->setParameter($param, $value);
  }

  /**
   * ritorna il nome della directory corrente
   *
   * @return string
   */
  public function getSite()
  {
    return $this->configuration->getSite();
  }

  /**
   * ritorna l'id del sito
   *
   * Nota: L'id è uguale al gruppo associato se questo esiste
   *
   * @return integer
   */
  public function getSiteId()
  {
    return intval($this->configuration->getSiteId());
  }

  /**
   * @return ProjectConfiguration
   */
  public function getConfiguration()
  {
    return $this->configuration;
  }

  /**
   * Imposta un header http
   *
   * @param string $header
   * @return void
   */
  public function setHttpHeader($header)
  {
    $this->httpHeaders[$header];
  }

  /**
   * Invia gli headers http
   *
   * @return boolean True se invia gli header False se sono già stati inviati
   */
  public function sendHttpHeaders()
  {
    throw new CoreException('Implementare la funzione');
  }

  /**
   * Dispatching della richiesta HTTP
   *
   * Questa funzione si occupa di inizializzare la richiesta, identificare la
   * richiesta interna ed eseguirla
   *
   */
  public function dispatch()
  {
    if(!$this->getConfiguration()->isDebug())
    {
      ob_start();     // il buffer inizia qui così gli startup errors del Core sono stampati sullo stdout
    }

    Routing::initialize();
    $route = Routing::matchCurrentRequest();

    $this->initializeRequest($route);

    $this->forward($route->getController(), $route->getAction());

    if(!$this->getConfiguration()->isDebug())
    {
      ob_end_clean();
    }
  }

  /**
   * Inizializza l'applicazione in base alla Route data
   *
   * imposta i parametri della request ed inizializza il controller di pagina
   *
   */
  public function initializeRequest(Route $route)
  {
    $this->setRequestParameters($route->getAllParameters());

    //$this->controller_obj = $this->initializeController($route->getParam('p'), $route->getParam('module'));
  }

  /**
   * Crea e ritorna il controller di pagina associato alla pagina
   *
   * Questa funzione gestisce sia un action per file sia più actions definite in un unico file (moduli)
   *
   * Esempio di action singola filename 'Home.php':
   * <code>
   * class Home
   * {
   *   public function execute()
   *   {
   *     // business logic here
   *   }
   * }
   * </code>
   *
   * Esempio di action multiple per file filename 'SearchControllers.php':
   * <code>
   * // modulo Search
   * class SearchControllers extends Controllers
   * {
   *   public function executeRegione()
   *   {
   *     // business logic here for action Regione
   *   }
   *
   *   public function executeConcessionario()
   *   {
   *     // business logic here for action Concessionario
   *   }
   * }
   * </code>
   *
   *
   * @return Controller
   */
  public function initializeController($controller, $action = null)
  {
    $controllerClassName = ucfirst($this->configuration->getApplicationName()) . '\\Controller\\' . $controller;


    // ritorno l'istanza del controller già inizializzata se esiste
    if(isset($this->controllers[$this->getConfiguration()->getApplicationName()][$controllerClassName]))
    {
      return $this->controllers[$this->getConfiguration()->getApplicationName()][$controllerClassName];
    }

    //self::loadController($controllerClassName);

    $controllerObj = new $controllerClassName($this);
    $this->controllers[$this->getConfiguration()->getApplicationName()][$controllerClassName] = $controllerObj; // salvo il controller nel buffer

    return $controllerObj;
  }

  /**
   * Ritorna l'istanza del controller di pagina corrente
   *
   * @return Controller
   */
  public function getController($name = null)
  {
    if($name)
    {
      if(isset($this->controllers[$this->getConfiguration()->getApplicationName()][$name]))
      {
        return $this->controllers[$this->getConfiguration()->getApplicationName()][$name];
      }
      else
      {
        throw new RuntimeException(sprintf('Connot find controller with name "%s", be sure to initialize it', $name));
      }
    }
    return $this->controller_obj;
  }

  /**
   * Inizializza la variabile globale _REQUEST
   *
   * @param array $params
   */
  public function setRequestParameters($params = array())
  {
    foreach($params as $name => $value)
    {
      $_REQUEST[$name] = $value;
    }
  }

  /**
   * esegue il controller di pagina
   *
   * Dopo l'inizializzazione del core e dell'applicazione questa funzione si occupa
   * di iniziare la logica di business del controller identificato dalla route
   */
  public function execute()
  {
    throw new CoreException('"chi ha osato toccarmi?" Non esisto più, e se mi hai chiamato hai proprio sbagliato');
  }

  public function shallRender()
  {
    return $this->renderView;
  }

  /**
   * Effettua il forward da una action ad un'altra
   *
   * Si tratta di un redirecting interno. Consente di spostare l'esecuzione da un'action
   * ad un'altra senza effettuare un redirect HTTP
   *
   */
  public function forward($controller, $action)
  {
    if($this->nbForwards > 5)
    {
      throw new CoreException('Superato il limite massimo (5) di forward');
    }

    $this->action = $action;
    $this->controller = $controller;

    $this->controller_obj = $this->initializeController($controller, $action);

    //Logger::info(sprintf('Core | forward | forwarding(%d) to "%s/%s"', $this->nbForwards, $module, $action));

    $this->nbForwards++;

    $this->filterManager = new Manager($this);
    $this->filterManager->loadConfiguration();

    $this->filterManager->execute($this->filterManager);
  }
  
  /**
   * Forward interno al controller per la gestione della pagina di errore HTTP 404
   * 
   * @param string $message
   */
  public final function forward404($message)
  {
    $this->forward(Config::get('forward404_action', 'Page404'), Config::get('forward404_module'));
  }

  /**
   * proxy-metod per {@see Routing::redirect()}
   * 
   * @deprecated L'uso non permette di tener traccia del punto in cui è stato richiesto il redirect (...e conseguenza del fatto che Routing non è un oggetto)
   */
  public function redirect($route = '' , $cod = 302)
  {
    Routing::redirect($route, $cod);
  }

  /**
   * Aggiunge un componente web da caricare automaticamente
   *
   * Nota: componenti web definiti staticamente in content.ini
   *
   * @param string $name
   */
  public function addComponent($name)
  {
    throw new CoreException('Metodo non ancora implementato');
  }

  /**
   * rimuove un componente web dall'esecuzione automatica
   *
   * Nota: componenti web definiti staticamente in content.ini
   *
   * @param string $name
   */
  public function removeComponent($name)
  {
    throw new CoreException('Metodo non ancora implementato');
  }

  /**
   * controlla che sia stata effettuata una richiesta asincrona con l'oggetto
   * javascript XMLHTTPRequest
   *
   * @return boolean
   */
  public function isXmlHttpRequest()
  {
    return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest');
  }

  /**
   *
   * @return EventDispatcher
   */
  public function getEventDispatcher()
  {
    return $this->event_dispatcher;
  }

  // /**
  // * RENDERING FUNCTIONS
  // *
  // */

  /**
   * @deprecated vedi RenderingFilter
   * @throws CoreException
   */
  public function render()
  {
    throw new CoreException('Core::render() - eliminata. vedi RenderingFilter');
  }

  /**
   * Ritorna il path relativo del template in base al nome dell'action corrente
   *
   * @return string
   */
  public function getActionTemplate()
  {
    return $this->getControllerName() . DIRECTORY_SEPARATOR . $this->getActionName();
  }

  /**
   * Disabilita il caricamento del motore di rendering segnalandolo a RenderingFilter
   *
   */
  public function renderWithoutView()
  {
    $this->renderView = false;
  }

  /**
   * carica nella view un componente web
   */
  public function loadComponent($action, $module = null, $vars = array(), $configuration = array())
  {
    Logger::log(sprintf('Core | loadComponent | Load component "%s/%s"', $module, $action), 6);

  	$controller = $this->initializeController($action, $module);

  	foreach ($vars as $name => $value)
  	{
  		$controller->$name = $value;
  	}

  	$componentMethod = 'execute';
  	if($module)
  	{
  		$componentMethod .= ucfirst($action);
  	}

  	$controller->$componentMethod();

    $path = '';
    if($module)
    {
      $path .= strtolower($module) . DIRECTORY_SEPARATOR;
    }

    $path .=  ucfirst($action);

    $renderer = $this->initializeRenderer();

    $renderer->setSuffix('.view');
    $renderer->setTemplate($path);

    echo $renderer->render($this->renderer->variables);
  }

  /**
   * Aggiunge un javascript nel tag HEAD
   *
   * La lista dei files è inidicizzata con chiave inizializzata con il nome del file del foglio stesso
   *
   * Note:
   * - Il metodo si occupa di aggiungere il suffisso <em>.js</em> ai nomi dei files
   * - Il path di <var>$filename</var> pu&ograve; essere assoluto
   *
   * @param string $filename Path del file relativo all'applicazione
   * @param string $position Valori validi [after|before]
   */
  public function addJavascript($filename, $position = 'after')
  {
    if(!Utility::isValidUri($filename))
    {
	    if(!Utility::isAbsolutePath($filename))
	    {
	      $filename = strtolower('/' . $this->getConfiguration()->getApplicationName() . '/js/' . $filename);
	    }
    }

    if($position == 'before')
    {
      $this->javascript = array_merge(array($filename => $filename), $this->javascript);
      return;
    }
    $this->javascript[$filename] = $filename;
    return;
  }

  /**
   * Aggiunge una lista di files javascript
   *
   * @param array $filenames
   */
  public function addJavascripts($filenames)
  {
    foreach($filenames as $filename)
    {
      $this->addJavascript($filename);
    }
  }

  /**
   * Aggiunge un foglio di stile nel tag HEAD
   *
   * La lista dei files è inidicizzata con chiave inizializzata con il nome del file del foglio stesso
   *
   * Note:
   * - Il metodo si occupa di aggiungere il suffisso <em>.css</em> ai nomi dei files
   * - Il path di <var>$filename</var> pu&ograve; essere assoluto
   *
   * Esempio d'uso:
   * <code>
   * Core::addStylesheet('main');
   * Core::addStylesheet('ie6', array('position' => 'before'));
   * Core::addStylesheet('print', array('media' => 'print'));
   *
   * // genera
   *
   * <link href="/application_name/css/ie6.css" type="text/css" rel="stylesheet" media="all" />
   * <link href="/application_name/css/main.css" type="text/css" rel="stylesheet" media="all" />
   * <link href="/application_name/css/print.css" type="text/css" rel="stylesheet" media="print" />
   *
   * </code>
   *
   * @param string $filename   Path del file relativo all'applicazione
   * @param array  $parameters
   */
  public function addStylesheet($filename, $parameters = array())
  {
    if(!Utility::isAbsolutePath($filename) && !Utility::isValidUri($filename))
    {
      $filename = strtolower('/' . $this->getConfiguration()->getApplicationName() . '/css/' . $filename);
    }

    $position = isset($parameters['position']) ? $parameters['position'] : 'after';

    $ary = array('filename' => $filename,
                 'media'    => isset($parameters['media']) ? $parameters['media'] : 'all',
                 'version'  => isset($parameters['version']) ? $parameters['version'] : null);

    if($position == 'before')
    {
      $this->stylesheet = array_merge(array($filename => $ary), $this->stylesheet);
      return;
    }
    else
    {
      $this->stylesheet[$filename] = $ary;
      return;
    }
  }

  /**
   * Aggiunge una lista di files javascript
   *
   * @param array $filenames
   */
  public function addStylesheets($filenames)
  {
    foreach($filenames as $filename => $parameters)
    {
      $this->addStylesheet($filename, $parameters);
    }
  }

  /**
   * Rimuove tutti i javascript
   */
  public function cleanJavascript()
  {
    $this->javascript = array();
  }

  /**
   * Rimuove tutti i fogli di stile
   */
  public function cleanStylesheet()
  {
    $this->stylesheet = array();
  }

  /**
   * Ritorna la stringa da inserire nell'header html per caricare i javascript
   *
   * @return string
   */
  public function loadJavascript()
  {
    $str = '';
    foreach ($this->javascript as $filename => $javascript)
    {
      $str .= sprintf('  <script type="text/javascript" src="%s%s"></script>' . "\n", $javascript , (Utility::isValidUri($javascript)? '':'.js' ) );
    }
    return $str;
  }

  /**
   * Ritorna la stringa da inserire nell'header html per caricare i fogli di stile
   *
   * @return string
   */
  public function loadStylesheet()
  {
    $str = '';
    foreach ($this->stylesheet as $filename => $stylesheet)
    {
      $str .= sprintf('  <link href="%s.css%s" type="text/css" rel="stylesheet" media="%s" />' . "\n",
                      $stylesheet['filename'],
                      isset($stylesheet['version']) ? '?' . $stylesheet['version'] : '',
                      $stylesheet['media']);
    }
    return $str;
  }
  
  public function loadHeadTags()
  {
    $str = '';
    foreach ($this->headTags as $tag)
    {
      $str .= $tag;
    }
    return $str;
  }

  /**
   * Aggiunge un meta all'head HTML
   *
   * @param string $name
   * @param string $content
   */
  public function addMeta($name, $content)
  {
    $this->meta[$name] = $content;
  }

  /**
   * Carica i meta dell'head HTML
   */
  public function loadMeta()
  {
    $str = '';
    foreach ($this->meta as $name => $content)
    {
      $attributes = '';
      if(is_array($content))
      {
        foreach ($content as $key => $value)
        {
            if($key == 'title')
            {

                $str .= sprintf('  <title> %s' . "</title> \n", $value );
            }
            else
            {
                $attributes .= sprintf('%s="%s" ', $key, $value);
            }

        }
      }
      else
      {
          if($name == 'title')
          {

              $str .= sprintf('  <title> %s' . "</title> \n", $content );
          }
          else
          {
            $attributes = sprintf('name="%s" content="%s" ', $name, $content);
          }
      }
      $str .= $attributes? sprintf('  <meta %s />' . "\n", $attributes):'';
    }
    return $str;
  }

  /**
   * Inizializza il motore di rendering
   */
  public function initializeView()
  {
    $this->renderer = $this->initializeRenderer();

    //$this->renderer->setSuffix( Config::get('VIEW/suffix', '.view'));

    $this->renderer->addVariable('context', $this);

    $this->event_dispatcher->dispatch('view.configure', new GenericEvent($this->renderer));

    $this->isViewInitialized = true;
  }

  public function initializeRenderer($class = null, $params = array())
  {
    $class = $class ? $class : Config::get('VIEW/class', 'Core\View\Php');

    $params = array_merge(array(
        'default_path' => $this->configuration->getTemplatesDir()
    ), $params);

    $renderer = new $class(
        $this,
        $params,
        $this->event_dispatcher);

    if(!($renderer instanceof \Core\View\View))
    {
      throw new \RuntimeException('La variabile $renderer non è di tipo View');
    }

    $renderer->setSuffix(''); // default suffix

    $this->event_dispatcher->dispatch('view.configure', new GenericEvent($renderer));

    return $renderer;
  }

  /**
   * @return View
   */
  public function getRenderer()
  {
    if(!$this->isViewInitialized)
    {
      $this->initializeView();
    }

    return $this->renderer;
  }

  public function enablePlugin($name)
  {
    $name = ucfirst($name) . 'Plugin';
    $plugin = new $name();

    $plugin->initialize($this);

    Logger::info('Core | enablePlugin | Plugin ' . $name . ' inizializzato');
  }
  
  public function addHeadTag($tag, array $attributes, $content = '')
  {
    $attributesString = '';
    foreach($attributes as $param => $value)
    {
      $attributesString .= sprintf(' %s="%s"', $param, $value);
    }

    if($content)
    {
      $this->headTags[] = sprintf('<%s %s>%s</%s>' . "\n", $tag, $attributesString, $content, $tag);
    }
    else
    {
      $this->headTags[] = sprintf('<%s %s />'. "\n", $tag, $attributesString);
    }
  }
}

