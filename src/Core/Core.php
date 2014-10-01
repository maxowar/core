<?php

namespace Core;

use Core\Configuration\Project;
use Core\Filter\Manager;
use Core\Http\Event\FilterRequest;
use Core\Routing\Routing;
use Core\Util\Config;
use Core\Http\Request;
use Core\Http\Response;
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
 * $configuration = new \Frontend\Configuration\Configuration('prod');
 * Core\Core::createInstance($configuration)->dispatch();
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
  private $eventDispatcher;

  /**
   * Nome della vista corrente
   *
   * @var string
   */
  public $view;

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
     * @var \Core\Routing\Routing
     */
    private $routing;

  /**
   *
   * @var ProjectConfiguration
   */
  private $configuration;


  /**
   * @var $filterManager FilterManager
   */
  protected $filterManager;


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
     * @var \Core\Http\Request
     *
     */
    protected $request;

    /**
     * @var \Core\Http\Response
     *
     */
    protected $response;


  /**
   * inizializza le variabili di sistema, imposta i path dell'applicazione
   *
   */
	protected function __construct(Project $projectConfiguration)
	{
	  $this->startTime = microtime(true);

	  //sfTimerManager::getTimer('Total execution');

    $this->configuration = $projectConfiguration;

    $this->eventDispatcher = $projectConfiguration->getEventDispatcher();

        self::$instance = $this; // transform Core back again in an singleton?!?

    register_shutdown_function(array($this, 'shutdown'));
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
        return new self($projectConfiguration);
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
	  if(Config::get('application.debug'))
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

      $this->eventDispatcher->notify(new Event($this->session, 'session.initialize'));

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
  public static function getInstance()
  {
    return self::$instance;
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

    $this->getView()->setTemplate($name);
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
      ob_start();
    }

      $this->request = Request::createFromPHP();
      $this->response = new Response();

    $this->routing = new Routing();
    $this->routing->loadRoutesFromFile(Config::get('application.dir') . '/config/routing.php');
    $route = $this->routing->matchRequest($this->request);

    $this->forward($route->getController(), $route->getAction());
/**
    if(!$this->getConfiguration()->isDebug())
    {
      ob_end_clean();
    }*/

      // life ends here! bye bye Response! :( we will miss u until next Request
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
  public function initializeController($controller)
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
        throw new \RuntimeException(sprintf('Connot find controller with name "%s", be sure to initialize it', $name));
      }
    }
    return $this->controller_obj;
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
      throw new \RuntimeException("Forward limit number '{$this->nbForwards}' reached");
    }

    $this->action = $action;
    $this->controller = $controller;

    $this->controller_obj = $this->initializeController($controller);

    //Logger::info(sprintf('Core | forward | forwarding(%d) to "%s/%s"', $this->nbForwards, $module, $action));

    $this->nbForwards++;

    $this->filterManager = new Manager($this);
    $this->filterManager->loadConfiguration();
    $this->filterManager->execute();
  }

    public function handle()
    {
        $this->eventDispatcher->dispatch('request.filter', new FilterRequest($this->getRequest()));

        return call_user_func(array($this->getController(), $this->action), $this->getRequest(), $this->getResponse());
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
    $this->response->redirect($route, $cod);
  }



  /**
   *
   * @return EventDispatcher
   */
  public function getEventDispatcher()
  {
    return $this->eventDispatcher;
  }

  // /**
  // * RENDERING FUNCTIONS
  // *
  // */


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

    public function getRequest()
    {
        return $this->request;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function getRouting()
    {
        return $this->routing;
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
    $this->renderer->addVariable('context', $this);

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
        $this->eventDispatcher);

    if(!($renderer instanceof \Core\View\View))
    {
      throw new \RuntimeException('La variabile $renderer non è di tipo View');
    }

    $renderer->setSuffix(''); // default suffix

    $this->eventDispatcher->dispatch('view.configure', new GenericEvent($renderer));

    return $renderer;
  }

  /**
   * @return \Core\View\View
   */
  public function getView()
  {
    if(!$this->isViewInitialized)
    {
      $this->initializeView();
    }

    return $this->renderer;
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

