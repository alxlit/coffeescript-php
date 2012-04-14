<?php

namespace CoffeeScript;

define('VERSION', '1.3.1');

class Init {

  /**
   * Dummy function that doesn't actually do anything, it's just used to make
   * sure that this file gets loaded.
   */
  static function init() {}

  /**
   * This function may be used in lieu of an autoloader.
   */
  static function load($root = NULL)
  {
    if ($root === NULL)
    {
      $root = realpath(dirname(__FILE__));
    }

    $files = array(
      'Compiler',
      'Error',
      'Helpers',
      'Lexer',
      'Nodes',
      'Parser',
      'Rewriter',
      'Scope',
      'SyntaxError',
      'Value',

      'yy/Base',  // load the base class first
      'yy/While', // For extends While

      'yy/Access',
      'yy/Arr',
      'yy/Assign',
      'yy/Block',
      'yy/Call',
      'yy/Class',
      'yy/Closure',
      'yy/Code',
      'yy/Comment',
      'yy/Existence',
      'yy/Extends',
      'yy/For',
      'yy/If',
      'yy/In',
      'yy/Index',
      'yy/Literal',
      'yy/Obj',
      'yy/Op',
      'yy/Param',
      'yy/Parens',
      'yy/Range',
      'yy/Return',
      'yy/Slice',
      'yy/Splat',
      'yy/Switch',
      'yy/Throw',
      'yy/Try',
      'yy/Value',
    );

    foreach ($files as $file)
    {
      require_once "$root/$file.php";
    }
  }

}

//
// Function shortcuts. These are all used internally.
//

function compact(array $array) { return Helpers::compact($array); }
function del( & $obj, $key) { return Helpers::del($obj, $key); }
function extend($obj, $properties) { return Helpers::extend($obj, $properties); }
function flatten(array $array) { return Helpers::flatten($array); }
function & last( & $array, $back = 0) { return Helpers::last($array, $back); }
function wrap($v) { return Helpers::wrap($v); }
function t() { return call_user_func_array('CoffeeScript\Lexer::t', func_get_args()); }
function t_canonical() { return call_user_func_array('CoffeeScript\Lexer::t_canonical', func_get_args()); }
function multident($code, $tab) { return Nodes::multident($code, $tab); }
function unfold_soak($options, $parent, $name) { return Nodes::unfold_soak($options, $parent, $name); }
function utility($name) { return Nodes::utility($name); }
function yy() { return call_user_func_array('CoffeeScript\Nodes::yy', func_get_args()); }

?>
