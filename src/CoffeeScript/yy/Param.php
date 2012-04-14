<?php

namespace CoffeeScript;

class yy_Param extends yy_Base
{
  public $children = array('name', 'value');

  function constructor($name, $value = NULL, $splat = NULL)
  {
    $this->name = $name;
    $this->value = $value;
    $this->splat = $splat;

    $name = $this->name->unwrap_all();
    $name = isset($name->value) ? $name->value : NULL;

    if (in_array($name, Lexer::$STRICT_PROSCRIBED))
    {
      throw new SyntaxError("parameter name \"$name\" is not allowed");
    }

    return $this;
  }

  function as_reference($options)
  {
    if (isset($this->reference) && $this->reference)
    {
      return $this->reference;
    }

    $node = $this->name;

    if (isset($node->this) && $node->this)
    {
      $node = $node->properties[0]->name;

      if (isset($this->value->reserved) && $this->value->reserved)
      {
        $node = yy('Literal', $options['scope']->free_variable($node->value));
      }
    }
    else if ($node->is_complex())
    {
      $node = yy('Literal', $options['scope']->free_variable('arg'));
    }

    $node = yy('Value', $node);

    if ($this->splat)
    {
      $node = yy('Splat', $node);
    }

    return ($this->reference = $node);
  }

  function compile($options, $level = NULL)
  {
    return $this->name->compile($options, LEVEL_LIST);
  }

  function is_complex()
  {
    return $this->name->is_complex();
  }

  function names($name = NULL)
  {
    if ($name === NULL)
    {
      $name = $this->name;
    }

    $at_param = function($obj)
    {
      $value = $obj->properties[0]->name;

      return isset($value->reserved) && $value->reserved ? array() : array($value);
    };

    if ($name instanceof yy_Literal)
    {
      return array($name->value);
    }

    if ($name instanceof yy_Value)
    {
      return $at_param($name);
    }

    $names = array();

    foreach ($name->objects as $obj)
    {
      if ($obj instanceof yy_Assign)
      {
        $names[] = $obj->variable->base->value;
      }
      else if ($obj->is_array() || $obj->is_object())
      {
        $names = array_merge($names, (array) $this->names($obj->base));
      }
      else if (isset($obj->this) && $obj->this)
      {
        $names = array_merge($names, (array) $at_param($obj));
      }
      else
      {
        $names[] = $obj->base->value;
      }
    }

    return $names;
  }
}

?>
