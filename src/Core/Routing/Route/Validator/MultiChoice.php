<?php

namespace Core\Routing\Route\Validator;

/**
 * Validatore che accetta una route in base ad una lista di valori
 *
 * @author Massimo Naccari <massimo.naccari@wdmn.it>
 * @copyright Massimo Naccari
 * @package core
 * @subpackage routing
 *
 */
class MultiChoice extends BaseRouteValidator
{
  public function doValidate($value )
  {
    if(!isset($this->parameters['position'])){
      $this->parameters['position'] = 0;
    }
    if(!$this->inChoices($value, $this->parameters['choices'] , $this->parameters['position'] ) )
    {
      Logger::debug(sprintf('ChoiceRouteValidator | doValidate | Il valore "%s" non compare nella lista dei valori possibili', $value));

      return false;
    }

    return true;
  }

  protected function inChoices($value, array $choices = array() , $arrayPosition )
  {
    foreach ($choices as $key => $choice)
    {
      if ((string) (is_array($choice)? $choice[$arrayPosition] : $choice)  == (string) $value)
      {
        $this->cleanedValue = array('key' => $key , 'value' => $choice);
        return true;
      }
    }

    return false;
  }
}
