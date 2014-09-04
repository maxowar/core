<?php

namespace Core\Routing;

use Core\Exception\PageNotFound;
use Core\Routing\Route\Route;
use Core\Util\Config;

/**
 * Contenitore e gestore delle regole di routing
 *
 * @author Massimo Naccari <massimo.naccari@wdmn.it>
 * @copyright Massimo Naccari
 * @package core
 * @subpackage routing
 */
class Routing
{
  const ROUTE_NAME_DEFAULT        = 'default';
  const ROUTE_NAME_MODULE_DEFAULT = 'default_module';
  const CONFIG_FILENAME           = 'routing.php';

  const PREPEND = 'prepend';
  const APPEND  = 'append';

  const ENV_EXTENSION_DISABLES = 'ROUTING_EXTENSION_DISABLED';

  const POST     = 'post';
  const GET      = 'get';
  const PUT      = 'put';
  const DELETE   = 'delete';
  const HEAD     = 'head';
  const OPTIONS = 'options';

  /**
   * Elenco delle regole registrate
   *
   * @var array
   */
  private static $routes = array();

  /**
   * Regola corrente
   *
   * @var Route
   */
  private static $currentRoute;

  /**
   * Il nome della route della request corrente
   *
   * @var string
   */
  private static $currentRouteName;

  /**
   *
   * @var CacheRouting
   */
  private static $cache;

  /**
   * array dei files
   *
   * @var array
   */
  private static $fixedFileArray = false;

  /**
   * Mappa dei codici di stato HTTP
   *
   * @var array
   */
  static protected $statusTexts = array(
    '100' => 'Continue',
    '101' => 'Switching Protocols',
    '200' => 'OK',
    '201' => 'Created',
    '202' => 'Accepted',
    '203' => 'Non-Authoritative Information',
    '204' => 'No Content',
    '205' => 'Reset Content',
    '206' => 'Partial Content',
    '300' => 'Multiple Choices',
    '301' => 'Moved Permanently',
    '302' => 'Found',
    '303' => 'See Other',
    '304' => 'Not Modified',
    '305' => 'Use Proxy',
    '306' => '(Unused)',
    '307' => 'Temporary Redirect',
    '400' => 'Bad Request',
    '401' => 'Unauthorized',
    '402' => 'Payment Required',
    '403' => 'Forbidden',
    '404' => 'Not Found',
    '405' => 'Method Not Allowed',
    '406' => 'Not Acceptable',
    '407' => 'Proxy Authentication Required',
    '408' => 'Request Timeout',
    '409' => 'Conflict',
    '410' => 'Gone',
    '411' => 'Length Required',
    '412' => 'Precondition Failed',
    '413' => 'Request Entity Too Large',
    '414' => 'Request-URI Too Long',
    '415' => 'Unsupported Media Type',
    '416' => 'Requested Range Not Satisfiable',
    '417' => 'Expectation Failed',
    '500' => 'Internal Server Error',
    '501' => 'Not Implemented',
    '502' => 'Bad Gateway',
    '503' => 'Service Unavailable',
    '504' => 'Gateway Timeout',
    '505' => 'HTTP Version Not Supported',
  );

  protected static $initialized = false;
  
  /**
   * Inizializzazione del sistema di routing
   *
   * se esiste la cache
   * - inizializza le routes con quelle presenti nella cache
   * altrimenti
   * - carica il file di configurazione delle routes
   * - inizializza e salva le routes
   *
   * @param array $parametes Parametri per configurare l'inizializzazione
   */
  public static function initialize($parameters = array())
  {
    // gestione delle estensioni che non si vogliono gestire dinamicamente
    if( (isset($_SERVER[self::ENV_EXTENSION_DISABLES]) && $_SERVER[self::ENV_EXTENSION_DISABLES] != '') ||
        (isset($_ENV[self::ENV_EXTENSION_DISABLES]) && $_ENV[self::ENV_EXTENSION_DISABLES] != ''))
    {
      Logger::info('Routing | initialize | Estensione non abilitata');

      throw new PageNotFound('Estensione non abilitata');
    }

    Route::$extension = str_replace('.', '', Config::get('ROUTING/extension', ''));


      self::loadRoutesFromFile(Config::get('MAIN/base_path') . '/config/routing.php');

    self::$initialized = true;
  }

