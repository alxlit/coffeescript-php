<?php

namespace CoffeeScript;

class yy_Access extends yy_Base
{
  public $children = array('name');

  function constructor($name, $tag = NULL)
  {
    $this->name = $name;
    $this->name->as_key = TRUE;

    $this->soak = $tag === 'soak';

    return $this;
  }

  function compile($options, $level = NULL)
  {
    $name = $this->name->compile($options);
    return preg_match(IDENTIFIER, $name) ? ".{$name}" : "[{$name}]";
  }

  function is_complex()
  {
    return FALSE;
  }
}

?>
