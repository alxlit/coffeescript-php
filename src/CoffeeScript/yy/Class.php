<?php

namespace CoffeeScript;

class yy_Class extends yy_Base
{
  public $children = array('variable', 'parent', 'body');

  function constructor($variable = NULL, $parent = NULL, $body = NULL)
  {
    $this->variable = $variable;
    $this->parent = $parent;

    $this->body = $body === NULL ? yy('Block') : $body;
    $this->body->class_body = TRUE;

    $this->bound_funcs = array();

    return $this;
  }

  function add_bound_functions($options)
  {
    if ($this->bound_funcs)
    {
      foreach ($this->bound_funcs as $bvar)
      {
        $lhs = yy('Value', yy('Literal', 'this'), array(yy('Access', $bvar)))->compile($options);
        $this->ctor->body->unshift(yy('Literal', "{$lhs} = ".utility('bind')."({$lhs}, this)"));
      }
    }
  }

  function add_properties($node, $name, $options)
  {
    $props = array_slice($node->base->properties, 0);
    $exprs = array();

    while ($assign = array_shift($props))
    {
      if ($assign instanceof yy_Assign)
      {
        $base = $assign->variable->base;
        $func = $assign->value;

        $assign->context = NULL;

        if ($base->value === 'constructor')
        {
          if ($this->ctor)
          {
            throw new Error('cannot define more than one constructor in a class');
          }

          if (isset($func->bound) && $func->bound)
          {
            throw new Error('cannot define a constructor as a bound functions');
          }

          if ($func instanceof yy_Code)
          {
            $assign = $this->ctor = $func;
          }
          else
          {
            $this->external_ctor = $options['scope']->free_variable('class');
            $assign = yy('Assign', yy('Literal', $this->external_ctor), $func);
          }
        }
        else
        {
          if (isset($assign->variable->this) && $assign->variable->this)
          {
            $func->static = TRUE;

            if (isset($func->bound) && $func->bound)
            {
              $func->context = $name;
            }
          }
          else
          {
            $assign->variable = yy('Value', yy('Literal', $name), array( yy('Access', yy('Literal', 'prototype')), yy('Access', $base) ));

            if ($func instanceof yy_Code && isset($func->bound) && $func->bound)
            {
              $this->bound_funcs[] = $base;
              $func->bound = FALSE;
            }
          }
        }
      }

      $exprs[] = $assign;
    }

    return compact($exprs);
  }

  function compile_node($options)
  {
    $decl = $this->determine_name();

    $name = $decl ? $decl : '_Class';

    if (isset($name->reserved) && $name->reserved)
    {
      $name = '_'.$name;
    }

    $lname = yy('Literal', $name);

    $this->hoist_directive_prologue();
    $this->set_context($name);
    $this->walk_body($name, $options);
    $this->ensure_constructor($name);
    $this->body->spaced = TRUE;

    if ( ! ($this->ctor instanceof yy_Code))
    {
      array_unshift($this->body->expressions, $this->ctor);
    }

    if ($decl)
    {
      array_unshift($this->body->expressions, yy('Assign', yy('Value', yy('Literal', $name), array(yy('Access', yy('Literal', 'name')))), yy('Literal', "'{$name}'")));
    }

    $this->body->expressions[] = $lname;
    $this->body->expressions = array_merge($this->directives, $this->body->expressions);

    $this->add_bound_functions($options);

    $call = yy_Closure::wrap($this->body);

    if ($this->parent)
    {
      $this->super_class = yy('Literal', $options['scope']->free_variable('super', FALSE));
      array_unshift($this->body->expressions, yy('Extends', $lname, $this->super_class));
      $call->args[] = $this->parent;

      if (isset($call->variable->params))
      {
        $params = & $call->variable->params;
      }
      else
      {
        $params = & $call->variable->base->params;
      }

      $params[] = yy('Param', $this->super_class);
    }

    $klass = yy('Parens', $call, TRUE);

    if ($this->variable)
    {
      $klass = yy('Assign', $this->variable, $klass);
    }

    return $klass->compile($options);
  }

  function determine_name()
  {
    if ( ! (isset($this->variable) && $this->variable))
    {
      return NULL;
    }

    if (($tail = last($this->variable->properties)))
    {
      $decl = $tail instanceof yy_Access ? $tail->name->value : NULL;
    }
    else
    {
      $decl = $this->variable->base->value;
    }

    if (in_array($decl, Lexer::$STRICT_PROSCRIBED, TRUE))
    {
      throw new SyntaxError("variable name may not be $decl");
    }

    $decl = $decl ? (preg_match(IDENTIFIER, $decl) ? $decl : NULL) : NULL;

    return $decl;
  }

  function ensure_constructor($name)
  {
    if ( ! (isset($this->ctor) && $this->ctor))
    {
      $this->ctor = yy('Code');

      if ($this->parent)
      {
        $this->ctor->body->push(yy('Literal', "{$name}.__super__.constructor.apply(this, arguments)"));
      }

      if (isset($this->external_ctor) && $this->external_ctor)
      {
        $this->ctor->body->push(yy('Literal', "{$this->external_ctor}.apply(this, arguments)"));
      }

      $this->ctor->body->make_return();

      array_unshift($this->body->expressions, $this->ctor);
    }

    $this->ctor->ctor = $this->ctor->name = $name;

    $this->ctor->klass = NULL;
    $this->ctor->no_return = TRUE;
  }

  function hoist_directive_prologue()
  {
    $index = 0;
    $expressions = $this->body->expressions;

    while (isset($expressions[$index]) && ($node = $expressions[$index]) && ( ($node instanceof yy_Comment) || ($node instanceof yy_Value) && $node->is_string() ))
    {
      $index++;
    }

    $this->directives = array_slice($expressions, 0, $index);
  }

  function set_context($name)
  {
    $this->body->traverse_children(FALSE, function($node) use ($name)
    {
      if (isset($node->class_body) && $node->class_body)
      {
        return FALSE;
      }

      if ($node instanceof yy_Literal && ''.$node->value === 'this')
      {
        $node->value = $name;
      }
      else if ($node instanceof yy_Code)
      {
        $node->klass = $name;

        if ($node->bound)
        {
          $node->context = $name;
        }
      }
    });
  }

  function walk_body($name, $options)
  {
    $self = $this;

    $this->traverse_children(FALSE, function($child) use ($name, $options, & $self)
    {
      if ($child instanceof yy_Class)
      {
        return FALSE;
      }

      if ($child instanceof yy_Block)
      {
        foreach (($exps = $child->expressions) as $i => $node)
        {
          if ($node instanceof yy_Value && $node->is_object(TRUE))
          {
            $exps[$i] = $self->add_properties($node, $name, $options);
          }
        }

        $child->expressions = $exps = flatten($exps);
      }
    });
  }
}

?>
