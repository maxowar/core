<?php

namespace Core\Routing\Route\Validator;

/**
 * Interfaccia per i validatori
 *
 * @author Massimo Naccari <massimo.naccari@wdmn.it>
 * @copyright Massimo Naccari
 * @package core
 * @subpackage validator
 *
 */
abstract class BaseValidator implements RouteValidator
{
  protected $parameters;

  protected $cleanedValue;

  public function __construct($parameters = array())
  {
    $this->parameters = $parameters;
  }

  public function filter(&$value)
  {
    $value = addslashes($value);
  }

	/**
   * @see /lib/routing/validator/RouteValidator::validate()
   */
  public function validate(&$value)
  {
    $this->filter($value);

    return $this->doValidate($value);
  }

  public function getCleanedValue()
  {
    return $this->cleanedValue;
  }

  public function getParameters()
  {
    return $this->parameters;
  }

  abstract function doValidate($value);
}