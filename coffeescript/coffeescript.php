<?php

namespace CoffeeScript;

require 'classes/lexer.php';
require 'classes/parser.php';

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

  Parser::$FILE = $source;

  foreach ($lexer->tokenize() as $token)
  {
    $parser->parse($token);
  }

  // Signal end-of-input to the parser.
  $parser->parse(0, 0);

  return $lexer->tokens;
  // return $parser->yystack;
}

?>
