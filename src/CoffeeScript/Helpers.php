<?php

namespace CoffeeScript;

Init::init();

class Helpers {

  /**
   * In some cases it seems to be unavoidable that method parameters conflict
   * with their parent class's. This workaround is used to silence E_STRICT
   * errors.
   *
   * @param $args      `func_get_args()`
   * @param $required  The number of required arguments.
   * @param $optional  An array of defaults for optional arguments.
   */
  static function args(array $args, $required, array $optional = NULL)
  {
    if (count($args) < $required)
    {
      throw new Error("Function requires at least $required arguments.");
    }

    return array_merge($args, (array) $optional);
  }

  static function compact(array $array)
  {
    $compacted = array();

    foreach ($array as $k => $v)
    {
      if ($v)
      {
        $compacted[] = $v;
      }
    }

    return $compacted;
  }

  static function del( & $obj, $key)
  {
    $val = NULL;

    if (isset($obj[$key]))
    {
      $val = $obj[$key];
      unset($obj[$key]);
    }

    return $val;
  }

  static function extend($obj, $properties)
  {
    foreach ($properties as $k => $v)
    {
      $obj->{$k} = $v;
    }

    return $obj;
  }

  static function flatten(array $array)
  {
    $flattened = array();

    foreach ($array as $k => $v)
    {
      if (is_array($v))
      {
        $flattened = array_merge($flattened, flatten($v));
      }
      else
      {
        $flattened[] = $v;
      }
    }

    return $flattened;
  }

  static function & last( & $array, $back = 0)
  {
    static $NULL;

    $i = count($array) - $back - 1;

    if (isset($array[$i]))
    {
      return $array[$i];
    }
    else
    {
      // Make sure $NULL is really NULL.
      $NULL = NULL;

      return $NULL;
    }
  }


  /**
   * Wrap a primitive with an object, so that properties can be attached to it
   * like in JavaScript.
   */
  static function wrap($v)
  {
    return new Value($v);
  }

}

?>
