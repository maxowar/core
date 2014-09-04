<?php

/**
 * Gestione SEO friendly della disabilitazione dell'applicazione
 *
 * @author Massimo Naccari
 * @copyright Massimo Naccari
 * @package core
 * @subpackage exception
 */
class ApplicationDisabledException extends CoreException
{
  protected $httpCode = '503';

  /**
   * Unix timestamp della fine dei lavori di manutenzione
   *
   * @var integer
   */
  public $maintenanceEndTime;

  /**
   * Invia il codice HTTP 503 e inizializza l'header HTTP Retry-After con il tempo mancante alla fine dei lavori
   *
   */
  protected function sendHttpHeaders()
  {
    header('HTTP/1.0 503 Service Unavailable');
    header('Content-Type: text/html; charset=utf8');
    header('Retry-After: ' . ($this->maintenanceEndTime && $this->maintenanceEndTime - time() > 0 ? $this->maintenanceEndTime - time() : 1800 ));
  }

  public function __construct($message = null, $code = 503)
  {
    $this->maintenanceEndTime = $message;

    parent::__construct('Site Under Maintenance', $code);
  }

  /**
   * Ritorna il path ai template degli errori|eccezioni
   *
   * @param boolean $debug
   *
   * @return mixed
   */
  protected static function getTemplatePathForError($debug = false)
  {
    $template = 'maintenance.html.php';

    $path = is_readable(Config::get('MAIN/base_path') . '/lib/exception/data/' . $template) ? Config::get('MAIN/base_path') . '/lib/exception/data/' . $template : Config::get('MAIN/core_path') . '/lib/exception/data/' . $template;

    if (!is_null($path) && is_readable($path))
    {
      return $path;
    }

    return false;
  }
}