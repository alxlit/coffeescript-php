<?php

namespace CoffeeScript;

require_once 'helpers.php';

/**
 * Lexical scope manager.
 *
 * Slightly different than the original compiler, variable.type is an array
 * rather than an object, where [0] is the name of the type.
 */
class Scope
{
  static $root = NULL;

  public $shared = NULL;

  function __construct($parent, $expressions, $method)
  {
    $this->parent = $parent;
    $this->expressions = $expressions;
    $this->method = $method;

    $this->variables = array(
      array('name' => 'arguments', 'type' => array('arguments'))
    );

    $this->positions = array();

    if (is_null($this->parent))
    {
      self::$root = $this;
    }
  }

  function add($name, $type, $immediate = FALSE)
  {
    if ($this->shared && ! $immediate)
    {
      return $this->parent->add($name, $type, $immediate);
    }

    if (isset($this->positions[$name]) && is_numeric($pos = $this->positions[$name]))
    {
      $this->variables[$pos]['type'] = array($type);
    }
    else
    {
      $this->variables[] = array('name' => $name, 'type' => array($type));
      $this->positions[$name] = count($this->variables) - 1;
    }
  }

  function assign($name, $value)
  {
    $this->add($name, array('value' => $value, 'assigned' => TRUE));
    $this->has_assignments(TRUE);
  }

  function assigned_variables()
  {
    $tmp = array();

    foreach ($this->variables as $v)
    {
      if (isset($v['type']['assigned']) && $v['type']['assigned'])
      {
        $tmp[] = "{$v['name']} = {$v['type'][0]}";
      }
    }

    return $tmp;
  }

  function check($name, $immediate = FALSE)
  {
    $found = !! $this->type($name);

    if ($found || $immediate)
    {
      return $found;
    }

    return $this->parent ? !! $this->parent->check($name) : FALSE;
  }

  function declared_variables()
  {
    $real_vars = array();
    $temp_vars = array();

    foreach ($this->variables as $v)
    {
      if ($v['type'][0] === 'var')
      {
        if ($v['name']{0} === '_')
        {
          $temp_vars[] = $v['name'];
        }
        else
        {
          $real_vars[] = $v['name'];
        }
      }
    }

    asort($real_vars);
    asort($temp_vars);

    return array_merge($real_vars, $temp_vars);
  }

  function find($name, $options = array())
  {
    if ($this->check($name, $options))
    {
      return TRUE;
    }

    $this->add($name, 'var');

    return FALSE;
  }

  function free_variable($type)
  {
    $index = 0;

    while ($this->check(($temp = $this->temporary($type, $index)), TRUE))
    {
      $index++;
    }

    $this->add($temp, 'var', TRUE);

    return $temp;
  }

  function has_assignments($set = NULL)
  {
    static $value = FALSE;

    if ( ! is_null($set))
    {
      $value = $set;
    }

    return $value;
  }

  function has_declarations()
  {
    return !! count($this->declared_variables());
  }

  function parameter($name)
  {
    if ($this->shared && $this->parent->check($name, TRUE))
    {
      return;
    }

    $this->add($name, 'param');
  }

  function temporary($name, $index)
  {
    if (strlen($name) > 1)
    {
      return '_'.$name.($index > 1 ? $index : '');
    }
    else
    {
      $val = strval(base_convert($index + intval($name, 36), 10, 36));
      $val = preg_replace('/\d/', 'a', $val);

      return '_'.$val;
    }
  }

  function type($name)
  {
    foreach ($this->variables as $v)
    {
      if ($v['name'] === $name)
      {
        return $v['type'];
      }
    }

    return NULL;
  }
}

?>
