<?php

namespace Core\Configuration;

use Core\Util\Config;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Debug\ExceptionHandler as Exeption;

/**
 * Amministra l'inizializzazione delle configurazioni dell'intero progetto
 *
 * @author Massimo Naccari <massimo.naccari@wdmn.it>
 * @copyright Massimo Naccari
 * @package core
 * @subpackage config
 *
 */
abstract class Project
{
  /**
   * il nome dell'applicazione
   *
   * @var string
   */
  protected $application;

  /**
   * Indica se si sta eseguendo un'action da SAPI CLI
   *
   * @var boolean
   */
  protected $cliExecution;

  /**
   * è il path dov'è installato il progetto
   *
   * @var string
   */
  protected $rootDir;

    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcher
     */
    protected $eventDispatcher;

    protected $environment;

  /**
   * Costruttore astratto
   *
   * Si occupa di inizializzare tutte le configurazioni del sistema
   */
  public function __construct($environment)
  {
      $this->environment = $environment;

    $this->application  = $this->getApplicationName();
    $this->cliExecution = false;
      $this->rootDir = $this->guessRootDir();

    $this->loadConstants();

    $this->eventDispatcher = new EventDispatcher();

    Exeption::register();

    //$this->loadLogger();

    //Logger::info(sprintf('ProjectConfiguration | __construct | inizializzazione applicazione "%s"', $application));

    //$this->loadPhpConfiguration();
  }

    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }

  /**
   * Inizializza il sitema di logging
   */
  private function loadLogger()
  {
    // inizializza logger
    Logger::setOptions(array('filter_level' => Config::get('LOG/level'),
                             'log_dir'      => Config::get('LOG/dir', Config::get('application.dir') . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . $this->application),
                             'late_write'   => Config::get('LOG/late_write', true)
    ));
  }

  /**
   * Carica le costanti di sistema
   *
   */
  private function loadConstants()
  {
    // config.ini globale
    $configurationFileName = (!empty($_SERVER['DOCUMENT_ROOT']) ? dirname($_SERVER['DOCUMENT_ROOT']) : getcwd()) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.ini';
    if(!file_exists($configurationFileName))
    {
      throw new \Exception(sprintf('File di configurazione "%s" non trovato', $configurationFileName));
    }
    Config::add(parse_ini_file($configurationFileName, true));
    
    Config::set('application.dir', $this->getRootDir());
    
    if(!Config::get('MAIN/document_root'))
    {
      Config::set('MAIN/document_root', $this->getRootDir() . DIRECTORY_SEPARATOR . 'public');
    }
    
    if(!Config::get('MAIN/cache_path'))
    {
      Config::set('MAIN/cache_path', $this->getRootDir() . DIRECTORY_SEPARATOR . 'cache');
    }

    $coreDir = $this->getCoreDir();
  }

  /**
   * load runtime php configurations
   *
   * Carica le direttive php runtime definite dall'utente ed imposta
   * l'error handler
   *
   */
  private function loadPhpConfiguration()
  {
    // direttive php
    require Config::get('MAIN/core_path') . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'baseCache.class.php';
    $cache = new BaseCache(array('cache_dir' => 'ini', 'extension' => '.cache'));

    if(!$cache->has('php.ini'))
    {
      $options = array();
      if(file_exists(Config::get('application.dir') . DIRECTORY_SEPARATOR . 'ini' . DIRECTORY_SEPARATOR . 'php.ini'))
      {
        $options = parse_ini_file(Config::get('application.dir') . DIRECTORY_SEPARATOR . 'ini' . DIRECTORY_SEPARATOR . 'php.ini', true);
      }
      else
      {
        $options = parse_ini_file(Config::get('MAIN/core_path') . DIRECTORY_SEPARATOR . 'ini' . DIRECTORY_SEPARATOR . 'php.ini', true);
      }

      $cache->set('php.ini', serialize($options));
    }
    else
    {
      $options = unserialize($cache->get('php.ini', array()));
    }

    $options = Config::get('application.debug') ? (isset($options['dev']) ? $options['dev'] : array()) : (isset($options['prod']) ? $options['prod'] : array());

    foreach($options as $varname => $varvalue)
    {
      ini_set($varname, $varvalue);
    }

    // php version
    if(Config::has('phpversion') && Config::get('phpversion') > phpversion())
    {
      throw new CoreException(sprintf('Per eseguire l\'applicazione è richiesto PHP %s, installato PHP %s', Config::get('phpversion'), phpversion()));
    }

    // error handler
    //set_error_handler(array('CoreException', 'errorHandler'));
  }

  /**
   * ritorna il nome dell'applicazione corrente definito nel front-controller
   *
   * <code><?php $configuration = ProjectConfiguration::getApplicationConfiguration('backend'); ?></code>
   *
   * @return string
   */
  abstract public function getApplicationName();

  /**
   * Indovina la root-dir del progetto considerando l'installazione del Core framework allìinterno
   * del progetto stesso
   *
   * <ul>
   * 	 <li>pathToRootDir/
   *   		<ul>
   *   			<li>core/</li>
   *   		</ul>
   *   </li>
   * </ul>
   *
   * @return string The project root directory
   */
  public function guessRootDir()
  {
    $r = new \ReflectionClass(get_class($this));
    return realpath(dirname($r->getFileName()).'/../../..');
  }

  static public function guessCoreDir()
  {
    $r = new \ReflectionClass('Core\Configuration\Project');

    return realpath(dirname($r->getFileName()).'/..');
  }

  /**
   * Ritorna la stringa che rappresenta il path al framework Core
   *
   * @return string
   */
  public function getCoreDir()
  {
    if(!Config::has('MAIN/core_path'))
    {
      Config::set('MAIN/core_path', $this->guessCoreDir());
    }

    return Config::get('MAIN/core_path');
  }

  /**
   * Ritorna il path alla radice del progetto
   *
   * @return string
   */
  public function getRootDir()
  {
    return $this->rootDir;
  }

  /**
   * Indica all'applicazione che dev'essere eseguita da riga di comando
   *
   * @return ProjectConfiguration
   */
  public function setCli()
  {
    $this->cliExecution = true;
    return $this;
  }

  /**
   * Controlla se l'esecuzione è da riga da comando
   *
   */
  public function isCli()
  {
    return $this->cliExecution && php_sapi_name() == 'cli';
  }

  /**
   * Recupera il gestore delle configurazioni specifiche per l'applicazione
   *
   * @param string $application
   * @param string $rootDir     Per i progetti che includono all'interno della propria struttura directory il Core questo parametro può essere null
   * @return ApplicationConfiguration
   */
  public static function getApplicationConfiguration($application, $rootDir = null)
  {
    $class = ucfirst($application) . 'Configuration';

    if (null === $rootDir)
    {
      $rootDir = self::guessRootDir();
    }

    // se non c'è una classe di configurazione specifica carica quella di default
    if (!file_exists($file = $rootDir . '/controller/' . $application . '/' . $class . '.class.php'))
    {
      $class = 'Core\Configuration\Application';
    }

    return new $class($application, $rootDir);
  }

}
