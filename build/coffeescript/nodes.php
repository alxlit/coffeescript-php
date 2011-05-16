<?php

namespace CoffeeScript;

require_once 'helpers.php';
require_once 'scope.php';

define('LEVEL_TOP',     1);
define('LEVEL_PAREN',   2);
define('LEVEL_LIST',    3);
define('LEVEL_COND',    4);
define('LEVEL_OP',      5);
define('LEVEL_ACCESS',  6);

define('TAB', '  ');

define('TRAILING_WHITESPACE',   '/[ \t]+$/gm');
define('IDENTIFIER',            '/^[$A-Za-z_\x7f-\uffff][$\w\x7f-\uffff]*$/');
define('IS_STRING',             '/^[\'"]/');
define('SIMPLENUM',             '/^[+-]?\d+$/');

$UTILITIES = array(
  'bind'    => 
    'function(fn, me){ return function(){ return fn.apply(me, arguments); }; }',

  'extends' =>
    'function(child, parent) {'
  . '  for (var key in parent) { if (__hasProp.call(parent, key)) child[key] = parent[key]; }'
  . '  function ctor() { this.constructor = child; }'
  . '  ctor.prototype = parent.prototype;'
  . '  child.prototype = new ctor;'
  . '  child.__super__ = parent.prototype;'
  . '  return child;'
  . '}',

  'hasProp' => 'Object.prototype.hasOwnProperty',

  'indexOf' =>
    'Array.prototype.indexOf || function(item) {'
  . '  for (var i = 0, l = this.length; i < l; i++) {'
  . '    if (this[i] === item) return i;'
  . '  }'
  . '  return -1;'
  . '}',

  'slice' => 'Array.prototype.slice'
);

function multident($code, $tab)
{
  return preg_replace('/\n/g', "\n$tab");
}

function unfold_soak($options, $parent, $name)
{
  if ( ! ($ifn = $parent[$name]->unfold_soak($options)))
  {
    return;
  }

  $parent[$name] = $ifn->body;
  $ifn->body = new yyValue($parent);

  return $ifn;
}

function utility($name)
{
  global $UTILITIES;

  Scope::$root->assign($ref = "__$name", $UTILITIES[$name]);

  return $ref;
}

// Base class.
require_once 'nodes/base.php';

$nodes = array(
  'access',
  'arr',
  'assign',
  'block',
  'call',
  'class',
  'closure',
  'code',
  'comment',
  'existence',
  'extends',
  'for',
  'if',
  'in',
  'index',
  'literal',
  'obj',
  'param',
  'parens',
  'push',
  'range',
  'return',
  'slice',
  'splat',
  'switch',
  'throw',
  'try',
  'value',
  'while'
);

foreach ($nodes as $node)
{
  require_once "nodes/{$node}.php";
}

?>
