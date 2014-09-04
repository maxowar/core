<?php

namespace Core\Routing\Route;

use Core\Util\Config;

/**
 * Rappresenta una route interna
 *
 * Gli oggetti di tipo Route possono essere inizializzati a partire da un array che rappresenta
 * una route interna secondo il seguente schema:
 * <code>
 * array(
 *   'route_name'       =>     array('url'     => '/cerca/:var/:type/*',            // il pattern dell'URL
 *                                   'class'  => 'Route',                           // il tipo di Route da istanziare
 *                                   'params'  => array('p' => 'Search'),           // valori di default e obbligatori
 *                                                                                  // validatori dei parametri...
 *                                   'requirements' => array('type' => new ChoiceRouteValidator(array('choices' => array_keys(AdvType::$types))))),
 *                                   );
 * </code>
 *
 * Una route deve sempre almeno indicare il parametro "p" che indica il controller di pagina
 *
 * @author Massimo Naccari <massimo.naccari@wdmn.it>
 * @copyright Massimo Naccari
 * @package core
 * @subpackage routing
 */
class Route implements RouteInterface
{
  /**
   * default extension
   *
   * @var string
   */
  public static $extension = '';

  protected $params = array();
  
  private $name,
          $url,
          $regex,
          $compiled,
          $tokens,
          $isDir = false,
          $requirements;

    private $reserved_variable_name = array('_controller', '_action', '_format');

  /**
   * Crea una nuova route e la inizializza con i parametri passati in <var>$parameters</var>
   *
   * Le opzioni obbligatorie per <var>$parameters</var> sono:
   *   - url         : l'url che arriva dal web-server
   *   - params      : elenco di parametri
   *     - p         : nome dell'action o del controller se 'module' non &egrave; presente
   *     - module    : nome del modulo
   *     - :var      : valore di default per il parametro della request 'var'
   *
   * @param $name       Nome della route
   * @param $parameters Parametri della route
   */
  public function __construct($name, $parameters = array())
  {
    $this->name       = $name;
    $this->compiled   = false;
    $this->tokens     = array();

    $this->initialize($parameters);
  }

  /**
   * Ritorna l'url compilata
   *
   * {@see Route::createUrl()}
   */
  public function __toString()
  {
    return $this->createUrl();
  }

  /**
   * Inizializza l'oggetto corrente
   *
   * @throws CoreException Non &egrave; specificato ne una variabile 'p' ne una variabile 'module'
   *
   * @param array $parameters
   */
  protected function initialize(array $parameters)
  {
    $params = isset($parameters['params']) ? $parameters['params'] : array();
    $this->url    = $parameters['url'];

    $this->parse();

    $this->params = array_merge($this->params, $params);

    // manca il parametro obbligatorio p e il modulo pure
    if(!array_key_exists('_controller', $this->params))
    {
      throw new \Exception(sprintf('Bisogna specificare il parametro obbligatorio "_controller" per la route "%s"', $this->name));
    }

    // setting della default action per un modulo
    if(!array_key_exists('_action', $this->params))
    {
      $this->params['_action'] = 'execute';
    }

    $this->requirements = isset($parameters['requirements']) ? $parameters['requirements'] : array();
  }

  /**
   * Ritorna l'url (non compilata) della route
   *
   * @return string
   */
  public function getUrl()
  {
    return $this->url;
  }

  /**
   * Ritorna il nome della route
   *
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }

  /**
   * Ritorna il nome della action corrente
   *
   * Corrisponde al parametro della request "p"
   *
   * @return string
   */
  public function getPage()
  {
    return Core::parseControllerFileName($this->params['p']);
  }

  /**
   * Imposta il nome della action
   *
   * imposta il parametro "p" della request
   *
   * @param string $page
   * @return void
   */
  public function setPage($page)
  {
    $this->params['p'] = Core::parseControllerFileName($page);
  }

  public function getZone($default = null)
  {
    return $this->getParam('z', $default);
  }

  public function getOper($default = null)
  {
    return $this->getParam('o', $default);
  }

  /**
   * Ritorna il valore di un parametro della route
   *
   * @param $parameter
   * @param $default
   * @return mixed
   */
  public function getParam($parameter, $default = null)
  {
    return isset($this->params[$parameter]) ? $this->params[$parameter] : $default;
  }

  /**
   * Ritorna l'array dei parametri
   *
   * @return array
   */
  public function getAllParameters($parameters = array())
  {
    if(count($parameters) > 0)
    {
      return array_merge($this->params, $parameters);
    }

    return $this->params;
  }

