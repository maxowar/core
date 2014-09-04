<?php

namespace Core\Cache;

/**
 * Cache delle view
 *
 *
 * @author Massimo Naccari <massimo.naccari@wdmn.it>
 * @copyright Massimo Naccari
 * @package core
 * @subpackage view
 */
class CacheView
{
  private $enabled = false;
  
  private $options = array();
  
  private $driver;

  public function __construct(array $options)
  {
    if(!isset($options['driver']))
    {
      throw new CoreException('Opzione driver mancante');
    }
    
    $this->driver = new $options['driver']($options['params']);
    
    if(!($this->driver instanceof sfCache))
    {
      throw new RuntimeException(sprintf('Oggetto non di tipo sfCache ma "%s"', get_class($this->driver)));
    }
    
    unset($options['driver'], $options['params']);
    
    $this->options = array_merge(array('lifetime' => 3600), $options); // imposta default ttl a 1h
  }

  /**
   * sovrascrive l'attuale driver
   * 
   * @return CacheView
   */
  public function setDriver(sfCache $driver)
  {
    $this->driver = $driver;
    
    return $this;
  }
  
  /**
   * passa tutte le chiamate a funzioni non definite all'oogetto driver
   */
  public function __call($method, $parameters)
  {
    if(is_callable(array($this->driver, $method)))
    {
      return call_user_func_array(array($this->driver, $method), $parameters);
    }
    throw new RuntimeException("Funzione CacheView::$method() inesistente");
  }
  
  public function set($key, $data, $lifetime = null)
  {
    if(!$lifetime)
    {
      $lifetime = $this->getLifetime();
    }
    return $this->driver->set($key, $data, $lifetime);
  }
  
  /**
   * verifica che la route corrente sia cacheabile
   *
   * @return boolean True se cache attivata e non si trata di HTTP POST
   */
  public function isCacheable()
  {
    return $this->enabled &&
           Routing::getRequestMethod() == Routing::GET;
  }
  
  /**
   * attiva la cache dell'output
   *
   * @return CacheView
   */
  public function enable()
  {
    $this->enabled = true;
    
    // trigger cache enabled event
    Core::getCurrentInstance()->getEventDispatcher()->notify(new Event($this, 'view.cache_enabled'));
    
    return $this;
  }
  
  public function disable()
  {
    $this->enabled = false;
    return $this;
  }
  
  public function getLifetime()
  {
    return $this->options['lifetime'];
  }

  /**
   * imposta il ttl
   *
   * @param integer $seconds
   * @return CacheView
   */
  public function lifetime($seconds)
  {
    $this->options['lifetime'] = $seconds;
    return $this;
  }

}
