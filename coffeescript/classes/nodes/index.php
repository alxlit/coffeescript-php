<?php

namespace CoffeeScript;

class yyIndex extends yyBase
{
  public $children = array('index');

  function __construct($index)
  {
    $this->index = $index;
  }

  function compile($options)
  {
    return ($this->proto ? '.prototype' : '').'['.$this->index->compile($options, LEVEL_PAREN).']';
  }

  function is_complex()
  {
    return $this->index->is_complex();
  }
}

?>
