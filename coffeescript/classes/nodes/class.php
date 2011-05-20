<?php

namespace CoffeeScript;

class yyClass extends yyBase
{
  public $children = array('variable', 'parent', 'body');

  function __construct($variable, $parent, $body = NULL)
  {
    $this->variable = $parent;
    $this->parent = $parent;

    $this->body = is_null($body) ? new yyBlock : $body;
    $this->body->class_body = TRUE;

    $this->bound_funcs = array();
  }

  function add_bound_functions($options)
  {
    if (count($this->bound_funcs))
    {
      foreach ($this->bound_funcs as $bvar)
      {
        $bname = $bvar->compile($options);
        array_unshift($this->ctor->body, new yyLiteral("this.{$bname} = ".utility('bind')."(this.{$bname}, this);"));
      }
    }
  }

  function add_properties($node, $name)
  {
    $props = array_slice($node->base->properties, 0);

    while ($assign = array_shift($props))
    {
      if ($assign instanceof yyAssign)
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

          if ($func instanceof yyCode)
          {
            $assign = $this->ctor = $func;
          }
          else
          {
            $assign = $this->ctor = new yyAssign(new yyValue(new yyLiteral($name)), $func);
          }
        }
        else
        {
          if ( ! ($assign->variable->this))
          {
            $assign->variable = new yyValue(new yyLiteral($name), array(new yyAccess($base, 'proto')));
          }

          if ($func instanceof yyCode && $func->bound)
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
    $name = $decl ? $decl : ($this->name ? $this->name : '_Class');
    $lname = new yyLiteral($name);

    $this->set_context($name);
    $this->walk_body($name);
    $this->ensure_constructor($name);

    if ($this->parent)
    {
      array_splice($this->body->expressions, 1, 0, new yyExtends($lname, $this->parent));
    }

    $this->body->expressions[] = $lname;

    $this->add_bound_functions($options);

    $klass = new yyParens(Closure::wrap($this->body), TRUE);

    if ($this->variable)
    {
      $klass = new yyAssigns($this->variable, $klass);
    }

    return $klass->compile($options);
  }

  function determine_name()
  {
    if ( ! $this->variable)
    {
      return NULL;
    }

    if (($tail = last($this->variable->properties)))
    {
      $decl = (tail instanceof yyAccess) ? $tail->name->value : NULL;
    }
    else
    {
      $decl = $this->variable->base->value;
    }

    return $decl ? preg_match(IDENTIFIER, $decl) && $decl : FALSE;
  }

  function ensure_constructor($name)
  {
    if ( ! $this->ctor)
    {
      $this->ctor = new yyCode;

      if ($this->parent)
      {
        $this->ctor->body[] = new yyCall('super', array(new yySplat(new yyLiteral('arguments'))));
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
      if ($node->class_body)
      {
        return FALSE;
      }

      if ($node instanceof yyLiteral && $node->value === 'this')
      {
        $node->value = $name;
      }
      else if ($node instanceof yyCode)
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
      if ($child instanceof yyClass)
      {
        return FALSE;
      }

      if ($child instanceof yyBlock)
      {
        $exps = $child->expressions;

        foreach ($exps as $i => $node)
        {
          if ($node instanceof yyValue && $node->is_object(TRUE))
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