  /**
   * Inizializza regole di routing da un file ini
   *
   * @param string $filename
   * @return void
   */
  public static function loadRoutesFromFile($filename, $position = self::APPEND, $clearAll = false)
  {
    if(!file_exists($filename))
    {
      throw new \Exception(sprintf('Il file delle configurazioni delle regole di routing non è disponibile. Controllare che esista "%s"', $filename));
    }

    $clearAll ? self::clear() : null;

    $routes = include $filename;

    if(!is_array($routes))
    {
      return;
    }

        self::addRoutes($routes);
  }

  /**
   * Aggiunge una regola all'inizio delle definizioni
   *
   * @param Route $route
   * @return void
   */
  public static function prependRoute(Route $route)
  {
    $routes = self::$routes;
    self::$routes = array();

    self::$routes[$route->getName()] = $route;

    foreach($routes as $route)
    {
      self::$routes[$route->getName()] = $route;
    }
  }

  /**
   * Aggiunge una regola alla fine delle definizioni
   *
   * @param Route $route
   * @return void
   */
  public static function appendRoute(Route $route)
  {
    self::$routes[$route->getName()] = $route;
  }
  
  /**
   * Inserisce prima di 
   * 
   * @param Route $route
   */
  public static function prependRouteTo(Route $route, $position)
  {
    if(!self::$routes[$position])
    {
      throw new CoreException(sprintf('Cannot prepend to route at "%s"', $position));
    }
    
    self::$routes = array_insert(self::$routes, $route, $position);
  }

  /**
   * elimina tutte le regole registrate fin'ora
   *
   * @return void
   */
  public static final function clear()
  {
    self::$routes = array();
  }

  /**
   * Aggiunge una nuova route in append
   *
   * Funzione alias di {@link Core::appendRoute()}
   *
   * @param Route $route
   */
  public static function add(Route $route)
  {
    self::appendRoute($route);
  }

  /**
   * Aggiunge una lista di route al routing
   *
   * @param array $routes
   */
  public static function addRoutes(array $routes)
  {
    foreach($routes as $route)
    {
      self::add($route);
    }
  }

  /**
   * Controllo se esiste una route
   *
   * @param string $routeName
   * @return boolean
   */
  public static function has($routeName)
  {
    return array_key_exists((string)$routeName, self::$routes);
  }

  /**
   * Ritorna una route
   *
   * @param string $routeName
   * @return Route
   */
  public static function get($routeName)
  {
    if(!self::$initialized) Routing::initialize();
    
    if(!isset(self::$routes[$routeName]))
    {
      throw new RuntimeException(sprintf('Non esiste alcuna route "%s"', $routeName));
    }
    
    return self::$routes[$routeName];
  }

  /**
   * ritorna tutte le regole inizializzate
   *
   * @return array
   */
  public static function getAll()
  {
    return self::$routes;
  }

  /**
   * Cerca di trovare il match della richiesta HTTP con le regole di routing interne definite
   *
   * Se la funzione viene chiamata una seconda volta ritorna l'istanza dell'oggetto Route
   * precedentemente identificato, ossia la route corrente
   *
   * @throws PageNotFoundException Nessuna route corrisponde all'URL fornita
   *
   * @return Route La route
   */
  public static function matchCurrentRequest()
  {
    if(self::$currentRoute instanceof Route)
    {
      return self::$currentRoute;
    }

    if(false !== $route = self::parse($_SERVER['REQUEST_URI']))
    {
      self::$currentRoute = $route;
      self::$currentRouteName = $route->getName();

      //Logger::info(sprintf('Routing | matchCurrentRequest | Request "%s %s" match con "%s", parametri %s', strtoupper(self::getRequestMethod()), $_SERVER['REQUEST_URI'], $route->getName(), print_r($route->getAllParameters(), true)));

      return $route;
    }

    // nota: serve per non rompere il codice dove si presuppone che ci sia sempre una (current) Route inizializzata
    self::$currentRoute = new Route('404', array('url' => '/' . $_SERVER['REQUEST_URI'], 'params' => array('p' => Config::get('forward404_action', 'Page404'), 'module' => Config::get('forward404_module'))));
    self::$currentRouteName = '404';

    throw new PageNotFoundException(sprintf('Impossibile trovare una route per "%s"', $_SERVER['REQUEST_URI']));
  }

