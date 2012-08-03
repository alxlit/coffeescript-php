<?php

namespace CoffeeScript;

class yy_Throw extends yy_Base
{
  public $children = array('expression');

  function constructor($expression)
  {
    $this->expression = $expression;

    return $this;
  }

  function compile_node($options = array())
  {
    return $this->tab.'throw '.$this->expression->compile($options).';';
  }

  function is_statement($options = NULL)
  {
    return TRUE;
  }

  function jumps()
  {
    return FALSE;
  }

  function make_return($res = NULL)
  {
    return $this;
  }
}

?>
