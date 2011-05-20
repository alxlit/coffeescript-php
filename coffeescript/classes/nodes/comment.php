<?php

namespace CoffeeScript;

class yyComment extends yyBase
{
  function __construct($comment)
  {
    $this->comment = $comment;
  }

  function compile_node($options, $level = NULL)
  {
    $code = '/*'.multident($this->comment, $this->tab).'*/';
    
    if ($level === LEVEL_TOP || $options['level'] === LEVEL_TOP)
    {
      $code = $options['indent'].$code;
    }

    return $code;
  }

  function is_statement()
  {
    return TRUE;
  }

  function make_return()
  {
    return $this;
  }
}

?>
