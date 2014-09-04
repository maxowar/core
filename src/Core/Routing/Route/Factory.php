<?php

namespace Core\Routing\Route;

/**
 * Crea oggetti di tipo Route
 *
 * @todo gestire maiuscole e minuscole nella relazione tra nome classe e file della class
 *
 * @author Massimo Naccari <massimo.naccari@wdmn.it>
 * @copyright Massimo Naccari
 * @package core
 * @subpackage routing
 */
class Factory
{
    private function __construct()
    {

    }

  public static function createRoute($routeName, $parameters)
  {
    $className = isset($parameters['class']) ?  $parameters['class'] : Config::get('ROUTING/default_route_class');

    if(!class_exists($className))
    {
      $classPath = Config::get('MAIN/core_path') . '/lib/routing/' . $className . '.class.php';
      if(file_exists($classPath))
      {
        require_once($classPath);
      }
      else
      {
        throw new Exception(sprintf('La classe "%s" non è definita e il file "%s" non esiste', $className, $classPath));
      }
    }

    $route = new $className($routeName, $parameters);
    if(!($route instanceof Route))
    {
      throw new CoreException(sprintf('L\'istanza di "%s" non è di tipo Route', $className));
    }

    return $route;
  }
}