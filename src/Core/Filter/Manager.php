<?php

namespace Core\Filter;

use Core\Core;

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
  public $context;

  protected
    $chain = array(),
    $index = -1;

  /**
   * 
   * @param Core $dispatcher
   */
  public function __construct(Core $dispatcher)
  {
    $this->context = $dispatcher;

      Filter::setManager($this);
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
    return $this->context;
  }

  /**
   * Loads filters configuration for a given action instance.
   *
   */
  public function loadConfiguration()
  {
    // rendering filter è sempre il primo(/ultimo)
    //$this->register(new Rendering());
    /*
    // filtri utente
    $cache = new Cache();
    
    if(Config::get('FILTER/enable', false))
    {
      if(!$cache->has('filter'))
      {
        if(!is_readable(Config::get('application.dir') . '/ini/filter.ini'))
        {
          throw new \RuntimeException('Impossibile trovare il file filter.ini');
        }
        $data = parse_ini_file(Config::get('application.dir') . '/ini/filter.ini', true);
  
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
  public function execute($coin = null)
  {
    $this->index++;

    if ($this->index < count($this->chain))
    {
      // eseguo il filtro
      $this->chain[$this->index]->execute($coin);
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
