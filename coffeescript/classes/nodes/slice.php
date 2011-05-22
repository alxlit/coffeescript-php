<?php

namespace CoffeeScript;

class yySlice extends yyBase
{
  public $children = array('range');

  function __construct($range)
  {
    parent::__construct();
    $this->range = $range;
  }

  function compile_node($options)
  {
    $to = $this->range->to;
    $from = $this->range->from;

    $from_str = $from ? $from->compile($options, LEVEL_PAREN) : '0';
    $compiled = $to ? $to->compile($options, LEVEL_PAREN) : '';

    if ($to && ! ( ! $this->range->exclusive && ((int) $compiled) === -1))
    {
      $to_str = ', ';

      if ($this->range->exclusive)
      {
        $to_str .= $compiled;
      }
      else if (preg_match(SIMPLENUM, $compiled))
      {
        $to_str .= (((int) $compiled) + 1);
      }
      else
      {
        $to_str .= "({$compiled} + 1) || 9e9";
      }
    }

    return ".slice({$from_str}".(isset($to_str) ? $to_str : '').')';
  }
}

?>
