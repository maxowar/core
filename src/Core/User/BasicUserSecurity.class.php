<?php

/**
 * Semplice implementazione dell'interfaccia di sicurezza
 *
 * @author Massimo Naccari <massimo.naccari@wdmn.it>
 *
 */
abstract class BasicUserSecurity implements UserSecurity
{
	/**
   * @param unknown_type $credential
   */
  public function addCredential($credential)
  {

  }

	/**
   *
   */
  public function clearCredentials()
  {

  }

	/**
   * @param unknown_type $credential
   */
  public function hasCredential($credential)
  {

  }

	/**
   * @param unknown_type $credential
   */
  public function removeCredential($credential)
  {

  }


}