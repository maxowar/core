<?php

namespace Core\PHP;

/**
 * Storage della sessione attravero il sistema nativo di PHP
 *
 * @author Massimo Naccari <massimo.naccari@wdmn.it>
 * @copyright Massimo Naccari
 * @package core
 * @subpackage session
 */
class PHP extends Session
{
  /**
   * Tempo di sopravvivenza della sessione utente
   *
   * @var integer
   */
  protected $ttl;

  public function initialize(array $params)
  {
    session_name($params['name']);
    session_set_cookie_params(0, $params['path'], $params['domain']);

    if(!session_start())
    {
      Logger::log('PhpSession | initialize | Impossibile inizializzare la sessione', Logger::WARNING);
    }
    else
    {
      $this->is_started = true;
      $this->id = session_id();
      Logger::log('PhpSession | initialize | Sessione "' . $params['name'] . '" inizializzata correttamente. id: ' . $this->id, Logger::NOTICE);

      $this->ttl = $params['ttl'];
      
      $this->setAuthenticated($this->checkAuthentication());
    }
  }

  /**
   * Controlla che l'utente sia autenticato in questo istante
   *
   * Il mantenimento della sessione si basa sul controllo dell'expiration time attraverso
   * il parametro ttl, il controllo dell'ip del client e il controllo dello user agent
   *
   * @return boolean
   */
  public function checkAuthentication()
  {
    // flag loggato
    if(($loggedin_flag = $this->getAttribute('loggedin', false, 'user')) === false)
    {
      return false;
    }

    // timestamp dell'ultima richiesta HTTP
    $time_survived = ($this->getAttribute('lastrequest', 0, 'user') + $this->ttl)  > time();

    //utente loggato
    if($loggedin_flag && $time_survived)
    {
      // l'indirizzo ip è rimasto invariato
      $remote_address = $_SERVER['REMOTE_ADDR'];
      if($remote_address != $this->getAttribute('remoteaddress', null, 'user'))
      {
        throw new SessionException('Remote address cambiato. Possibile hacking.');
      }

      // l'identificativo dello user agent è invariato (session hijacking)
      $user_agent = $_SERVER['HTTP_USER_AGENT'];
      if($user_agent != $this->getAttribute('useragent', null, 'user'))
      {
        throw new SessionException('User agent cambiato. Possibile hacking.');
      }

      return true;
    }
    else
    {
      Config::get('LOG/debug') && Logger::debug(sprintf('PhpSession | isAuthenticated | sessione scaduta'));

      if(!$this->destroyed)
      {
        $this->destroy();
      }

      return false;
    }
  }

	/**
	 * Recupera una variabile di sessione dallo storage
	 *
   * @param string $name
   * @param mixed $default
   * @param string $domain
   */
  public function getAttribute($name, $default = null, $domain = '')
  {
    if($domain)
    {
      return isset($_SESSION[$domain][$name]) ? $_SESSION[$domain][$name] : $default;
    }
    else
    {
      return isset($_SESSION[$name]) ? $_SESSION[$name] : $default;
    }
  }

	/**
	 * Controlla l'esistenza di una variabile di sessione nello storage
	 *
   * @param string $name
   * @param string $domain
   */
  public function hasAttribute($name, $domain = '')
  {
    if($domain)
    {
      return array_key_exists($name, isset($_SESSION[$domain]) ? $_SESSION[$domain] : array());
    }
    else
    {
      return array_key_exists($name, $_SESSION);
    }
  }

	/**
	 * Salva una variabile di sessione nello storage
	 *
   * @param string $name
   * @param mixed $value
   * @param string $domain
   */
  public function setAttribute($name, $value, $domain = '')
  {
    if($domain)
    {
      $_SESSION[$domain][$name] = $value;
    }
    else
    {
      $_SESSION[$name] = $value;
    }
  }

  /**
   * Elimina un'entry dallo storage
   *
   * @param string $name
   * @param string $domain
   */
  public function removeAttribute($name, $domain = '')
  {
    if($domain)
    {
      unset($_SESSION[$domain][$name]);
    }
    else
    {
      unset($_SESSION[$name]);
    }
  }

  /**
   * Ritorna i nomi di tutte le variabile di sessione salvate
   *
   * @param string $domain
   */
  public function getNames($domain = '')
  {
    if($domain)
    {
      return array_keys(isset($_SESSION[$domain]) ? $_SESSION[$domain] : array());
    }
    else
    {
      return array_keys($_SESSION);
    }
  }

  /**
   * {@see Session::regenerate()}
   */
  public function regenerate()
  {
    session_regenerate_id(true);
  }

  public function doDestroy()
  {
    session_destroy();
  }

  /**
   * {@see Session::toArray()}
   */
  public function toArray()
  {
    return $_SESSION;
  }

  /**
   * Ritorna l'ip dell'host remoto
   *
   * @return string
   */
  public function getRemoteAddress()
  {
    return $this->getAttribute('remoteaddress', $_SERVER['HTTP_USER_AGENT'], 'user');
  }

}