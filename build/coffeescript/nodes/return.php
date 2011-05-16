<?php

namespace CoffeeScript;

class yyReturn extends yyBase
{
  public $children = array('expression');
  public $expression;

  function __construct($expr)
  {
    if ($expr && $expr->unwrap !== NULL)
    {
      $this->expression = $expr;
    }
  }

  function compile($options, $level)
  {
    $expr = isset($this->expression) ? $this->expression->make_return() : NULL;

    if ($expr && ! ($expr instanceof yyReturn))
    {
      return $expr->compile($options);
    }
    else
    {
      parent::compile($options, $level);
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