  /**
   * Esegue il parsing dell'url
   *
   * Le route vengono eseguite nell'ordine in cui sono state definite
   *
   * @throw {@link RoutingException} l'url non è una directory o l'estensione non corrisponde
   *
   * @param string $url Una stringa rappresentante una URL
   * @return Route La route corrispondente | false altrimenti
   */
  public static function parse($url)
  {
    $url = parse_url($url);

    //$url = self::normalizeUrl($url);

    // chiamata diretta al front-controller del tipo /index.php?p=Home[&param=value]
    if (preg_match('%^/[a-z]+.php$%', $url['path']) || 
        Config::get('ROUTING/bc') && isset($_GET['p']))
    {
      if(!Config::get('ROUTING/allow_query_string', false))
      {
        throw new RoutingException('Non è consentito inserire parametri come query string nell\'url');
      }
      return self::getDefaultRoute()->setParameters($_GET);
    }

    // ciclo su tutte le route definite per trovare quella che corrisponde all'URL corrente
    foreach (self::$routes as $name => $route)
    {
      $clone = clone $route;
      if (!$clone->matchesUrl($url['path']))
      {
        unset($clone);
        continue;
      }
      return $clone;
    }
    return false;
  }

  /**
   * Ritorna la route di default
   *
   * La route di default viene usata come catch-all
   *
   * @return Route
   */
  public static function getDefaultRoute()
  {
    return self::$routes[self::ROUTE_NAME_DEFAULT];
  }

  /**
   * Normalizzazione dell'url
   *
   * - url inizia con '/'
   * - query_string eliminata
   * - caratteri '/' consecutivi eliminati
   *
   * @param $url
   * @return unknown_type
   */
  protected static function normalizeUrl(&$url)
  {
    // an URL should start with a '/', mod_rewrite doesn't respect that, but no-mod_rewrite version does.
    if ('/' != substr($url, 0, 1))
    {
      $url = '/'.$url;
    }

    // we remove the query string
//    if (false !== $pos = strpos($url, '?'))
//    {
//      $url = substr($url, 0, $pos);
//    }

    // remove multiple /
    $url = preg_replace('#/+#', '/', $url);

    return $url;
  }

  /**
   * Effettua un redirect ad un URI
   *
   * @param mixed   $route
   * @param integer $cod   Un codice HTTP valido
   */
  public static function redirect($route = '' , $cod = 302)
  {
    if(Config::get('LOG/debug'))
    {
      $trace = debug_backtrace();
      Logger::log('Routing | redirect | Redirect invocato in "' . $trace[0]['file'] . '(' . $trace[0]['line'] . ')" codice HTTP: ' . $cod, Logger::DEBUG);
    }

    // url
    if(!is_object($route) && (Utility::isValidUri($route) || Utility::isAbsolutePath($route)))
    {
      self::doRedirect($route);
    }

    $destination = '/';
    if(empty($cod) || !is_numeric($cod))
    {
      $cod = 302;
    }
    
    // route name
    if(is_string($route))
    {
      $route = Routing::get($route);
    }

    // route object
    if($route instanceof Route)
    {
      $destination = $route->createUrl();
    }
    
    // what a shit u gave me?
    else
    {
      throw new CoreException('$route must be a valid url, string route name or Route obj');
    }

    self::normalizeUrl($destination);

    self::doRedirect($destination, $cod);
  }

  /**
   * Inizializza gli header HTTP per effettuare un redirect
   *
   * @param string  $destination L'URI di destinazione
   * @param integer $cod
   */
  private static final function doRedirect($destination, $cod = 302)
  {
    if(array_key_exists($cod, self::$statusTexts))
    {
      $textStatus = self::$statusTexts[$cod];
    }
    else
    {
      $cod = 302;
      $textStatus = self::$statusTexts[$cod];
    }

    Logger::info('Routing | redirect | Redirecting all\'indirizzo "' . $destination . '"');

    header('HTTP/1.1 '.$cod.' '.$textStatus);
    header('Location: ' . $destination);
    exit(0);
  }

  /**
   * ritorna l'istanza di route relativa alla richiesta HTTP corrente
   *
   * @return Route
   */
  public static function getCurrentRequestRoute()
  {
    return self::$currentRoute;
  }

  /**
   * Ritorna il nome della route corrente o lo confronta con il parametro della funzione
   *
   * @param string $matchName
   * @return string|boolean Se <var>$matchName</var> &egrave; diverso da null la funzione ritorna il test {@source 1 3 }
   */
  public static function getCurrentRequestRouteName($matchName = null)
  {
    if($matchName)
    {
      return self::$currentRouteName == $matchName;
    }

    return self::$currentRouteName;
  }

