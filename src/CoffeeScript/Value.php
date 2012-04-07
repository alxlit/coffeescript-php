<?php

namespace CoffeeScript;

Init::init();

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
