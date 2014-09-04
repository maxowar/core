<?php

namespace Core\Filter;

/**
 * Interfaccia dei filtri
 * 
 * Note: presa da Symony
 *
 * @author Massimo Naccari <massimo.naccari@wdmn.it>
 * @copyright Massimo Naccari
 * @package core
 * @subpackage filter
 */
abstract class Filter
{
  public static
  $filterCalled    = array();
  
  abstract public function execute(Manager $filterManager);
  
  /**
   * Returns true if this is the first call to the sfFilter instance.
   *
   * @return boolean true if this is the first call to the sfFilter instance, false otherwise
   */
  protected function isFirstCall()
  {
    $class = get_class($this);
    if (isset(self::$filterCalled[$class]))
    {
      return false;
    }
    else
    {
      self::$filterCalled[$class] = true;
  
      return true;
    }
  }
}