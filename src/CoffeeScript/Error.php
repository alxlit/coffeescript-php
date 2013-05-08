<?php

namespace CoffeeScript;

Init::initiate();

class Error extends \Exception
{
  function __construct($message)
  {
    $this->message = $message;
  }
}

?>
