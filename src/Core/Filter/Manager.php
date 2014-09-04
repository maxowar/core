<?php

namespace Core\Filter;

use Core\Core;
use Core\Util\Config;

/**
 * Gestore dei filtri delle request
 *
 * Semplice implementazione del filter paradigm
 *
 * @author Massimo Naccari
 * @copyright Massimo Naccari
 * @package core
 * @subpackage filter
 */
class Manager
{
  /**
   *
   * @var Core
   */
  public $dispatcher;

  protected
    $chain = array(),
    $index = -1;

  /**
   * 
   * @param Core $dispatcher
   */
  public function __construct(Core $dispatcher)
  {
    $this->dispatcher = $dispatcher;
  }
  
  public function reset()
  {
    $this->index = -1;
  }

  /**
   * @return Core
   */
  public function getContext()
  {
    return $this->dispatcher;
  }

  /**
   * Loads filters configuration for a given action instance.
   *
   */
  public function loadConfiguration()
  {
    // rendering filter è sempre il primo(/ultimo)
    $this->register(new Rendering());
    /*
    // filtri utente
    $cache = new Cache();
    
    if(Config::get('FILTER/enable', false))
    {
      if(!$cache->has('filter'))
      {
        if(!is_readable(Config::get('MAIN/base_path') . '/ini/filter.ini'))
        {
          throw new \RuntimeException('Impossibile trovare il file filter.ini');
        }
        $data = parse_ini_file(Config::get('MAIN/base_path') . '/ini/filter.ini', true);
  
        $cache->set('filter', $data);
      }
  
      include_once($cache->getFilePath('filter'));
    }
    */
    // execution filter è sempre l'ultimo
    $this->register(new Execution());
  }

  /**
   * Esegue il prossimo filtro registrato
   *
   */
  public function execute()
  {
    // skip to the next filter
    ++$this->index;

    if ($this->index < count($this->chain))
    {
      if(Config::get('LOG/debug', false))
      {
        //Logger::debug('FilterManager | execute | esecuzione del filtro "' . get_class($this->chain[$this->index]) . '"');
      }

      // eseguo il filtro
      $this->chain[$this->index]->execute($this);
    }
  }

  /**
   * Controlla l'esistenza di un filtro nella catena dei filtri
   *
   * @param string $class Il nome della classe filtro
   * @return boolean
   */
  public function hasFilter($class)
  {
    foreach ($this->chain as $filter)
    {
      if ($filter instanceof $class)
      {
        return true;
      }
    }

    return false;
  }

  /**
   * Registra un filtro nell'esecuzione dei filtri
   *
   * @param Filter $filter
   */
  public function register(Filter $filter)
  {
    $this->chain[] = $filter;
  }
}
