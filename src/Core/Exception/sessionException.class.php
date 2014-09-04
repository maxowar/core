<?php

/**
 * Gestore degli errori riguardante le sessioni
 *
 * @author Massimo Naccari <massimo.naccari@wdmn.it>
 * @copyright Massimo Naccari
 * @package core
 * @subpackage exception
 */
class SessionException extends CoreException
{
  public $instance;
  
  /**
   * Esegue il signout e fa il redirect alla home page del sito
   *
   * @param string  $message
   * @param integer $code
   */
  public function __construct($message)
  {
    parent::__construct($message);

    if(Core::getCurrentInstance()->getSession())
    {
      Core::getCurrentInstance()->getSession()->signOut();
      Core::getCurrentInstance()->getSession()->regenerate();
    }

    Routing::redirect(Routing::get('home_page'));
  }
}