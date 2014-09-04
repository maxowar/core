<?php

namespace Core\Routing\Route;

/**
 * Interfaccia di accesso per gli oggetti di tipo Route
 *
 * @author Massimo Naccari <massimo.naccari@wdmn.it>
 * @copyright Massimo Naccari
 * @package core
 * @subpackage routing
 */
interface RouteInterface
{
  public function getName();

  public function getUrl();

  public function matchesUrl($url);

  public function createUrl($parameters = array());
}