  /**
   * Ritorna i parametri della richiesta HTTP corrente
   *
   * @param $parameters
   * @return array
   */
  public static function getCurrentRequestParameters($parameters = array())
  {
    return self::$currentRoute->getAllParameters($parameters);
  }

  /**
   * Crea un nuovo oggetto Route inizializzandolo con i parametri della request
   * dell'oggetto Route passato
   *
   * @param Route $route
   */
  public function cloneRoute($name, Route $route)
  {
    $clone = new Route($name);
    $clone->setParameters($route->getAllParameters());
    return $clone;
  }

  /**
   * Ritorna il metodo HTTP della request
   *
   * Viene data precedenza al metodo della richiesta fittizio, impostabili attraverso il parametro 'request_method',
   * questo per simulare tutti i metodi disponibili dal protocollo HTTP ma non usabili
   * attraverso i browser
   *
   * Esempio di utilizzo:
   * <code>
   * if(Routing::getRequestMethod() == Routing::POST)
   * {
   *   echo "POST";
   * }
   * </code>
   *
   * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html HTTP Method Definitions
   *
   * @return string Il metodo HTTP della request
   */
  public static function getRequestMethod()
  {
    if(self::getCurrentRequestRoute())
    {
      return strtolower(self::getCurrentRequestRoute()->getParam('request_method') == null ?
                        $_SERVER['REQUEST_METHOD'] :
                        self::getCurrentRequestRoute()->getParam('request_method'));
    }
    return null;
  }

  /**
   * Retrieves an array of files.
   *
   * {@see sfWebRequest} property of symfony
   *
   * @param  string $key  A key
   * @return array  An associative array of files
   */
  public static function getFiles($key = null)
  {
    if (false === self::$fixedFileArray)
    {
      self::$fixedFileArray = self::convertFileInformation($_FILES);
    }

    return null === $key ? self::$fixedFileArray : (isset(self::$fixedFileArray[$key]) ? self::$fixedFileArray[$key] : array());
  }

  /**
   * Converts uploaded file array to a format following the $_GET and $POST naming convention.
   *
   * It's safe to pass an already converted array, in which case this method just returns the original array unmodified.
   *
   * {@see sfWebRequest} property of symfony
   *
   * @param  array $taintedFiles An array representing uploaded file information
   *
   * @return array An array of re-ordered uploaded file information
   */
  static public function convertFileInformation(array $taintedFiles)
  {
    $files = array();
    foreach ($taintedFiles as $key => $data)
    {
      $files[$key] = self::fixPhpFilesArray($data);
    }

    return $files;
  }

  /**
   * {@see sfWebRequest} property of symfony
   *
   * @param unknown_type $data
   */
  static protected function fixPhpFilesArray($data)
  {
    $fileKeys = array('error', 'name', 'size', 'tmp_name', 'type');
    $keys = array_keys($data);
    sort($keys);

    if ($fileKeys != $keys || !isset($data['name']) || !is_array($data['name']))
    {
      return $data;
    }

    $files = $data;
    foreach ($fileKeys as $k)
    {
      unset($files[$k]);
    }
    foreach (array_keys($data['name']) as $key)
    {
      $files[$key] = self::fixPhpFilesArray(array(
        'error'    => $data['error'][$key],
        'name'     => $data['name'][$key],
        'type'     => $data['type'][$key],
        'tmp_name' => $data['tmp_name'][$key],
        'size'     => $data['size'][$key],
      ));
    }

    return $files;
  }

  public static function setCurrentRequestRoute(Route $route)
  {
    self::$currentRoute = $route;
    self::$currentRouteName = $route->getName();
  }
  
  public static function getHost()
  {
    return $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
  }
}

/**
 * Qui vengono raccolte le funzioni di utilità riguardanti il routing
*
* @author Massimo Naccari
* @copyright Massimo Naccari
* @package core
* @subpackage routing
*/

