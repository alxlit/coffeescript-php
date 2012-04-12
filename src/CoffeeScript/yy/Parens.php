<?php

namespace CoffeeScript;

class yy_Parens extends yy_Base
{
  public $children = array('body');

  function constructor($body)
  {
    $this->body = $body;

    return $this;
  }

  function compile_node($options = array())
  {
    $expr = $this->body->unwrap();

    if ($expr instanceof yy_Value && $expr->is_atomic())
    {
      $expr->front = $this->front;
      return $expr->compile($options);
    }

    $code = $expr->compile($options, LEVEL_PAREN);

    $bare = $options['level'] < LEVEL_OP && ($expr instanceof yy_Op || $expr instanceof yy_Call ||
      ($expr instanceof yy_For && $expr->returns));

    return $bare ? $code : "({$code})";
  }

  function is_complex()
  {
    return $this->body->is_complex();
  }

  function unwrap()
  {
    return $this->body;
  }
}

?>
