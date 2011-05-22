<?php

namespace CoffeeScript;

function compact(array $array)
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

function del( & $obj, $key)
{
  $val = NULL;

  if (isset($obj[$key]))
  {
    $val = $obj[$key];
    unset($obj[$key]);
  }

  return $val;
}

function extend($obj, $properties)
{
  foreach ($properties as $k => $v)
  {
    $obj->{$k} = $v;
  }

  return $obj;
}

function flatten(array $array)
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

function & last( & $array, $back = 0)
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
 * In Jison, token tags can be represented simply using strings, whereas with
 * ParserGenerator (a port of Lemon) we're stuck using numeric constants for
 * everything.
 *
 * This function maps those string representations to their numeric constants,
 * making it easier to port directly from the CoffeeScript source.
 */
function t($name = NULL)
{
  static $map =  array(
    '.'   => 'ACCESSOR',
    '['   => 'ARRAY_START',
    ']'   => 'ARRAY_END',
    '@'   => 'AT_SIGN',
    '=>'  => 'BOUND_FUNC',
    ':'   => 'COLON',
    ','   => 'COMMA',
    '...' => 'ELLIPSIS',
    '='   => 'EQUALS',
    '?.'  => 'EXISTENTIAL_ACCESSOR',
    '->'  => 'FUNC',
    '&'   => 'LOGIC',
    '&&'  => 'LOGIC',
    '||'  => 'LOGIC',
    '-'   => 'MINUS',
    '{'   => 'OBJECT_START',
    '}'   => 'OBJECT_END',
    '('   => 'PAREN_START',
    ')'   => 'PAREN_END',
    '+'   => 'PLUS',
    '::'  => 'PROTOTYPE'
  );

  if (is_null($name))
  {
    return $map;
  }

  if (func_num_args() > 1)
  {
    $name = func_get_args();
  }

  if (is_array($name) || (func_num_args() > 1 && $name = func_get_args()))
  {
    $tags = array();

    foreach ($name as $v)
    {
      $tags[] = t($v);
    }

    return $tags;
  }

  $name = 'CoffeeScript\Parser::YY_'.(isset($map[$name]) ? $map[$name] : $name);

  return defined($name) ? constant($name) : NULL;
}

/**
 * Change a CoffeeScript PHP token tag to it's equivalent canonical form (the
 * form used in the JavaScript version).
 *
 * This function is used for testing purposes only.
 */
function t_canonical($token)
{
  static $map = array(
    'ACCESSOR'              => '.',

    // These are separate from INDEX_START and INDEX_END.
    'ARRAY_START'           => '[', 
    'ARRAY_END'             => ']',

    'AT_SIGN'               => '@',
    'BOUND_FUNC'            => '=>',
    'COLON'                 => ':',
    'COMMA'                 => ',',
    'ELLIPSIS'              => '...',
    'EQUALS'                => '=',
    'EXISTENTIAL_ACCESSOR'  => '?.',
    'FUNC'                  => '->',
    'OBJECT_START'          => '{',
    'OBJECT_END'            => '}',

    // These are separate from CALL_START and CALL_END.
    'PAREN_START'           => '(',
    'PAREN_END'             => ')',

    'PLUS'                  => '+',
    'PROTOTYPE'             => '::',
  );

  if (is_array($token))
  {
    for ($i = 0; $i < count($token); $i++)
    {
      if (is_array($token[$i]))
      {
        $t = & $token[$i][0];
      }
      else
      {
        $t = & $token[$i];
      }

      $t = t_canonical($t);
    }

    return $token;
  }
  else if (is_numeric($token))
  {
    $token = substr(Parser::tokenName($token), 3);
  }

  return isset($map[$token]) ? $map[$token] : $token;
}

?>
