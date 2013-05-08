<?php

namespace CoffeeScript;

Init::initiate();

class Value
{
  function __construct($v)
  {
    $this->v = $v;
  }

  function __toString()
  {
    return $this->v;
  }
}

?>