/**
 * Stampa l'HTML corrsipondente al tag "a" per una determinata route
*
* @param string $content
* @param string $routeName
* @param array  $parameters
* @param array  $htmlParameters
* @return string
*/
function link_to($content, $routeName, $parameters = array(), $htmlParameters = array())
{
  $attributes = '';

  if(isset($htmlParameters['post']))
  {
    $htmlParameters['onclick'] = _method_javascript_function(isset($parameters['request_method']) ? $parameters['request_method'] : Routing::POST , isset($parameters['confirm']) ? $parameters['confirm'] : 'Sei sicuro?' );
    unset($parameters['confirm']);
    unset($htmlParameters['post']);
    unset($parameters['request_method']);
  }

  if(isset($htmlParameters['selected_if']) && $htmlParameters['selected_if'] == true)
  {
    $htmlParameters['class'] = (isset($htmlParameters['class']) ? $htmlParameters['class'] . ' ' : '') . 'selected';
  }

  foreach($htmlParameters as $param => $value)
  {
    $attributes .= sprintf(' %s="%s"', $param, $value);
  }
  $absolute = isset($parameters['absolute']) ? $parameters['absolute'] : false;
  $qsa = isset($parameters['query_string']) ? '?' . $parameters['query_string'] : '';
  unset($parameters['query_string']);

  $attributes = ' href="' . url_for($routeName, $parameters, $absolute) . $qsa  .'"' . $attributes;

  return sprintf('<a%s>%s</a>', $attributes, $content);
}


/**
 * Crea l'url per una route
 *
 * @param string  $route
 * @param array   $parameters
 * @param boolean $absolute
 * @return string
 */
function url_for($route, array $parameters = array(), $absolute = false)
{
  if (Utility::isValidUri($route))
  {
    return $route;
  }
  else if(Routing::has($route))
  {
    $route = Routing::get($route);
  }
  else
  {
    throw new RoutingException(sprintf('La route "%s" non esiste', $route), RoutingException::STOP_ON_BC);
  }

  if (isset($parameters['domain']))
  {
    // $route->setDomain($domain); @todo mi piacerebbe fare così, e quindi anche setProtocol('https')
    $domain = $parameters['domain'];
    unset($parameters['domain']);
  }

  return ($absolute === true ?
  (isset($domain) ? $domain : Config::get('MAIN/base_url')) :
  '') .
  $route->createUrl($parameters);
}


function _method_javascript_function($method , $confirm)
{
  $function = "if (confirm('" .  addslashes($confirm) . "')) { var f = document.createElement('form'); f.style.display = 'none'; this.parentNode.appendChild(f); f.method = 'post'; f.action = this.href;";

  if ('post' != strtolower($method))
  {
    $function .= "var m = document.createElement('input'); m.setAttribute('type', 'hidden'); ";
    $function .= sprintf("m.setAttribute('name', 'request_method'); m.setAttribute('value', '%s'); f.appendChild(m);", strtolower($method));
  }

  $function .= "f.submit(); };return false;";

  return $function;
}

/**
 * Stampa l'HTML che rappresenta il tag <img>
 *
 * @param string $url
 * @param array  $htmlParameters
 */
function image_tag($url, $htmlParameters = array())
{
  $attributes = '';

  if(!isset($htmlParameters['alt']))
  {
    $htmlParameters['alt'] = '';
  }

  if(!preg_match('/\.(gif|png|jpe?g)$/i', $url))
  {
    $url .= '.jpg';
  }

  // non cominicia con uno slash
  // e
  // non inizia con la stringa 'http:'
  if(!Utility::isAbsolutePath($url) && !preg_match('/^http:/i', $url))
  {
    // @todo eliminare questa cosa aberrante. usare l'oggetto  ApplicationConfiguration o una costante
    $url = '/' . Core::getCurrentInstance()->getConfiguration()->getApplicationName() . '/' . 'img' . '/' . $url;
  }

  foreach($htmlParameters as $param => $value)
  {
    $attributes .= sprintf(' %s="%s"', $param, $value);
  }

  return sprintf('<img src="%s" %s />', $url, $attributes);
}

/**
 * Crea la stringa che rappresenta l'input tag HTML per impostare in un form il metodo della request
 *
 * <code>
 * <input type="hidden" name="request_metohd" value="[post|get|put|head|options|delete]" />
 * </code>
 *
 * @param string $method Un metodo HTTP valido
 * @return string Il tag input HTML
 */
function request_method_input_tag($method = 'post')
{
  if(!constant('Routing::' . strtoupper($method)))
  {
    throw new CoreException(sprintf('Il metodo HTTP "%s" non esiste', strtoupper($method)));
  }

  return sprintf('<input type="hidden" name="request_method" value="%s" />', $method);
}


