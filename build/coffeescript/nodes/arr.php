<?php

namespace CoffeeScript;

class yyArr extends yyBase
{
  public $children = array('objects');

  function __construct($objs)
  {
    $this->objects = $objs ? $objs : array();
  }

  function assigns($name)
  {
    foreach ($this->objects as $obj)
    {
      if ($obj->assigns($name))
      {
        return TRUE;
      }
    }

    return FALSE;
  }

  function compile_node($options)
  {
    if ( ! count($this->options))
    {
      return '[]';
    }

    $options['indent'] .= TAB;
    $objs = $this->filter_implicit_objects($this->objects);

    if (($code = Splat::compile_splatted_array($options, $objs)))
    {
      return $code;
    }

    $code = array();

    foreach ($objs as $obj)
    {
      $code[] = $obj->compile($options);
    }

    $code = implode(', ', $code);

    if (strpos("\n", $code) >= 0)
    {
      return "[\n{$options['indent']}{$code}\n{$this->tab}]";
    }
    else
    {
      return "[{$code}]";
    }
  }

  function filter_implicit_objects()
  {
    return call_user_func_array(array(new yyCall, __METHOD__), func_get_args());
  }
}

?>
