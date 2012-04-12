<?php

namespace CoffeeScript;

class yy_Existence extends yy_Base
{
  public $children = array('expression');

  function constructor($expression)
  {
    $this->expression = $expression;

    return $this;
  }

  function compile_node($options = array())
  {
    $this->expression->front = $this->front;
    $code = $this->expression->compile($options, LEVEL_OP);

    if (preg_match(IDENTIFIER, $code) && ! $options['scope']->check($code))
    {
      list($cmp, $cnj) = $this->negated ? array('===', '||') : array('!==', '&&');

      $code = "typeof {$code} {$cmp} \"undefined\" {$cnj} {$code} {$cmp} null";
    }
    else
    {
      $code = "{$code} ".($this->negated ? '==' : '!=').' null';
    }

    return (isset($options['level']) && $options['level'] <= LEVEL_COND) ? $code : "({$code})";
  }

  function invert()
  {
    $this->negated = ! $this->negated;
    return $this;
  }
}

?>
