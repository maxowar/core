<?php

namespace Core\Session;

/**
 * Interfaccia per la gestione della sessione di navigazione e
 * allo stesso tempo session storage fisico
 *
 * Gestisce la sessione di navigazione del client e si occupa dello storage della stessa
 *
 * La definizione dello storage &egrave; demandato alle implementazioni
 *
 * @author Massimo Naccari <massimo.naccari@wdmn.it>
 * @copyright Massimo Naccari
 * @package core
 * @subpackage session
 */
abstract class Session
{
  /**
   * L'istanza dell'utente corrente
   *
   * @var User
   */
  protected $user;
  
  protected $is_started;
  
  protected $id;

  /**
   *
   * @var boolean
   */
  private $authenticated;

  protected $destroyed;

  /**
   * Inizializza l'oggetto Session
   *
   * @param array $params Opzioni di configurazione passate all'implementazione dell'interfaccia che verr&agrave; iniziazializzata
   */
  public function __construct($params)
  {
    $this->authenticated = false;
    $this->destroyed     = false;
    $this->is_started    = false;
    
    $this->initialize($params);

    // gestione variabili flash
    foreach ($names = $this->getNames('core/user/flash') as $name)
    {
      $this->setFlash($name, $this->getFlash($name), false);
    }
  }

  /**
   * Inizializza la sessione
   *
   * @param array $params
   */
  abstract public function initialize(array $params);

  /**
   * Sets a flash variable that will be passed to the very next action.
   *
   * @param  string $name     The name of the flash variable
   * @param  string $value    The value of the flash variable
   */
  public function setFlash($name, $value, $persist = true)
  {
    $this->setAttribute($name, $value, 'core/user/flash');

    if($persist)
    {
      $this->removeAttribute($name, 'core/user/flash/remove');
    }
    else
    {
      $this->setAttribute($name, true, 'core/user/flash/remove');
    }
  }

  /**
   * Gets a flash variable.
   *
   * @param  string $name     The name of the flash variable
   * @param  string $default  The default value returned when named variable does not exist.
   *
   * @return mixed The value of the flash variable
   */
  public function getFlash($name, $default = null)
  {
    if(!$this->hasFlash($name))
    {
      return $default;
    }

    return $this->getAttribute($name, $default, 'core/user/flash');
  }

  /**
   * Returns true if a flash variable of the specified name exists.
   *
   * @param  string $name  The name of the flash variable
   *
   * @return bool true if the variable exists, false otherwise
   */
  public function hasFlash($name)
  {
    return $this->hasAttribute($name, 'core/user/flash');
  }

  /**
   * Operazioni al momento dell chiusura del processo php
   */
  public function shutdown()
  {
    $this->setAttribute('lastrequest', time(), 'user');

    // rimozione variabili flash da eliminare
    foreach ($names = $this->getNames('core/user/flash/remove') as $name)
    {
      $this->removeAttribute($name, 'core/user/flash');
      $this->removeAttribute($name, 'core/user/flash/remove');
    }

    // termina immediatamente
    session_write_close();
  }

  /**
   * Inizializza l'istanza dell'oggetto User
   */
  public function initializeUser($user)
  {
    $this->user = $user;
    $this->setAttribute('id',            $user,                   'user');
  }

  /**
   * Esegue l'autenticazione nel sistema di un utente
   *
   * @param mixed $user
   */
  public function signIn($user)
  {
    $this->setAuthenticated(true);

    $this->setAttribute('loggedin',      true,                    'user');
    $this->setAttribute('remoteaddress', $_SERVER['REMOTE_ADDR'], 'user');
    $this->setAttribute('useragent', $_SERVER['HTTP_USER_AGENT'], 'user');

    $this->initializeUser($user);
  }

  /**
   * Chiude la sessione di autenticazione di un utente
   *
   * @param boolean $regenerate Se true rigenera la sessione attraverso il metodo {@see regenerate()}
   */
  public function signOut($regenerate = false)
  {
    $this->setAttribute('loggedin',      false,   'user');
    $this->removeAttribute('remoteaddress', 'user');
    $this->removeAttribute('useragent', 'user');

    $this->user = null;
    $this->removeAttribute('id','user');

    $this->unsetAllExept('user');

    if($regenerate) $this->regenerate();
  }

  /**
   * Conntrolla che l'utente sia autenticato
   *
   * @return boolean
   */
  public function isAuthenticated()
  {
    return $this->authenticated;
  }

  public function setAuthenticated($auth)
  {
    $this->authenticated = $auth;
  }

  /**
   * Ritorna l'istanza dell'oggetto User che rappresenta l'utente di sessione corrente
   *
   * @return User
   */
  public function getUser()
  {
    return $this->user;
  }

  /**
   * Rigenera l'identificativo della sessione
   *
   * Questa funzione è vuota
   */
  protected function regenerate()
  {
    // empty function
  }

  protected function destroy()
  {
    $this->doDestroy();

    $this->destroyed = true;
  }

  protected function doDestroy()
  {
    foreach ($this->toArray() as $var)
    {
      $this->removeAttribute($var);
    }
  }
  
  public function getId()
  {
    return $this->id;
  }

  /**
   * Rimuove tutti i domini dalla sessione eccetto quelli voluti
   *
   * @params string|array O il nome del dominio o un array di domini
   */
  public function unsetAllExept()
  {
    $exceptions = is_array(func_get_arg(0)) ? func_get_arg(0) : func_get_args();

    foreach ($this->toArray() as $key => $value)
    {
    	if(!array_key_exists($key, $exceptions))
    	{
    	  $this->removeAttribute($key);
    	}
    }
  }
  
  public function isStarted()
  {
    return $this->is_started;
  }

  /**
   * Ritorna la lista di tutte le variabili di sessione come un array
   *
   * @return array
   */
  abstract public function toArray();

  /**
   * Imposta una variabile di sessione
   *
   * @param string $name
   * @param mixed  $value
   * @param string $domain
   */
  abstract public function setAttribute($name, $value, $domain = '');

  /**
   * Recupera una variabile di sessione
   *
   * @param string $name
   * @param mixed  $default
   * @param string $domain
   */
  abstract public function getAttribute($name, $default = null, $domain = '');

  /**
   * Esegue il test dell'esistenza di una variabile di sessione
   *
   * @param string $name
   * @param string $domain
   */
  abstract public function hasAttribute($name, $domain = '');

  /**
   * Rimuove una variabile di sessione
   *
   * @param string $name
   * @param string $domain
   */
  abstract public function removeAttribute($name, $domain = '');

  /**
   * Ritorna i nomi di tutte le variabili di sessione
   *
   * @param string $domain
   */
  abstract public function getNames($domain = '');
}