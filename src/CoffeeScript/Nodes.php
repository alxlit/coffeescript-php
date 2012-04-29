<?php

namespace CoffeeScript;

Init::init();

define('LEVEL_TOP',     1);
define('LEVEL_PAREN',   2);
define('LEVEL_LIST',    3);
define('LEVEL_COND',    4);
define('LEVEL_OP',      5);
define('LEVEL_ACCESS',  6);

define('TAB', '  ');

define('IDENTIFIER_STR',  '[$A-Za-z_\x7f-\x{ffff}][$\w\x7f-\x{ffff}]*');
define('IDENTIFIER',      '/^'.IDENTIFIER_STR.'$/u');
define('IS_STRING',       '/^[\'"]/');
define('SIMPLENUM',       '/^[+-]?\d+$/');
define('METHOD_DEF',      '/^(?:('.IDENTIFIER_STR.')\.prototype(?:\.('.IDENTIFIER_STR.')|\[("(?:[^\\\\"\r\n]|\\\\.)*"|\'(?:[^\\\\\'\r\n]|\\\\.)*\')\]|\[(0x[\da-fA-F]+|\d*\.?\d+(?:[eE][+-]?\d+)?)\]))|('.IDENTIFIER_STR.')$/u');

class Nodes {

  static function multident($code, $tab)
  {
    $code = preg_replace('/\n/', "\n{$tab}", $code);
    return preg_replace('/\s+$/', '', $code);
  }

  static function unfold_soak($options, $parent, $name)
  {
    if ( ! (isset($parent->{$name}) && $parent->{$name} && $ifn = $parent->{$name}->unfold_soak($options)))
    {
      return NULL;
    }

    $parent->{$name} = $ifn->body;
    $ifn->body = yy('Value', $parent);

    return $ifn;
  }

  static function utility($name)
  {
    Scope::$root->assign($ref = "__$name", call_user_func(array(__CLASS__, "utility_$name")));

    return $ref;
  }

  static function utility_bind()
  {
    return 'function(fn, me){ return function(){ return fn.apply(me, arguments); }; }';
  }

  static function utility_extends()
  {
    return 'function(child, parent) { '
    . 'for (var key in parent) { '
    . 'if ('.self::utility('hasProp').'.call(parent, key)) '
    .   'child[key] = parent[key]; '
    . '} '
    . 'function ctor() { '
    .   'this.constructor = child; '
    . '} '
    . 'ctor.prototype = parent.prototype; child.prototype = new ctor; child.__super__ = parent.prototype; '
    . 'return child; '
    . '}';
  }

  static function utility_hasProp()
  {
    return '{}.hasOwnProperty';
  }

  static function utility_indexOf()
  {
    return '[].indexOf || function(item) { for (var i = 0, l = this.length; i < l; i++) { if (i in this && this[i] === item) return i; } return -1; }';
  }

  static function utility_slice()
  {
    return '[].slice';
  }

  /**
   * Since PHP can't return values from __construct, and some of the node
   * classes rely heavily on this feature in JavaScript, we use this function
   * instead of 'new'.
   */
  static function yy($type)
  {
    $args = func_get_args();
    array_shift($args);

    $type = __NAMESPACE__.'\yy_'.$type;

    $inst = new $type;
    $inst = call_user_func_array(array($inst, 'constructor'), $args);

    return $inst;
  }

}

?>
