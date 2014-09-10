<?php

namespace Core\Exception;

use Core\Core;
use Core\Util\Config;
use Core\Routing\Routing;

/**
 * Exception handler dell'applicazione
 *
 * @author Massimo Naccari
 * @copyright Massimo Naccari
 * @package core
 * @subpackage exception
 */
class Exception extends \Exception
{
  protected
    $wrappedException = null;

  protected $httpCode = '500';

  public function __construct($message, $code = 0, Exception $previous = null)
  {
    parent::__construct($message, $code, $previous);

    //Logger::log(get_class($this) . ' | __construct() | File: ' . $this->file . '(' . $this->line . ') Messaggio: ' . $message, Logger::ERROR);
  }

  /**
   * Wrap di una eccezione
   *
   * @param Exception $e Exception instance
   *
   * @return CoreException
   */
  public static function createFromException(\Exception $e)
  {
    $exception = new static(sprintf('Wrapped %s: %s', get_class($e), $e->getMessage()));
    $exception->setWrappedException($e);

    return $exception;
  }

  /**
   * Imposta l'eccezione wrappata
   *
   * @param Exception $e An Exception instance
   */
  public function setWrappedException(\Exception $e)
  {
    $this->wrappedException = $e;
  }

  /**
   * Stampa lo stack trace per this
   */
  public function printStackTrace()
  {
    $exception = is_null($this->wrappedException) ? $this : $this->wrappedException;

    try
    {
      $this->outputStackTrace($exception);
    }
    catch (Exception $e)
    {
    }

  }

  /**
   * Costruisce l'output dello stack trace
   */
  protected function outputStackTrace(\Exception $exception)
  {
    while(ob_get_level() > 0)
    {
      // elimino l'output dell'applicazione
      ob_end_clean();
    }

    $this->sendHttpHeaders();

    // send an error 500 if not in debug mode
    if (!Config::get('application.debug'))
    {
      if ($template = $this->getTemplatePathForError(false))
      {
        include $template;
        return;
      }
    }

    $this->debugStackTrace($exception);
  }

  protected function sendHttpHeaders()
  {
    header('HTTP/1.0 500 Internal Server Error');
    header('Content-Type: text/html; charset=utf8');
  }

  static protected function debugStackTrace(\Exception $exception)
  {
    $message = is_null($exception->getMessage()) ? 'n/a' : $exception->getMessage();
    $name    = get_class($exception);
    $traces  = self::getTraces($exception, 0 == strncasecmp(PHP_SAPI, 'cli', 3) ? 'plain' : 'html');

    $route = Routing::getCurrentRequestRoute();

    if(Core::getCurrentInstance())
    {
    $dataController = Core::getCurrentInstance()->getDataController();
    if($dataController instanceof DataController)
    {
      $databaseTable = self::formatArrayAsHtml(array('connections' => array_keys($dataController::$connections)));
    }
    else
    {
      $databaseTable = self::formatArrayAsHtml(array('connections' => 'n/d'));
    }

    $configTable = self::formatArrayAsHtml(array(
    'paths' => array(
      'core' =>   Core::getCurrentInstance()->getConfiguration()->getCoreDir(),
      'root' =>   Core::getCurrentInstance()->getConfiguration()->getRootDir(),
      'controller' => Core::getCurrentInstance()->getConfiguration()->getControllersDir(),
      'template' => Core::getCurrentInstance()->getConfiguration()->getTemplatesDir()),
    'site' => Core::getCurrentInstance()->getConfiguration()->getSite(),
    'site_id' => Core::getCurrentInstance()->getConfiguration()->getSiteId(),
    'application' => Core::getCurrentInstance()->getConfiguration()->getApplicationName()

    ));

    $constantsTable  = self::formatArrayAsHtml(Config::getAll());
    }

    if(Core::getCurrentInstance() &&
       $session = Core::getCurrentInstance()->getSession())
    {
    $userTable    = self::formatArrayAsHtml(array('started' => $session->isStarted(),
                                                  'id' => $session->getId(),
                                                  'authenticated' => $session->isAuthenticated(),
                                                  'attributes' => $session->toArray()
      ));
    }
    $routingTable = self::formatArrayAsHtml(array('request' => 'HTTP 1.0 ' . strtoupper(Routing::getRequestMethod()) . ' ' . $_SERVER['REQUEST_URI'] ,
    																							'route' => array('name' => ($route instanceof Route) ? $route->getName() : 'n/d', 'uri' => ($route instanceof Route) ? $route->getUrl() : 'n/d'),
                                                  'parameters' => $route ? $route->getAllParameters() : 'n/d',
    																							'routes' => array_keys(Routing::getAll())
      ));
    //$logTable     = self::formatArrayAsHtml(array('path' => Logger::$path, 'level' => Logger::$level, 'messages' => Logger::getMessages()));
    $infoTable    = self::formatArrayAsHtml($_SERVER);

    if ($template = self::getTemplatePathForError(true))
    {
      include $template;
      return;
    }
  }

