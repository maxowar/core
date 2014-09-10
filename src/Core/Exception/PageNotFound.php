<?php

namespace Core\Exception;

use Core\Util\Config;

/**
 * Gestisce errori riguardanti problemi di routing interno
 *
 * @author Massimo Naccari
 * @copyright Massimo Naccari
 * @package core
 * @subpackage exception
 *
 */
class PageNotFound extends Exception
{
  const STOP_ON_BC = 100;

  protected $httpCode = '404';
  
  public function __construct($message, $code = 0, \Exception $previous = null)
  {
    parent::__construct($message, $code, $previous);

    if(!Config::get('application.debug'))
    {
      $this->sendHttpHeaders();
      Core::getCurrentInstance()->forward404($message);
      exit(0);
    }
  }

  protected function sendHttpHeaders()
  {
    header('HTTP/1.0 404 Page Not Found');
    header('Content-Type: text/html; charset=utf8');
  }

}