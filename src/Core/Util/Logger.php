<?php

namespace Core\Util;

/**
 * Semplice gestore di un log per l'applicazione
 *
 * @author Massimo Naccari
 * @package core
 * @subpackage utility
 */
class Logger
{
  /** 
   * Syslog Message Facilities
   * http://tools.ietf.org/html/rfc5424
  0       Emergency: system is unusable
  1       Alert: action must be taken immediately
  2       Critical: critical conditions
  3       Error: error conditions
  4       Warning: warning conditions
  5       Notice: normal but significant condition
  6       Informational: informational messages
  7       Debug: debug-level messages
  */
  
  const EMERGENCY = 0;
  const ALERT     = 1;
  const CRITICAL  = 2;
  const ERROR     = 3;
  const WARNING   = 4;
  const NOTICE    = 5;
  const INFO      = 6;
  const DEBUG     = 7;
  
  public static $level = 0;

  public static $type = 3;

  public static $path = '';

  public static $email = '';

  private static $messages = array();

  public static function setLevel($level)
  {
    self::$level = $level;
  }

  public static function setType($type)
  {
    self::$type = $type;
  }

  public static function setPath($path)
  {
    self::$path = $path;

    if(!file_exists(dirname($path)))
    {
      mkdir(dirname($path), 0755);
    }
  }

  /**
   *
   * Opzioni disponibili:
   *   filter_level
   *   type
   *   log_dir
   *   late_write
   *
   * @param array $options
   */
  public static function setOptions($options = array())
  {
    self::$level     = isset($options['filter_level']) ? $options['filter_level'] : self::$level;
    self::$type      = isset($options['type'])         ? $options['type']         : self::$type;
    self::setPath(isset($options['log_dir'])      ? $options['log_dir']      : self::$path);
  }

  /**
   * Scrive un log
   *
   * @param $msg
   * @param $level
   * @return unknown_type
   */
  public static function log($msg, $level = self::NOTICE)
  {
    if ($level > self::$level)
    {
      return;
    }

    self::$messages[] = date("H:i:s")." | ".$level." | " . (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1') . " | ". preg_replace('/[\n\r\t]+|(  )( )*/', ' ', $msg)."\n";
  }

  /**
   * Salva il buffer su file
   */
  public static function write()
  {
    // buffer vuoto
    if(count(self::$messages) == 0)
    {
      return;
    }
    
    $destination = self::$path . '.' . date("Y-m-d") . '.log';

    $fp = fopen($destination, 'a+');
    $data = '';
    foreach(self::$messages as $message)
    {
      $data .= $message;
    }
    fwrite($fp, $data);
    fclose($fp);
  }

  public function __destruct()
  {
    self::write();
  }

  /**
   * Ritorna il contenuto del file di log
   *
   * @return string
   */
  public static function getFileContent()
  {
    return file_get_contents(self::$path . '.' . date("Ymd") . '.log');
  }

  public static function getMessages()
  {
    return self::$messages;
  }

  /**
   * Log livello debug
   *
   * @param $message
   * @return unknown_type
   */
  public static function debug($message)
  {
    self::log($message, self::DEBUG);
  }

  /**
   * log livello info
   *
   * @param $message
   * @return unknown_type
   */
  public static function info($message)
  {
    self::log($message, self::INFO);
  }

  /**
   * Scrive nel log il dump di una varibile
   *
   * @param $var
   * @param $message
   * @return unknown_type
   */
  public static function dump($var, $message = '')
  {
    ob_start();
    var_dump($var);
    $dump = ob_get_clean();

    self::log("var_dump($message)" . "\n" . $dump, self::DEBUG);
  }

  /**
   * invia un messaggio con l'attuale memoria usata e il picco massimo
   *
   * vengono usate le funzioni del core memory_get_usage  e memory_get_peak_usage
   *
   * @param boolean $realUsage
   * @return unknown_type
   */
  public static function memory($message = '', $realUsage = false)
  {
    self::debug('Logger | memory | '. $message .' Actual' . ($realUsage ? ' REAL' : '') . ' memory usage: ' . number_format(memory_get_usage($realUsage)) . 'Bytes - Memory Peak: ' . number_format(memory_get_peak_usage($realUsage)) . ' Bytes');
  }

  public static function shutdown()
  {
    self::debug('Logger | shutdown | Salvo i messaggi dell\'istanza');

    self::write();
  }
}