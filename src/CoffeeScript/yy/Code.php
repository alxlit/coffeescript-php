<?php

namespace CoffeeScript;

class yy_Code extends yy_Base
{
  public $children = array('params', 'body');

  function constructor($params = NULL, $body = NULL, $tag = NULL)
  {
    $this->params = $params ? $params : array();
    $this->body = $body ? $body : yy('Block');
    $this->bound = $tag === 'boundfunc';
    $this->context = $this->bound ? '_this' : NULL;

    return $this;
  }

  function compile_node($options)
  {
    $options['scope'] = new Scope($options['scope'], $this->body, $this);
    $options['scope']->shared = del($options, 'sharedScope');
    $options['indent'] .= TAB;

    unset($options['bare']);
    unset($options['isExistentialEquals']);

    $params = array();
    $exprs = array();

    foreach ($this->param_names() as $name)
    {
      if ( ! $options['scope']->check($name))
      {
        $options['scope']->parameter($name);
      }
    }

    foreach ($this->params as $param)
    {
      if ($param->splat)
      {
        if (isset($param->name->value) && $param->name->value)
        {
          $options['scope']->add($param->name->value, 'var', TRUE);
        }

        $params = array();

        foreach ($this->params as $p)
        {
          $params[] = $p->as_reference($options);
        }

        $splats = yy('Assign', yy('Value', yy('Arr', $params)), yy('Value', yy('Literal', 'arguments')));

        break;
      }
    }

    foreach ($this->params as $param)
    {
      if ($param->is_complex())
      {
        $val = $ref = $param->as_reference($options);

        if (isset($param->value) && $param->value)
        {
          $val = yy('Op', '?', $ref, $param->value);
        }

        $exprs[] = yy('Assign', yy('Value', $param->name), $val, '=', array('param' => TRUE));
      }
      else
      {
        $ref = $param;

        if ($param->value)
        {
          $lit = yy('Literal', $ref->name->value.' == null');
          $val = yy('Assign', yy('Value', $param->name), $param->value, '=');

          $exprs[] = yy('If', $lit, $val);
        }
      }

      if ( ! (isset($splats) && $splats))
      {
        $params[] = $ref;
      }
    }

    $was_empty = $this->body->is_empty();

    if (isset($splats) && $splats)
    {
      array_unshift($exprs, $splats);
    }

    if ($exprs)
    {
      foreach (array_reverse($exprs) as $expr)
      {
        array_unshift($this->body->expressions, $expr);
      }
    }

    foreach ($params as $i => $p)
    {
      $options['scope']->parameter(($params[$i] = $p->compile($options)));
    }

    $uniqs = array();

    foreach ($this->param_names() as $name)
    {
      if (in_array($name, $uniqs))
      {
        throw new SyntaxError("multiple parameters named $name");
      }

      $uniqs[] = $name;
    }

    if ( ! ($was_empty || $this->no_return))
    {
      $this->body->make_return();
    }

    if ($this->bound)
    {
      if (isset($options['scope']->parent->method->bound) && $options['scope']->parent->method->bound)
      {
        $this->bound = $this->context = $options['scope']->parent->method->context;
      }
      else if ( ! (isset($this->static) && $this->static))
      {
        $options['scope']->parent->assign('_this', 'this');
      }
    }

    $idt = $options['indent'];
    $code = 'function';

    if ($this->ctor)
    {
      $code .= ' '.$this->name;
    }

    $code .= '('.implode(', ', $params).') {';

    if ( ! $this->body->is_empty())
    {
      $code .= "\n".$this->body->compile_with_declarations($options)."\n{$this->tab}";
    }

    $code .= '}';

    if ($this->ctor)
    {
      return $this->tab.$code;
    }

    return ($this->front || $options['level'] >= LEVEL_ACCESS) ? "({$code})" : $code;
  }

  function param_names()
  {
    $names = array();

    foreach ($this->params as $param)
    {
      $names = array_merge($names, (array) $param->names());
    }

    return $names;
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
      return parent::traverse_children($cross_scope, $func);
    }

    return NULL;
  }
}

?>
