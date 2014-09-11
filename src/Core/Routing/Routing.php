<?php

namespace Core\Routing;

use Core\Http\Request;
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
  const GENERIC_ROUTE_NAME        = 'generic_route'; // avoid use of it
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
  private $routes = array();

    private $extension;

  /**
   * Regola corrente
   *
   * @var Route
   */
  private $currentRoute;

  /**
   * Il nome della route della request corrente
   *
   * @var string
   */
  private $currentRouteName;

  protected $initialized = false;
  
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
  public function __construct()
  {
    // gestione delle estensioni che non si vogliono gestire dinamicamente
    if( (isset($_SERVER[self::ENV_EXTENSION_DISABLES]) && $_SERVER[self::ENV_EXTENSION_DISABLES] != '') ||
        (isset($_ENV[self::ENV_EXTENSION_DISABLES]) && $_ENV[self::ENV_EXTENSION_DISABLES] != ''))
    {
      //Logger::info('Routing | initialize | Estensione non abilitata');

      throw new \PageNotFound('Estensione non abilitata');
    }

    $this->extension = str_replace('.', '', Config::get('ROUTING/extension', ''));

    $this->initialized = true;
  }

  /**
   * Inizializza regole di routing da un file ini
   *
   * @param string $filename
   * @return void
   */
  public function loadRoutesFromFile($filename, $position = self::APPEND, $clearAll = false)
  {
    if(!file_exists($filename))
    {
      throw new \Exception(sprintf('Il file delle configurazioni delle regole di routing non è disponibile. Controllare che esista "%s"', $filename));
    }

    $clearAll ? $this->clear() : null;

    $routes = include $filename;

    if(!is_array($routes))
    {
      return;
    }

        $this->addRoutes($routes);
  }

  /**
   * Aggiunge una regola all'inizio delle definizioni
   *
   * @param Route $route
   * @return void
   */
  public function prependRoute(Route $route)
  {
    $routes = $this->routes;
    $this->routes = array();
    $this->routes[$route->getName()] = $route;

    foreach($routes as $route)
    {
      $this->$routes[$route->getName()] = $route;
    }
  }

  /**
   * Aggiunge una regola alla fine delle definizioni
   *
   * @param Route $route
   * @return void
   */
  public function appendRoute(Route $route)
  {
    $this->routes[$route->getName()] = $route;
  }
  
  /**
   * Inserisce prima di 
   * 
   * @param Route $route
   */
  public function prependRouteTo(Route $route, $position)
  {
    if(!$this->routes[$position])
    {
      throw new \InvalidArgumentException("Route '$position' doesnt exists");
    }
    
    $this->routes = array_insert($this->routes, $route, $position);
  }

  /**
   * elimina tutte le regole registrate fin'ora
   *
   * @return void
   */
  public final function clear()
  {
    $this->routes = array();
  }

  /**
   * Aggiunge una nuova route in append
   *
   * Funzione alias di {@link Core::appendRoute()}
   *
   * @param Route $route
   */
  public function add(Route $route)
  {
    $this->appendRoute($route);
  }

  /**
   * Aggiunge una lista di route al routing
   *
   * @param array $routes
   */
  public function addRoutes(array $routes)
  {
    foreach($routes as $route)
    {
      $this->add($route);
    }
  }

  /**
   * Controllo se esiste una route
   *
   * @param string $routeName
   * @return boolean
   */
  public function has($routeName)
  {
    return array_key_exists((string)$routeName, $this->$routes);
  }

  /**
   * Ritorna una route
   *
   * @param string $routeName
   * @return Route
   */
  public function get($routeName)
  {
    if(!$this->initialized) $this->initialize();

    if(!isset($this->routes[$routeName]))
    {
      throw new \InvalidArgumentException("Route '$routeName' doesnt exists");
    }
    
    return $this->routes[$routeName];
  }

  /**
   * ritorna tutte le regole inizializzate
   *
   * @return array
   */
  public function getAll()
  {
    return $this->routes;
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
  public function matchRequest(Request $request)
  {
    if($this->currentRoute instanceof Route)
    {
      return $this->currentRoute;
    }

    if(false !== $route = $this->parse($request->getUri()))
    {
      $this->currentRoute = $route;
      $this->currentRouteName = $route->getName();

      //Logger::info(sprintf('Routing | matchCurrentRequest | Request "%s %s" match con "%s", parametri %s', strtoupper(self::getRequestMethod()), $_SERVER['REQUEST_URI'], $route->getName(), print_r($route->getAllParameters(), true)));

      return $route;
    }

    // nota: serve per non rompere il codice dove si presuppone che ci sia sempre una (current) Route inizializzata
    $this->currentRoute = new Route('404', array('url' => $request->getUri(), 'params' => array('_controller' => Config::get('forward404_controller', 'Core\Controller\Default'), '_action' => 'pageNotFound')));
    $this->currentRouteName = '404';

      return $this->currentRoute;

    //throw new PageNotFoundException(sprintf('Impossibile trovare una route per "%s"', $_SERVER['REQUEST_URI'])); si? no? boh? forse? meglio? chissà... dilemma angustiante
      // nota: semmai ritornare false o null e lanciare poi una eccezione bah... il dilemma continua
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
  public function parse($url)
  {
    $url = parse_url($url);

    // ciclo su tutte le route definite per trovare quella che corrisponde all'URL corrente
    foreach ($this->routes as $name => $route)
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
  public function getDefaultRoute()
  {
    return $this->routes[self::ROUTE_NAME_DEFAULT];
  }





  /**
   * ritorna l'istanza di route relativa alla richiesta HTTP corrente
   *
   * @return Route
   */
  public function getMatchedRoute()
  {
    return $this->currentRoute;
  }

  /**
   * Ritorna il nome della route corrente o lo confronta con il parametro della funzione
   *
   * @param string $matchName
   * @return string|boolean Se <var>$matchName</var> &egrave; diverso da null la funzione ritorna il test {@source 1 3 }
   */
  public function getCurrentRequestRouteName($matchName = null)
  {
    if($matchName)
    {
      return $this->currentRouteName == $matchName;
    }

    return $this->currentRouteName;
  }

  /**
   * Ritorna i parametri della richiesta HTTP corrente
   *
   * @param $parameters
   * @return array
   */
  public function getCurrentRequestParameters($parameters = array())
  {
    return $this->currentRoute->getAllParameters($parameters);
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





  public function setCurrentRequestRoute(Route $route)
  {
      $this->currentRoute = $route;
      $this->currentRouteName = $route->getName();
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


