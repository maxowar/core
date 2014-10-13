<?php

namespace Core\Filter;

/**
 * Interfaccia dei filtri
 * 
 * Note: presa da Symfony
 *
 * @author Massimo Naccari <massimo.naccari@wdmn.it>
 * @copyright Massimo Naccari
 * @package core
 * @subpackage filter
 */
abstract class Filter
{
  public static $filterCalled = array();

    public static $filterManager;

    public function __construct(Manager $filterManager = null)
    {
        if(!self::$filterManager)
        {
            if(!$filterManager)
            {
                throw new \InvalidArgumentException('Need a manager');
            }
            self::$filterManager = $filterManager;
        }
    }

    public static  function setManager(Manager $manager)
    {
        self::$filterManager = $manager;
    }
  
  abstract public function execute($coin = null);
  
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

    protected function keepon($coin = null)
    {
        self::$filterManager->execute($coin);
    }

    protected function getContext()
    {
        return self::$filterManager->getContext();
    }

}