  /**
   * Effattua il parsing della route
   *
   * vengono identificati i token della route i cui tipi sono:
   *   - text ("string")
   *   - variabile (":var")
   *   - star mode ("*")
   *
   * esempio:
   *   - url input: /cerca/:param/*
   * produce:
   *   - route tokens: {(cerca; text),(param; var),(,*)}
   *
   * lo star blocca il parsing della route. Quindi definire eventuali altri token dopo lo star
   * è da considerarsi un errore. Per l'applicazione ad ogni modo sarà trasparente
   *
   * @throws RoutingException L'url non inizia con lo slash (/)
   */
  private function parse()
  {
    if(strpos($this->url, '/') != 0)
    {
      throw new RoutingException(sprintf('La URL della route "%s" non inizia con lo "/"', $this->name));
    }

    preg_match_all('%([^/.]+)%i', $this->url, $tokens, PREG_PATTERN_ORDER);
    $tokens = $tokens[0];

    foreach($tokens as $token)
    {
      // variable
      if(strpos($token, ':') === 0)
      {
        $this->tokens[] = array('type' => 'variable',
                                'value' => substr($token, 1, strlen($token)));

        $this->params[substr($token, 1, strlen($token))] = '';
      }
      // star
      else if($token == '*')
      {
        $this->tokens[] = array('type' => 'star',
                                'value' => '');
        break;
      }
      // static string
      else
      {
        $this->tokens[] = array('type' => 'text',
                                'value'=> $token);
      }
    }

    if(strrpos($this->url, '/') == strlen($this->url) - 1)
    {
      $this->isDir = true;
    }
    // richiesta estensione
    // E
    // l'ultimo token non è uno star E nemmeno un token variabile il cui valore è "format"
    else if(Config::get('ROUTING/extension') &&
            $this->tokens[count($this->tokens) - 1]['value'] != 'format')
    {
      $this->tokens[] = array('type' => 'text', 'value' => self::$extension); // viene aggiunto un token di tipo stringa che rappresenta l'estensione
    }
  }

  /**
   * Continua la compilazione
   *
   * Eventuali classi ereditarie possono estendere il metodo per effettuare
   * ulteriori operazioni sull'oggetto Route
   *
   * La compilazione viene fatta da doMatch in realtà, questo metodo
   * si dovrebbe chiamare postCompile(), ma non essendo definito un metodo compile()
   * si è preferito fare così
   * 
   * precedenza Params->GET->POST (le variabili in post sovrascrivono quelle in GET che sovrascrivono quelle interne) 
   *
   */
  protected function compile()
  {
    $this->params = array_merge($this->params, $_GET, $_POST);

    $this->compiled =  true;
  }

  /**
   * Verfica che data una URL questa corrisponda alla route corrente
   *
   * @param $url
   * @return boolean
   */
  public function matchesUrl($url)
  {
    $urlParts = parse_url($url);

    $extension = $this->getExtension($urlParts['path']);
    $path = $urlParts['path'];

    if(Config::get('ROUTING/extension') && !$extension && !$this->isDir)
    {
      // in questo punto non posso lanciare tale eccezzione
      // significa solo che la regola non combacia con l'URL
      //throw new RoutingException(sprintf('Estensione mancante'));
      // quindi ritorniamo false

      //return false;
    }

    //Logger::debug('Route | matchesUrl | matching route "' . $this->name . '"');

    preg_match_all('%([^/.]+)%i', $path, $urlTokens, PREG_PATTERN_ORDER);
    $urlTokens = $urlTokens[0];

    $it1 = new \ArrayIterator($urlTokens);
    $it2 = new \ArrayIterator($this->tokens);

    if($matched = $this->doMatch($it1, $it2, false))
    {
      $this->compile();
    }

    return $matched;
  }

  /**
   * Effettua il match tra l'url della request e la route
   *
   * Funzione ricorsiva che effettua il match di due token alla volta
   *
   * @param ArrayIterator $it1 Url Token
   * @param ArrayIterator $it2 Route Token
   * @param $starMode
   * @return boolean
   */
  private function doMatch(\ArrayIterator $urlIt, \ArrayIterator $routeIt, $starMode)
  {
    if($urlIt->valid() && !$routeIt->valid() && !$starMode )
    {
      //Logger::debug("Route | Caso limite cond #1");
      return false;
    }

    if(!$urlIt->valid() && $routeIt->valid() )
    {
      $routeToken = $routeIt->current();

      // non esistono parametri * (star)
      if($routeToken['type'] == 'star')
      {
        // controllo estensione
        if(self::$extension)
        {
          // $this->doMatch($urlIt, $routeIt, $starMode)
          // non serve
          //return false;
        }

        return true;
      }
      //Logger::debug("Route | Caso limite cond #2 ");
      return false;
    }

    if(!$urlIt->valid() && !$routeIt->valid())
    {
      //Logger::debug("Route | Caso limite cond #3 ");
      return true;
    }

    $urlToken   = $urlIt->current();
    $routeToken = $routeIt->current();

    //Logger::debug("Route | Confronto tra <'$urlToken' | '{$routeToken['value']}'> {$urlIt->key()}|{$routeIt->key()}");

    switch($routeToken['type'])
    {
      case 'variable':
        if (!empty($urlToken) && preg_match('([^/]*)', $routeToken['value']))
        {
          if(!$this->validateParam($routeToken['value'], $urlToken))
          {
            return false;
          }

          $urlIt->next();
          $routeIt->next();

          $this->params[$routeToken['value']] = $urlToken;
          //Logger::debug("Route | Variable match!");
          return true && $this->doMatch($urlIt, $routeIt, false);
        }
        else
        {
          //Logger::debug('Route | Variable NOT match');
          return false;
        }
        break;

      case 'text':
        if($urlToken == $routeToken['value'])
        {
          $urlIt->next();
          $routeIt->next();

          //Logger::debug("Route | Text match!");
          return true && $this->doMatch($urlIt, $routeIt, false);
        }
        else
        {
          //Logger::debug('Route | Text NOT match');
          return false;
        }
        break;

      case 'star':
        //Logger::debug('Route | Star mode match!');

        return $this->parseStarParameters($urlIt, $routeIt);

        break;
    }
  }

