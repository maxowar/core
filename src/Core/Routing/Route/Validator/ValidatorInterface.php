<?php

namespace Core\Routing\Route\Validator;

/**
 * Interfaccia per validatori di route
 *
 * @author Massimo Naccari <massimo.naccari@wdmn.it>
 * @copyright Massimo Naccari
 * @package core
 * @subpackage validator
 *
 */
interface ValidatorInterface
{
  public function filter(&$value);

  /**
   * validate the passed value against the current validator
   *
   * @param mixed $value
   */
  public function validate(&$value);

  /**
   * Ritorna il valore pulito dal validatore
   *
   * @return mixed
   */
  public function getCleanedValue();
}