<?php

namespace CoffeeScript;

class yy_Obj extends yy_Base
{
  public $children = array('properties');

  function constructor($props, $generated = FALSE)
  {
    $this->generated = $generated;

    $this->properties = $props ? $props : array();
    $this->objects = $this->properties;

    return $this;
  }

  function assigns($name)
  {
    foreach ($this->properties as $prop)
    {
      if ($prop->assigns($name))
      {
        return TRUE;
      }
    }

    return FALSE;
  }

  function compile_node($options)
  {
    $props = $this->properties;
    $prop_names = array();

    foreach ($this->properties as $prop)
    {
      if ($prop->is_complex())
      {
        $prop = isset($prop->variable) ? $prop->variable : NULL;
      }

      if ($prop)
      {
        $prop_name = $prop->unwrap_all();
        $prop_name = isset($prop_name->value) ? $prop_name->value.'' : NULL;

        if (in_array($prop_name, $prop_names))
        {
          throw new SyntaxError('multiple object literal properties named "'.$prop_name.'"');
        }

        $prop_names[] = $prop_name;
      }
    }

    if ( ! count($props))
    {
      return ($this->front ? '({})' : '{}');
    }

    if ($this->generated)
    {
      foreach ($props as $node)
      {
        if ($node instanceof yy_Value)
        {
          throw new Error('cannot have an implicit value in an implicit object');
        }
      }
    }

    $idt = $options['indent'] .= TAB;
    $last_non_com = $this->last_non_comment($this->properties);

    foreach ($props as $i => $prop)
    {
      if ($i === count($props) - 1)
      {
        $join = '';
      }
      else if ($prop === $last_non_com || $prop instanceof yy_Comment)
      {
        $join = "\n";
      }
      else
      {
        $join = ",\n";
      }

      $indent = $prop instanceof yy_Comment ? '' : $idt;

      if ($prop instanceof yy_Value && (isset($prop->this) && $prop->this))
      {
        $prop = yy('Assign', $prop->properties[0]->name, $prop, 'object');
      }

      if ( ! ($prop instanceof yy_Comment))
      {
        if ( ! ($prop instanceof yy_Assign))
        {
          $prop = yy('Assign', $prop, $prop, 'object');
        }

        if (isset($prop->variable->base))
        {
          $prop->variable->base->as_key = TRUE;
        }
        else
        {
          $prop->variable->as_key = TRUE;
        }
      }

      $props[$i] = $indent.$prop->compile($options, LEVEL_TOP).$join;
    }

    $props = implode('', $props);
    $obj = '{'.($props ? "\n{$props}\n{$this->tab}" : '').'}';

    return ($this->front ? "({$obj})" : $obj);
  }
}

?>