  /**
   * Esegue le validazioni associate al parametro passato
   *
   * Nota: la validazione si riferisce solo ai token di tipo 'variabile'
   *
   * @param string $param
   * @param string $value
   * @return boolean
   */
  public function validateParam($param, $value)
  {
    if(array_key_exists($param, $this->requirements))
    {
      $requirements = $this->requirements[$param];

      if(is_array($requirements))
      {
        foreach($requirements as $validator)
        {

          if(!$validator->validate($value))
          {
            return false;
          }
        }
      }
      else
      {
        return $requirements->validate($value);
      }
    }

    return true;
  }

  /**
   * Esegue il parsing del parametro * (star)
   *
   * I parametri * (star) sono così definiti
   *
   *  /[param]/[value][/[param]/[value]]
   *  es:
   *  {parte iniziale url}/da/5000/stato/auto-usata/iva/deducibile[.html]
   *
   * Nel caso in cui le coppie di valori non siano corrette (siano dispari)
   * non viene più lanciata un'eccezione in quanto il fatto
   * che il numero di tokens star non sia corretto implica che la route
   * non matcha la richiesta corrente. Si demanda quindi al programmatore la responsabilità
   * di definire correttamente la priorità di analisi delle regole di routing
   *
   * @param Iterator $it      L'iteratore dei tokens dell'URL
   * @param Iterator $routeIt L'iteratore dei tokens della route
   */
  private function parseStarParameters(\ArrayIterator $it, \ArrayIterator $routeIt)
  {
    // non ci sono altri elementi da scorrere
    if(!$it->valid())
    {
      //Logger::info('Route | star mode fine. non ci sono altri token dell URL');

      return true;
    }

    $paramN = $it->current();

    $it->next();
    if($it->valid())
    {
      $valueN = $it->current();

      if(!$this->validateParam($paramN, $valueN))
      {
        return false;
      }

      $this->setParameter($paramN, $valueN);

      $it->next();
      return true && $this->parseStarParameters($it, $routeIt);
    }
    // prima di gridare "al lupo!" controlliamo che il parametro in più non sia l'estensione
    else
    {
      $it->seek($it->count() - 1);
      $routeIt->next();

      return $this->doMatch($it, $routeIt, false);
      // throw new RoutingException('Il numero dei parametri star non è esatto. Deve essere pari', RoutingException::STOP_ON_BC);
      //Logger::info('Route | Il numero dei parametri star non è esatto. Deve essere pari');

      return false;
    }
    return true;
  }

  /**
   * Genera l'URL corrispondente alla corrente regola di routing
   *
   * Inoltre si possono integrare ulteriori parametri per la creazione dell'url
   * a quelli già presenti
   *
   * @todo Il metodo non &egrave; in grado di gestire array di parametri
   * @todo La route è parsata ... quindi ciclare sui token. valutare se l'algoritmo risulta più veloce e in caso implementarlo
   *
   * Note: <code>(end($ary) == 'format' ? '' : '.')</code> quando viene stampata l'url finale è necessaria in quanto manca
   * la gestione dei separatori di token come tipo di token
   *
   * @param array $parameters
   * @return string L'url compilata
   */
  public function createUrl($parameters = array())
  {
    $parameters = array_merge($this->params, $parameters);

    $url = $this->url;

    $ary = array();

    if(empty($parameters['p']) && !empty($parameters['module']))
    {
      $parameters['p'] = 'index';
    }

    // variable token
    foreach($parameters as $parameter => $value)
    {
      if(is_array($value))
      {
        continue;
      }

      if(strstr($url,':' . $parameter))
      {
        $url = str_replace(':' . $parameter, $this->normaliezeUrlVariable($value), $url);
        unset($parameters[$parameter]);
        $ary[] = $parameter;
      }
    }

    // star token
    if(strpos($url, '*') !== false)
    {
      $url = str_replace('/*', '', $url);

      $skipParameters = array('module', 'p', 'query_string');

      foreach($parameters as $parameter => $value)
      {
        if(is_array($value))
        {
          continue;
        }

        if(in_array($parameter, $skipParameters)) continue;

        $url .= sprintf('/%s/%s', $parameter, $this->normaliezeUrlVariable($value));
      }
    }

    $qsa = '';
    if(isset($parameters['query_string']))
    {
      $qsa = '?' . $parameters['query_string'];
      unset($parameters['query_string']);
    }

    // @todo bisogna controllare token variabile non inizializzati
    
    return $url .
          ($this->isDir ? '' : (array_key_exists('format', $this->params) ? '' : (self::$extension ? '.' . self::$extension : '')) )  . 
          $qsa;
  }
  
