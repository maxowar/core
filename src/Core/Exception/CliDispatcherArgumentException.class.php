<?php

class CliDispatcherArgumentException extends CoreException
{
  public function __construct($message, $error)
  {
    $this->error = $error;
    parent::__construct($message);
  }

  public function __toString()
  {
    return $this->getError();
  }

  public function getError()
  {
    return '[Invalid Argument : ' . $this->error . ']' . str_replace('%value%', (string) CliDispatcher::getInstance()->getOption($this->error), parent::getMessage());
  }
}