<?php

namespace Core\Routing\Route\Validator;

/**
 * Validatore di route che valida una route rispetto ad una
 * espressione regolare
 *
 * @author Massimo Naccari <massimo.naccari@wdmn.it>
 * @copyright Massimo Naccari
 * @package core
 * @subpackage validator
 *
 */
class Regexp extends BasValidator
{
  public function doValidate($value)
  {
    if(!$match = preg_match($this->parameters['pattern'], $value , $this->cleanedValue))
    {
      Logger::debug(sprintf('RegexpRouteValidator | doValidate | Il valore "%s" non è compatibile con "%s"', $value, $this->parameters['pattern']));
    }
    return $match;
  }
}