  public final function createUrlAndGo($parameters = array(), $code = 301)
  {
    Routing::redirect($this->createUrl($parameters), $code);
  }

  /**
   * @todo recuperare codice per normalizzare stringhe per un url
   *
   * @param $string
   * @return unknown_type
   */
  public static function normaliezeUrlVariable(&$string)
  {
    return urlencode(strtolower(str_replace(' ', '-', $string)));
  }

  /**
   * Ritorna l'estensione di un file
   *
   * @param $url
   * @return string
   */
  public static function getExtension($url)
  {
    if(false !== $dotPosition = strrpos($url, '.'))
    {
      return substr($url, $dotPosition, strlen($url));
    }
    return '';
  }

  /**
   * Imposta i parametri della route corrente in base ad un array
   *
   * @param array $parameters
   * @return Route L'istanza corrente
   */
  public function setParameters($parameters)
  {
    if(is_array($parameters))
    {
      foreach($parameters as $param => $value)
      {
        $this->params[$param] = $value;
      }
    }

    return $this;
  }

  /**
   * Imposta il valore di un parametro della route corrente
   *
   * @param string $param
   * @param $value
   * @return Route
   */
  public function setParameter($param, $value)
  {
    $this->params[$param] = $value;

    return $this;
  }

  /**
   * Aggiunge un criterio di validazione per un parametro della route
   *
   * @param string $param
   * @param RouteValidator $validator
   */
  public function addRequirment($param, RouteValidator $validator)
  {
    if(isset($this->requirements[$param]))
    {
      if(is_array($this->requirements[$param]))
      {
        $this->requirements[$param][] = $validator;
      }
      else
      {
        // faccio un array e inserisco i validatori
        $old_validator = $this->requirements[$param];
        $this->requirements[$param] = Array( $old_validator ,$validator );
      }
    }
    else
    {
      $this->requirements[$param] = $validator;
    }
  }

  /**
   * Ritorna il valore di "cleanedValue" settato dal validatore o array vuoto
   *
   * @todo array vuoto?!?! ma perchè non un bel null value?
   *
   * @param string $value
   * @return mixed
   */
  public function getRequirment( $param )
  {
    if( is_object($requirment = $this->getFirstValidator($param)))
    {
      return $requirment->getCleanedValue();
    }
    return array();
  }

  /**
   * ritorna la lista dei requisiti della route
   *
   * @return array
   */
  public function getrequirements()
  {
    return $this->requirements;
  }

  /**
   * Ritorna true o false se esiste almeno un validatore settato per il parametro
   *
   * @param $param
   * @return boolean
   */
  function hasValidator($param)
  {
   if( isset($this->requirements[$param]) && is_array($this->requirements[$param]) )
   {
      if(count($this->requirements[$param])>0 )
      {
        return true;
      }
   }
   else if( isset($this->requirements[$param]) && is_object($this->requirements[$param] ) )
   {
      return true;
   }
   return false;
  }

  /**
   * Controlla l'esistenza di un validatore e ne ritorna il primo se c'è, altrimenti NULL
   *
   * @param $param
   * @return RouteValidator
   */
  function getFirstValidator($param)
  {
    if($this->hasValidator($param))
    {
      if(is_array($this->requirements[$param]))
      {
        $validatorKeys = array_keys($this->requirements[$param]);
        return $this->requirements[$param][$validatorKeys[0]];
      }
      else
      {
        return $this->requirements[$param];
      }
    }
    return null;
  }

  /**
   * Rimuove la validazione di un parametro
   *
   * @param string $param
   */
  public function removeRequirment($param)
  {
    if(isset($this->requirements[$param]))
    {
      unset($this->requirements[$param]);
    }
  }
  
  public function getParamsForCache(){}
  
  public function getAction()
  {
    return $this->getParam('_action');
  }
  
  public function getController()
  {
    return $this->getParam('_controller');
  }
}