  /**
   * Ritorna il path ai template degli errori|eccezioni
   *
   * @param boolean $debug
   *
   * @return mixed
   */
  static protected function getTemplatePathForError($debug = false)
  {
    $template = sprintf('%s.html.php', $debug ? 'exception' : 'error');

    $path = is_readable(Config::get('application.dir') . '/lib/exception/data/' . $template) ?
            Config::get('application.dir') . '/lib/exception/data/' . $template :
            Config::get('MAIN/core_path') . '/Exception/data/' . $template;

    if (!is_null($path) && is_readable($path))
    {
      return $path;
    }

    return false;
  }

  /**
   * Ritorna un array del trace
   *
   * @param Exception $exception
   * @param string    $format    plain o html
   *
   * @return array An array of traces
   */
  static protected function getTraces($exception, $format = 'plain')
  {
    $traceData = $exception->getTrace();
    array_unshift($traceData, array(
      'function' => '',
      'file'     => $exception->getFile() != null ? $exception->getFile() : 'n/a',
      'line'     => $exception->getLine() != null ? $exception->getLine() : 'n/a',
      'args'     => array(),
    ));

    $traces = array();
    if ($format == 'html')
    {
      $lineFormat = 'at <strong>%s%s%s</strong>(%s)<br />in <em>%s</em> line %s <a href="#" onclick="toggle(\'%s\'); return false;">...</a><br /><ul id="%s" style="display: %s">%s</ul>';
    }
    else
    {
      $lineFormat = 'at %s%s%s(%s) in %s line %s';
    }

    for ($i = 0, $count = count($traceData); $i < $count; $i++)
    {
      $line = isset($traceData[$i]['line']) ? $traceData[$i]['line'] : 'n/a';
      $file = isset($traceData[$i]['file']) ? $traceData[$i]['file'] : 'n/a';
      $shortFile = preg_replace(array('#^'.preg_quote(Config::get('MAIN/core_path')).'#'), array('BASE_PATH'), $file);
      $args = isset($traceData[$i]['args']) ? $traceData[$i]['args'] : array();
      $traces[] = sprintf($lineFormat,
        (isset($traceData[$i]['class']) ? $traceData[$i]['class'] : ''),
        (isset($traceData[$i]['type']) ? $traceData[$i]['type'] : ''),
        $traceData[$i]['function'],
        self::formatArgs($args, false, $format),
        $shortFile,
        $line,
        'trace_'.$i,
        'trace_'.$i,
        $i == 0 ? 'block' : 'none',
        self::fileExcerpt($file, $line)
      );
    }

    return $traces;
  }

  /**
   * Formatta un array come stringa
   *
   * @param array   $args
   * @param boolean $single
   * @param string  $format html o plain
   *
   * @return string
   */
  static protected function formatArgs($args, $single = false, $format = 'html')
  {
    $result = array();

    $single and $args = array($args);

    foreach ($args as $key => $value)
    {
      if (is_object($value))
      {
        $formattedValue = ($format == 'html' ? '<em>object</em>' : 'object').sprintf("('%s')", get_class($value));
      }
      else if (is_array($value))
      {
        $formattedValue = ($format == 'html' ? '<em>array</em>' : 'array').sprintf("(%s)", self::formatArgs($value));
      }
      else if (is_string($value))
      {
        $formattedValue = ($format == 'html' ? sprintf("'%s'", self::escape($value)) : "'$value'");
      }
      else if (is_null($value))
      {
        $formattedValue = ($format == 'html' ? '<em>null</em>' : 'null');
      }
      else
      {
        $formattedValue = $value;
      }

      $result[] = is_int($key) ? $formattedValue : sprintf("'%s' => %s", self::escape($key), $formattedValue);
    }

    return implode(', ', $result);
  }

 /**
   * Ritorna un estratto del codice sorgente del file
   *
   * @param string $file file path
   * @param int    $line line number
   *
   * @return string HTML
   */
  static protected function fileExcerpt($file, $line)
  {
    if (is_readable($file))
    {
      $content = preg_split('#<br />#', highlight_file($file, true));

      $lines = array();
      for ($i = max($line - 3, 1), $max = min($line + 3, count($content)); $i <= $max; $i++)
      {
        $lines[] = '<li'.($i == $line ? ' class="selected"' : '').'>'.$content[$i - 1].'</li>';
      }

      return '<ol start="'.max($line - 3, 1).'">'.implode("\n", $lines).'</ol>';
    }
  }

  /**
   * Ritorna una versione leggibile di una variabile
   *
   * @param array $values
   *
   * @return string HTML
   */
  static protected function formatArrayAsHtml($values)
  {
    return '<pre>' . self::escape(print_r($values, true)) . '</pre>';
  }

  /**
   * Escapes di una stringa con html entities
   *
   * @param  string  $value
   *
   * @return string
   */
  static protected function escape($value)
  {
    if (!is_string($value))
    {
      return $value;
    }

    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
  }

  /**
   * Error handler
   *
   * Gestice i fatal error come eccezioni
   *
   * @param unknown_type $errno
   * @param unknown_type $errstr
   * @param unknown_type $errfile
   * @param unknown_type $errline
   */
  public function errorHandler($errno, $errstr, $errfile, $errline)
  {

    switch ($errno)
    {
    case E_USER_ERROR:
        echo "<b>My ERROR</b> [$errno] $errstr<br />\n";
        echo "  Fatal error on line $errline in file $errfile";
        echo ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
        echo "Aborting...<br />\n";
        exit(1);
        break;
    }

  }

}