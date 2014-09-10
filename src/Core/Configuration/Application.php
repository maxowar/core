<?php

namespace Core\Configuration;

use Core\Routing\Routing;
use Core\Util\Config;

/**
 * Gestore della configurazione di un'istanza dell'applicazione
 *
 * Nel framework un'applicazione rappresenta un sito, quindi un dominio
 *
 * @author Massimo Naccari <massimo.naccari@wdmn.it>
 * @copyright Massimo Naccari
 * @package core
 * @subpackage config
 */
class Application extends Project
{
  const PATH_CONTROLLER = 0;
  const PATH_VIEW       = 1;
  const PATH_CONFIG     = 2;
  const PATH_LIB        = 3;
  const PATH_CACHE      = 4;


  /**
   * contiene i path per il sito corrente
   *
   * array := [PATH_CONTROLLER
   *           PATH_VIEW
   *           PATH_CONFIG]
   *
   * @var array
   */
  private $paths;

  /**
   *
   * @var array
   */
  public $contentIni;

  /**
   *
   * @var string Nome del sito corrente
   */
  private $site;

  /**
   *
   * @var integer Id del sito corrente
   */
  private $siteId;

  private $debug;

  public function __construct($environment)
  {
    parent::__construct($environment);

    $this->debug = false;

    $this->configure();

    $this->initConfiguration();

    $this->initialize();
  }

    public function getApplicationName()
    {
        return get_class($this);
    }

  private function initConfiguration()
  {
    if(Config::get('application.disabled', false) &&
       php_sapi_name() != 'cli')
    {
      throw new \Exception(Config::get('application.maintenance_end_time'));
    }

    // gestione directory (domini) applicazione (solo per request HTTP)
    if(php_sapi_name() != 'cli')
    {
      $registeredSites = Config::get('DOMAINS', array());

      if(!array_key_exists($_SERVER['SERVER_NAME'], $registeredSites))
      {
        throw new \Exception(sprintf('Il dominio "%s" non è registrato nell\'applicazione.', $_SERVER['SERVER_NAME']));
      }
      $this->site   = substr($_SERVER['SERVER_NAME'], 0, strpos($_SERVER['SERVER_NAME'], '.'));
      $this->siteId = $registeredSites[$_SERVER['SERVER_NAME']];

      Config::set('MAIN/base_url', 'http://' . $_SERVER['SERVER_NAME']) . (isset($_SERVER['REMOTE_PORT']) && $_SERVER['REMOTE_PORT'] != 80 ? ':' . $_SERVER['REMOTE_PORT'] : ''); // @todo gestire https
    }

    // inizializza path di default dell'applicazione
    $this->paths = array(
      array(
        Config::get('application.dir') . '/apps/' . ucfirst($this->getApplicationName()) . '/Controller'
      ),
      array(
          Config::get('application.dir') . '/apps/' . ucfirst($this->getApplicationName()) . '/View'
      ),
      array(
          Config::get('application.dir') . '/config'
      )
    );

    $this->debug = Config::get('application.debug');
  }

  /**
   * Fate l'override questo metodo per modificare la configurazione dell'applicazione
   * corrente
   *
   * Empty function
   */
  public function configure()
  {
    //
  }

  /**
   * Fate l'override questo metodo per modificare l'inizializzazione dell'applicazione
   * corrente
   *
   * Empty function
   */
  public function initialize()
  {
    //
  }

  public function getControllersDir()
  {
    return (array) $this->paths[self::PATH_CONTROLLER];
  }

  public function getTemplatesDir()
  {
    return (array) $this->paths[self::PATH_VIEW];
  }

  public function getCacheDir()
  {
    // @todo queste funzioni non dovrebbero dipendere dalla costante
    return Config::get('MAIN/cache_path');
  }
  
  public function getDocRootDir()
  {
    // @todo queste funzioni non dovrebbero dipendere dalla costante
    return Config::get('MAIN/document_root');
  }

  public function getLanguage()
  {

  }

  /**
   *
   *
   * @param $type
   * @return string
   */
  public function getApplicationPath($type)
  {
    return $this->paths[$type];
  }

  /**
   * Imposta il valore di un path
   *
   * @param string $path
   * @param integer $type
   * @return void
   */
  public function setApplicationPath($path, $type)
  {
    $this->paths[$type] = $path;
  }

  /**
   * ritorna il nome della directory corrente
   *
   * @return string
   */
  public function getSite()
  {
    return $this->site;
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
    return intval($this->siteId);
  }

  public function setApplicationName($name)
  {
    $this->application = $name;
  }

  public function isDebug()
  {
    return $this->debug;
  }

}