<?php

namespace CoffeeScript;

if (! class_exists('CoffeeScript\Init')) {
    throw new \RuntimeException('The class Init not found');
}

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
