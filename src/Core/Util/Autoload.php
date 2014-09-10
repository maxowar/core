<?php

namespace Core\Util;

/**
 * Gestore per il caricamento automatico e manuale delle classi
 *
 * Adattamento della classe sfAutoload del framework Symfony
 *
 * @author Massimo Naccari
 * @package core
 * @subpackage util
 */
class Autoload
{
  static protected $instance;

  protected $classes = array();

  protected function __construct()
  {
  }

  /**
   * Retrieves the singleton instance of this class.
   *
   * @return Autoload A Autoload implementation instance.
   */
  static public function getInstance()
  {
    if (!isset(self::$instance))
    {
      self::$instance = new Autoload();
    }

    return self::$instance;
  }

  static public function initializeClasses()
  {
    $autoloadCacheFilename = Config::get('MAIN/cache_path') . DIRECTORY_SEPARATOR . 'autoload.php';
    if(file_exists($autoloadCacheFilename))
    {
      self::getInstance()->classes = include $autoloadCacheFilename;

      return;
    }

    require_once Config::get('MAIN/core_path') . '/lib/vendor/symfony/lib/util/sfFinder.class.php';

    $finder = sfFinder::type('file')->name('*.php')->follow_link();

    $dirs = array(Config::get('MAIN/core_path') . '/lib',
                  Config::get('application.dir') . '/controller',
                  Config::get('application.dir') . '/lib');

    // @todo: mergiare l'array delle directory con quelle definite dall'utente per il progetto

    $mapping = array();

    $directories_to_discard = explode(';', Config::get('AUTOLOAD/discard', ''));

    $finder->discard($directories_to_discard)->prune($directories_to_discard);

    foreach ($dirs as $path)
    {
      if ($matches = glob($path))
      {
        foreach ($finder->in($matches) as $file)
        {
          $mapping = array_merge($mapping, self::parseFile($path, $file));
        }
      }
    }

    self::getInstance()->classes = $mapping;

    //
    // creazione cache
    //

    $data = array();
    foreach ($mapping as $class => $file)
    {
      $data[] = sprintf("  '%s' => '%s',", $class, str_replace('\\', '\\\\', $file));
    }

    if(!file_exists(dirname($autoloadCacheFilename)))
    {
      mkdir(dirname($autoloadCacheFilename), 0755);
    }

    // compile data
    file_put_contents($autoloadCacheFilename, sprintf("<?php" . PHP_EOL . PHP_EOL .
                      "/**" . PHP_EOL .
                      " * auto-generated by Autoload" . PHP_EOL .
                      " * date: %s" . PHP_EOL .
                      " * @author Massimo Naccari " . PHP_EOL .
                      " */" . PHP_EOL . PHP_EOL .
                      "return array(\n%s\n);" . PHP_EOL ,
                      date('Y/m/d H:i:s'), implode("\n", $data)));
  }

  static public function parseFile($path, $file)
  {
    $mapping = array();
    
    // namespace
    $namespace = '';
    if(preg_match_all('/namespace (\w+(\\\\\w+)*);/', file_get_contents($file), $match))
    {
      $namespace = $match[1][0] . '\\';
    }

    preg_match_all('~^\s*(?:abstract\s+|final\s+)?(?:class|interface)\s+(\w+)~mi', file_get_contents($file), $classes);
    foreach ($classes[1] as $class)
    {
      $mapping[strtolower($namespace . $class)] = $file;
    }

    return $mapping;
  }

  /**
   * Effettua l'autoload di una classe
   *
   */
  public function autoloads($class)
  {
    // load the list of autoload classes
    if(!$this->classes)
    {
      self::initializeClasses();
    }

    return self::loadClass($class);
  }

  /**
   * Si occupa di registrare un handler per l'autoloading
   *
   * @return Autoload
   */
  static public function register()
  {
    ini_set('unserialize_callback_func', 'spl_autoload_call');

    if (false === spl_autoload_register(array(self::getInstance(), 'autoloads')))
    {
      throw new CoreException(sprintf('Unable to register %s::autoload as an autoloading method.', get_class(self::getInstance())));
    }

    return self::getInstance();
  }

  /**
   * Tries to load a class that has been specified in autoload.yml.
   *
   * @param  string  $class  A class name.
   *
   * @return boolean Returns true if the class has been loaded
   */
  public function loadClass($class)
  {
    $class = strtolower($class);

    // class already exists
    if (class_exists($class, false) || interface_exists($class, false))
    {
      return true;
    }

    // we have a class path, let's include it
    if (isset($this->classes[$class]))
    {
      require $this->classes[$class];

      return true;
    }

    return false;
  }
}