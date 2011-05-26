<?php

namespace CoffeeScript;

class yy_Return extends yy_Base
{
  public $children = array('expression');
  public $expression;

  function constructor($expr)
  {
    if ($expr && (isset($expr->unwrap) && ! is_null($expr->unwrap)))
    {
      $this->expression = $expr;
    }

    return $this;
  }

  function compile($options, $level = NULL)
  {
    $expr = isset($this->expression) ? $this->expression->make_return() : NULL;

    if ($expr && ! ($expr instanceof yy_Return))
    {
      return $expr->compile($options);
    }
    else
    {
      return parent::compile($options, $level);
    }
  }

  function compile_node($options)
  {
    return $this->tab.'return'.(isset($this->expression) ? ' '.$this->expression->compile($options, LEVEL_PAREN) : '').';';
  }

  function is_statement()
  {
    return TRUE;
  }

  function jumps()
  {
    return $this;
  }

  function make_return()
  {
    return $this;
  }
}

?>
