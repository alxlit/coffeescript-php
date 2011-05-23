<?php

namespace CoffeeScript;

require 'classes/lexer.php';
require 'classes/parser.php';

/**
 */
function compile($source, $options = array(), & $tokens = NULL)
{
  $lexer  = new Lexer(file_get_contents($source), $options);
  $parser = new Parser();

  Parser::$FILE = $source;

  foreach (($tokens = $lexer->tokenize()) as $token)
  {
   $parser->parse($token);
  }

  // Signal end-of-input to the parser.
  $parser->parse(NULL);

  // return $parser->yystack;
}

?>
