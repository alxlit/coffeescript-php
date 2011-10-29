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
    if (count($this->bound_funcs))
    {
      foreach ($this->bound_funcs as $bvar)
      {
        $bname = $bvar->compile($options);
        array_unshift($this->ctor->body, yy('Literal', "this.{$bname} = ".utility('bind')."(this.{$bname}, this);"));
      }
    }
  }

  function add_properties($node, $name)
  {
    $props = array_slice($node->base->properties, 0);

    while ($assign = array_shift($props))
    {
      if ($assign instanceof yy_Assign)
      {
        $base = $assign->variable->base;
        $func = $assign->value;

        unset($assign->context);

        if ($base->value === 'constructor')
        {
          if ($this->ctor)
          {
            throw new Error('cannot define more than one constructor in a class');
          }

          if ($func->bound)
          {
            throw new Error('cannot define a constructor as a bound functions');
          }

          if ($func instanceof yy_Code)
          {
            $assign = $this->ctor = $func;
          }
          else
          {
            $assign = $this->ctor = yy('Assign', yy('Value', yy('Literal', $name)), $func);
          }
        }
        else
        {
          if ( ! ($assign->variable->this))
          {
            $assign->variable = yy('Value', yy('Literal', $name), array(yy('Access', $base, 'proto')));
          }

          if ($func instanceof yy_Code && $func->bound)
          {
            $this->bound_funcs[] = $base;
            $func->bound = FALSE;
          }
        }
      }
    }

    return $assign;
  }

  function compile_node($options)
  {
    $decl = $this->determine_name();
    $name = $decl ? $decl : (isset($this->name) && $this->name ? $this->name : '_Class');
    $lname = yy('Literal', $name);

    $this->set_context($name);
    $this->walk_body($name);
    $this->ensure_constructor($name);

    if ($this->parent)
    {
      array_splice($this->body->expressions, 1, 0, array(yy('Extends', $lname, $this->parent)));
    }

    $this->body->expressions[] = $lname;

    $this->add_bound_functions($options);

    $klass = yy('Parens', yy_Closure::wrap($this->body), TRUE);

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
      $decl = ($tail instanceof yy_Access) ? $tail->name->value : NULL;
    }
    else
    {
      $decl = $this->variable->base->value;
    }

    return $decl ? preg_match(IDENTIFIER, $decl) && $decl : FALSE;
  }

  function ensure_constructor($name)
  {
    if ( ! (isset($this->ctor) && $this->ctor))
    {
      $this->ctor = yy('Code');

      if ($this->parent)
      {
        $this->ctor->body->push(yy('Call', 'super', array(yy('Splat', yy('Literal', 'arguments')))));
      }

      array_unshift($this->body->expressions, $this->ctor);
    }

    $this->ctor->ctor = $this->ctor->name = $name;
    $this->ctor->klass = NULL;
    $this->ctor->no_return = TRUE;
  }

  function set_context($name)
  {
    $this->body->traverse_children(FALSE, function($node)
    {
      if (isset($node->class_body) && $node->class_body)
      {
        return FALSE;
      }

      if ($node instanceof yy_Literal && $node->value === 'this')
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

  function walk_body($name)
  {
    $self = $this;

    $this->traverse_children(FALSE, function($child) use ( & $self)
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
            $exps[$i] = $this->add_properties($node, $name);
          }
        }

        $child->expressions = $exps = flatten($exps);
      }
    });
  }
}

?>
