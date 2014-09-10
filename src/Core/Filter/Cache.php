<?php

namespace Core\Filter;

/**
 *
 *
 * @author Massimo Naccari
 * @copyright Massimo Naccari
 * @package core
 * @subpackage filter
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
    $options['extension'] = '.php';

    parent::initialize($options);
  }

  /**
   * (non-PHPdoc)
   * @see lib/routing/cache/sfFileCache#set($key, $data, $lifetime)
   */
  public function set($key, $data, $lifetime = null)
  {
    $code = '<?php' . "\n" .
            '/****************' . "\n" .
            ' * ' . $key . '.php' . "\n" .
            ' * Questo file viene caricato dentro FileManager::loadConfiguration()' . "\n" .
            ' *' . "\n" .
            ' * file autogenerato il: ' . date(DATE_ISO8601) . "\n" .
            ' **/' . "\n\n";

    $classesToInclude = array();
    foreach($data as $filterClassName => $active)
    {
      if($active && !in_array($filterClassName, $classesToInclude))
      {
        $classesToInclude[] = $filterClassName;
      }
    }

    $code .= sprintf('include_once(\'%s/lib/filter/%s.class.php\');', Config::get('MAIN/core_path'), 'filterAbstract') . "\n";

    foreach($classesToInclude as $filterClassName)
    {
      $code .= sprintf('include_once(\'%s/lib/filter/%s.class.php\');', Config::get('application.dir'), $filterClassName) . "\n";
    }

    $code .= "\n" .  '$filter = ' .var_export($classesToInclude, true) . ";\n\n";

    $code .= 'foreach($filter as $pos => $filterClassName)' . "\n" .
             '{' . "\n" .
             '  $this->register(new $filterClassName);' . "\n" .
             '}' . "\n\n";

    parent::set($key, $code, $lifetime);
  }

  /**
   * (non-PHPdoc)
   * @see lib/routing/cache/sfFileCache#get($key, $default)
   */
  public function get($key, $default = null)
  {
    return $default;
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
    if (!$fp = @fopen($path, 'rb'))
    {
      throw new Exception(sprintf('Unable to read cache file "%s".', $path));
    }

    @flock($fp, LOCK_SH);

    $length = ftell($fp);
    $data = @fread($fp, $length);

    @flock($fp, LOCK_UN);
    @fclose($fp);

    return $data;
  }
}