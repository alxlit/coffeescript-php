<?php

namespace CoffeeScript;

class yyCall extends yyBase
{
  public $children = array('variable', 'args');

  function __construct($variable, $args = array(), $soak = FALSE)
  {
    $this->args = $args;
    $this->super = $variable === 'super';
    $this->variable = $this->super ? NULL : $variable;
  }

  function compile_node($options)
  {
    if ($this->variable)
    {
      $this->variable->front = $this->front;
    }

    if (($code = yySplat::compile_splatted_array($options, $this->args, TRUE)))
    {
      return $this->compile_splat($options, $code);
    }

    $args = array();

    foreach ($this->filter_implicit_objects($this->args) as $arg)
    {
      $args[] = $arg->compile($options, LEVEL_LIST);
    }

    $args = implode(', ', $args);

    if ($this->is_super())
    {
      return $this->super_reference($options).'.call(this'.($args ? ', '.$args : '').')';
    }
    else
    {
      return ($this->is_new() ? 'new ' : '').$this->variable->compile($options, LEVEL_ACCESS)."({$args})";
    }
  }

  function compile_super($args, $options)
  {
    return $this->super_reference($options).'.call(this'.(count($args) ? ', ' : '').$args.')';
  }

  function compile_splat($options, $splat_args)
  {
    if ($this->is_super())
    {
      return $this->super_reference($options).'.apply(this, '.$splat_args.')';
    }

    if ($this->is_new())
    {
      $idt = $this->tab.TAB;

      return 
        '(function(func, args, ctor) {'
      . "{$idt}ctor.prototype = func.prototype;"
      . "{$idt}var child = new ctor, result = func.apply(child, args);"
      . "{$idt}return typeof result === \"object\" ? result : child;"
      . "{$this->tab}})(".$this->variable->compile($options, LEVEL_LIST).", $splat_args, function() {})";
    }

    $base = new yyValue($this->variable);

    if (($name = array_pop($base->properties)) && $base->is_complex())
    {
      $ref = $options['scope']->free_variable('ref');
      $fun = "($ref = ".$base->compile($options, LEVEL_LIST).')'.$name->compile($options).'';
    }
    else
    {
      $fun = $base->compile($options, LEVEL_ACCESS);
      $fun = preg_match(SIMPLENUM, $fun) ? "($fun)" : $fun;

      if ($name)
      {
        $ref = $fun;
        $fun .= $name->compile($options);
      }
      else
      {
        $ref = null;
      }
    }

    return "{$fun}.apply({$ref}, {$splatArgs})";
  }

  function is_new($set = NULL)
  {
    static $val = FALSE;

    return is_null($set) ? $val : ($val = $set);
  }

  function is_super()
  {
    return $this->super;
  }

  function filter_implicit_objects($list)
  {
    $nodes = array();

    foreach ($list as $node)
    {
      if ( ! ($node->is_object() && $node->base->generated))
      {
        $nodes[] = $node;
        continue;
      }

      $obj = null;
      $properties[] = array();

      foreach ($node->base->properties as $prop)
      {
        if ($prop instanceof yyAssign)
        {
          if ( ! $obj)
          {
            $nodes[] = ($obj = new yyObj($properties = array(), TRUE));
          }

          $properties[] = $prop;
        }
        else
        {
          $nodes[] = $prop;
          $obj = NULL;
        }
      }
    }

    return $nodes;
  }

  function new_instance()
  {
    $base = isset($this->variable->base) ? $this->variable->base : $variable;

    if ($base instanceof yyCall)
    {
      $base->new_instance();
    }
    else
    {
      $this->is_new = TRUE;
    }

    return $this;
  }

  function super_reference($options)
  {
    $method = $options['scope']->method;

    if ( ! $method)
    {
      throw SyntaxError('cannot call super outside of a function.');
    }

    $name = $method->name;

    if ( ! $name)
    {
      throw SyntaxError('cannot call super on an anonymous function.');
    }

    if ($method->klass)
    {
      return $method->klass.'.__super__.'.$name;
    }
    else
    {
      return $name.'.__super__.constructor';
    }
  }

  function unfold_soak($options)
  {
    if ($this->soak)
    {
      if ($this->variable)
      {
        if (($ifn = unfold_soak($options, $this, 'variable')))
        {
          return $ifn;
        }

        $tmp = new yyValue($this->variable);
        list($left, $rite) = $tmp->cache_reference($options);
      }
      else
      {
        $left = new yyLiteral($this->super_reference($options));
        $rite = new yyValue($left);
      }

      $rite = new yyCall($rite, $this->args);
      $rite->is_new($this->is_new());
      $left = new yyLiteral('typeof '.$left->compile($options).' === "function"');

      return new yyIf($left, new yyValue($rite), array('soak' => TRUE));
    }

    $call = $this;
    $list = array();
  
    while (TRUE)
    {
      if ($call->variable instanceof yyCall)
      {
        $list[] = $call;
        $call = $call->variable;
        continue;
      }

      if ( ! (($call = $call->variable->base) instanceof yyCall))
      {
        break;
      }
    }

    foreach (array_reverse($list) as $call)
    {
      if (isset($ifn))
      {
        if ($call->variable instanceof yyCall)
        {
          $call->variable = $ifn;
        }
        else
        {
          $call->variable->base = $ifn;
        }

        $ifn = unfold_soak($options, $call, 'variable');
      }
    }

    return $ifn;
  }
}

?>
