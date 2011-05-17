<?php

namespace CoffeeScript;

class yyCode extends yyBase
{
  public $children = array('params', 'body');

  function __construct($params = NULL, $body = NULL, $tag = NULL)
  {
    $this->params = $params ? $params : array();
    $this->body = $body ? $body : new yyBlock;
    $this->bound = $tag === 'boundfunc';
    $this->context = $this->bound ? 'this' : NULL;
  }

  function compile_node($options)
  {
    $options['scope'] = new yyScope($options['scope'], $this->body, $this);
    $options['scope']->shared = del($options, 'sharedScope');
    $options['indent'] .= TAB;

    unset($options['bare']);

    $vars  = array();
    $exprs = array();

    foreach ($this->params as $param)
    {
      if ($param->splat)
      {
        if (isset($param->name->value))
        {
          $options['scope']->add($param->name->value, 'var');
        }

        $params = array();

        foreach ($this->params as $p)
        {
          $params[] = $p->as_reference($options);
        }

        $splats = new yyAssign(new yyValue(new yyArr($params)), new yyValue(new yyLiteral('arguments')));

        break;
      }
    }

    foreach ($this->params as $param)
    {
      if ($param->is_complex())
      {
        $val = $ref = $param->as_reference($options);

        if ($param->value)
        {
          $val = new yyOp('?', $ref, $param->value);
        }

        $exprs[] = new yyAssign(new yyValue($param->name), $val, '=', array('param' => TRUE));
      }
      else
      {
        $ref = $param;

        if ($param->value)
        {
          $lit = new yyLiteral($ref->name->value.' == null');
          $val = new yyAssign(new yyValue($param->name), $param->value, '=');

          $exprs[] = new yyIf($lit, $val);
        }
      }

      if ( ! $splats)
      {
        $vars[] = $ref;
      }
    }

    $was_empty = $this->body->is_empty();

    if ($splats)
    {
      array_unshift($exprs, $splats);
    }

    if (count($exprs))
    {
      $this->body->expressions = array_merge($this->body->expressions, $exprs);
    }

    if ( ! $splats)
    {
      foreach ($vars as $i => $v)
      {
        $options['scope']->parameter(($vars[$i] = $v->compile($options)));
      }
    }

    if ( ! ($was_empty || $this->no_return))
    {
      $this->body->make_return();
    }

    $idt = $options['indent'];
    $code = 'function';

    if ($this->ctor)
    {
      $code .= ' '.$this->name;
    }

    $code .= '('.implode(', ', $vars).') {';

    if ( ! $this->body->is_empty())
    {
      $code .= "\n".$this->body->compile_with_declarations($options)."\n{$this->tab}";
    }

    $code .= '}';

    if ($this->ctor)
    {
      return $this->tab.$code;
    }

    if ($this->bound)
    {
      return utility('bind')."({$code}, {$this->context})";
    }

    return ($this->front || ($options['level'] >= LEVEL_ACCESS)) ? "({$code})" : $code;
  }

  function is_statement()
  {
    return !! $this->ctor;
  }

  function jumps()
  {
    return FALSE;
  }

  function traverse_children($cross_scope, $func)
  {
    if ($cross_scope)
    {
      parent::traverse_children($cross_scope, $func);
    }
  }
}

?>
