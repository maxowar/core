<?php

namespace Core\Util;

/**
 * Classe per gestire le variabili globali dell'applicazione
 *
 * @author Massimo Naccari
 * @copyright Massimo Naccari
 * @package core
 * @subpackage config
 *
 */
class Config implements \ArrayAccess
{
  public static $parameters = array();

  /**
   * Aggiunge una lista di opzioni
   *
   * @param array $parameters
   */
  public static function add(array $parameters)
  {
    self::$parameters = array_merge(self::$parameters, $parameters);
  }

  /**
   * Imposta il valore di un parametro
   *
   * Per gestire il vecchio metodo di accesso alle costanti di sistema
   * è stato inserito il seguente codice:
   * {@source 3 5}
   * che permette di richiamare la costante attraverso il seguente codice:
   * <code>
   * Config::get('MAIN/base_path');
   * </code>
   *
   * @param string $name
   * @param mixed $value
   */
  public static function set($name, $value)
  {
    if(strstr($name, '/') !== false)
    {
      $namespace = explode('/', $name);
      self::$parameters[$namespace[0]][$namespace[1]] = $value;
    }

    self::$parameters[$name] = $value;
  }

  /**
   * Ritorna il valore di un parametro
   *
   * Nota: namespace definibile limitato a un solo livello
   *
   * @param string $name
   * @param mixed $default
   * @return mixed
   */
  public static function get($name, $default = null)
  {
    // definito un namespace per il parametro
    if(strstr($name, '/') !== false)
    {
      $namespace = explode('/', $name);
      return isset(self::$parameters[$namespace[0]][$namespace[1]]) ? self::$parameters[$namespace[0]][$namespace[1]] : $default;
    }

    return array_key_exists($name, self::$parameters) ? self::$parameters[$name] : $default;
  }

  /**
   * Controllo se esiste un parametro
   *
   * @param $name
   * @return boolean
   */
  public static function has($name)
  {
    // definito un namespace per il parametro
    if(strstr($name, '/') !== false)
    {
      $namespace = explode('/', $name);
      return isset(self::$parameters[$namespace[0]][$namespace[1]]);
    }

    return array_key_exists($name, self::$parameters);
  }

  /**
   * ritorna l'array di tutte le impostazioni
   *
   * @return array
   */
  public static function getAll()
  {
    return self::$parameters;
  }

  /**
   * Elimina tutte le opzioni
   *
   */
  public static function clean()
  {
    self::$parameters = array();
  }

  //
  // Implementazione interfaccia ArrayAccess
  //

  /**
   * @param unknown_type $offset
   */
  public function offsetExists($offset)
  {

  }

  /**
   * @param unknown_type $offset
   */
  public function offsetGet($offset)
  {

  }

  /**
   * @param unknown_type $offset
   * @param unknown_type $value
   */
  public function offsetSet($offset, $value)
  {

  }

  /**
   * @param unknown_type $offset
   */
  public function offsetUnset($offset)
  {

  }


}