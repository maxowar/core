<?php

namespace Core\User;

interface Security
{
  /**
   * Aggiunge una credenziale all'utente
   *
   * @param mixed $credential
   */
  public function addCredential($credential);

  /**
   * Pulisce tutte le credenziali
   */
  public function clearCredentials();

  /**
   * Controlla se un utente ha determinate credenziali
   *
   * @param mixed $credential
   * @return boolean
   */
  public function hasCredential($credential);

  /**
   * Rimuove una credenziale dall'utente
   *
   * @param mixed $credential
   */
  public function removeCredential($credential);

}