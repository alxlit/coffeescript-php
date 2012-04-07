<?php

namespace CoffeeScript;

Init::init();

class Error extends \Exception
{
  function __construct($message)
  {
    $this->message = $message;
  }
}

?>
