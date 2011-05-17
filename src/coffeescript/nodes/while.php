<?php

namespace CoffeeScript;

class yyWhile extends yyBase
{
  public $children = array('condition', 'guard', 'body');

  function __construct($condition, $options = NULL)
  {
    $this->condition = $options && $options->invert ? $condition->invert : $condition;
    $this->guard = $options ? $options->guard : NULL;
  }

  function add_body($body)
  {
    $this->body = $body;
    return $this;
  }

  function compile_node($options)
  {
    $options['indent'] .= TAB;
    $set = '';
    $body = $this->body;

    if ($body->is_empty())
    {
      $body = '';
    }
    else
    {
      if ($options['level'] > LEVEL_TOP || $this->returns)
      {
        $rvar = $options['scope']->free_variable('results');
        $set = "{$this->tab}{$this->rvar} = [];\n";

        if ($body)
        {
          $body = yyPush::wrap($rvar, $body);
        }
      }

      if ($this->guard)
      {
        $body = Block::wrap(array(new yyIf($this->guard, $body)));
      }

      $body = "\n".$body->compile($options, LEVEL_TOP)."\n{$this->tab}";
    }

    $code = $set.$this->tab.'while ('.$this->condition->compile($options, LEVEL_PAREN).") {{$body}}";

    if ($this->returns)
    {
      $code .= "\n{$this->tab}return {$rvar};";
    }

    return $code;
  }

  function is_statement()
  {
    return TRUE;
  }

  function jumps()
  {
    $expressions = $this->body->expressions;

    if ( ! count($expressions))
    {
      return FALSE;
    }

    foreach ($expressions as $node)
    {
      if ($node->jumps(array('loop' => TRUE)))
      {
        return $node;
      }
    }

    return FALSE;
  }

  function make_return()
  {
    $this->returns = TRUE;

    return $this;
  }
}

?>
