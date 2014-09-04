<?php

namespace Core\Routing\Route\Validator;

/**
 * Valida un valore rispetto ad una lista di valori non predefiniti
 *
 *
 * @author Massimo Naccari <massimo.naccari@wdmn.it>
 * @package core
 * @subpackage validator
 * @version $Id$
 */
class CallbackChoice extends Choice
{
  public function __construct($parameters = array())
  {
    if(!is_callable($parameters['method']))
    {
      throw new RuntimeException('Needed a valid callable');
    }

    parent::__construct($parameters);
  }

  public function doValidate($value)
  {
    $resultSet = call_user_func($this->parameters['method']);

    $choices = array();
    if(is_array($resultSet) || $resultSet instanceof Traversable)
    {
      foreach ($resultSet as $item)
      {
        $choices[] = $item->{$this->parameters['value']}();
      }
    }
    $this->parameters['choices'] = $choices;

    $isValid = parent::doValidate($value);

    if($isValid)
    {
      $this->cleanedValue = $item;
    }

    return $isValid;
  }
}
