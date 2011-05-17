<?php

namespace CoffeeScript;

class yyAccess extends yyBase
{
  public $children = array('name');

  function __construct($name, $tag = NULL)
  {
    $this->name = $name;
    $this->name->as_key = TRUE;

    $this->proto = $tag === 'proto' ? '.prototype' : '';
    $this->soak = $tag === 'soak';
  }

  function compile($options)
  {
    $name = $this->name->compile($options);
    return $this->proto.(preg_match(IS_STRING, $name) ? "[{$name}]" : ".{$name}");
  }

  function is_complex()
  {
    return FALSE;
  }
}

?>
