<?php

/**
 * Generatore di codice PHP per le regole di routing
 *
 *
 * @author Massimo Naccari
 * @copyright Massimo Naccari
 * @package core
 * @subpackage routing
 *
 */
class Cache extends sfFileCache
{
  /**
   * (non-PHPdoc)
   * @see lib/routing/cache/sfFileCache#initialize($options)
   */
  public function initialize($options = array())
  {
    $options['cache_dir'] = Config::get('MAIN/cache_path', './cache');
    $options['automatic_cleaning_factor'] = 0;
    $options['lifetime'] = 0;
    $options['extension'] = '.php';

    parent::initialize($options);
  }

  /**
   * (non-PHPdoc)
   * @see lib/routing/cache/sfFileCache#set($key, $data, $lifetime)
   */
  public function set($key, $data, $lifetime = null)
  {
    $code = '<?php' . PHP_EOL .
            '/****************' . PHP_EOL .
            ' * ' . $key . '.php' . PHP_EOL .
            ' * file autogenerato il: ' . date(DATE_ISO8601) . PHP_EOL .
            ' * ' . PHP_EOL .
            ' * @author Massimo Naccari' . PHP_EOL .
            ' * @package cache' . PHP_EOL .
            ' **/' . PHP_EOL . PHP_EOL;

    $classesToInclude = array();
    foreach($data as $route)
    {
      $rc = new ReflectionClass($route);
      if(!in_array($rc->getName(), $classesToInclude))
      {
        $classesToInclude[] = $rc->getName();
        $code .= sprintf('include_once(\'%s\');', $rc->getFileName()) . "\n";
      }
    }

    $code .= "\n" .  '$routes = array(' . PHP_EOL;

    foreach ($data as $name => $route)
    {
    	$code .= "  new " . get_class($route) . "( '" . $route->getName() . "', " . PHP_EOL .
    	  "    array('params' => " . var_export($route->getAllParameters(), true) . ", " . PHP_EOL .
    	  "      'url' => '" . $route->getUrl() . "', "  . PHP_EOL;
    	  foreach((array) $route->getParamsForCache() as $param => $value)
    	  {
    	    $code .= "'$param' => " . var_export($value, true) ." ," . PHP_EOL;
    	  }
    	  $code .= "      'requirments' => array(";
    	foreach ($route->getRequirments() as $var => $validator)
    	{
    	  $code .= "'$var' => new " . get_class($validator) . "( " . var_export($validator->getParameters(), true) . ") ," . PHP_EOL;
    	}

    	$code .= ") ) )," . PHP_EOL;
    }

    $code .= ');' . PHP_EOL;

    parent::set($key, $code, $lifetime);
  }

  /**
   * (non-PHPdoc)
   * @see lib/routing/cache/sfFileCache#get($key, $default)
   */
  public function get($key, $default = null)
  {
    include_once($this->getFilePath($key));

    return $routes;
  }

  /**
   * (non-PHPdoc)
   * @see routing/cache/sfFileCache#write($path, $data, $timeout)
   */
  protected function write($path, $data, $timeout)
  {
    $current_umask = umask();
    umask(0000);

    if (!is_dir(dirname($path)))
    {
      // create directory structure if needed
      mkdir(dirname($path), 0777, true);
    }

    $tmpFile = tempnam(dirname($path), basename($path));

    if (!$fp = @fopen($tmpFile, 'wb'))
    {
       throw new CoreException(sprintf('Unable to write cache file "%s".', $tmpFile));
    }

    @fwrite($fp, $data);
    @fclose($fp);

    if (!@rename($tmpFile, $path))
    {
      copy($tmpFile, $path);
      unlink($tmpFile);
    }

    chmod($path, 0666);
    umask($current_umask);

    return true;
  }

  /**
   * (non-PHPdoc)
   * @see routing/cache/sfFileCache#read($path, $type)
   */
  protected function read($path, $type = self::READ_DATA)
  {
    throw new RoutingException('Metodo non disponibile');
  }

  protected function isValid($path)
  {
    return true;
  }
}