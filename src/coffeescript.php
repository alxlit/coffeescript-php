<?php

namespace CoffeeScript;

require 'coffeescript/lexer.php';
require 'coffeescript/parser.php';

/**
 */
function compile($source, $options = array())
{
  $options = array_merge(array(
    'indent'  => 0,
    'index'   => 0,
    'line'    => 0,
    'rewrite' => TRUE
  ),
  $options);

  $lexer  = new Lexer(file_get_contents($source), $options);
  $parser = new Parser();

  Parser::Trace(fopen('/srv/http/coffeescript-php/trace.txt', 'w'), '> ');

  foreach ($lexer->tokenize() as $token)
  {
    $parser->parse($token[0], $token[1]);
  }

  // Signal end-of-input to the parser.
  $parser->parse(0, 0);

  return $parser->yystack;
}

?>
