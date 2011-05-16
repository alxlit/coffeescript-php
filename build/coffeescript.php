<?php

namespace CoffeeScript;

require 'coffeescript/lexer.php';
require 'coffeescript/parser.php';

/**
 */
function compile($source, $options = array())
{
  $options = (object) array_merge(array(
    'indent'  => 0,
    'index'   => 0,
    'line'    => 0,
    'rewrite' => TRUE
  ),
  $options);

  $lexer  = new Lexer(file_get_contents($source), $options);
  $parser = new Parser();

  // While not strictly necessary to do it like this, it's in keeping with the
  // Lemon documentation to do so.
  while ($lexer->tokenize())
  {
    $parser->parse($lexer->tag(), $lexer->value());
  }

  // Signal end-of-input to the parser.
  $parser->parse(0, 0);

  return $parser->yystack;
}

?>
