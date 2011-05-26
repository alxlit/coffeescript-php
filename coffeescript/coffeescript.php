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

  if (isset($options['trace']))
  {
    Parser::Trace(fopen($options['trace'], TRUE), '> ');
  }

  foreach (($tokens = $lexer->tokenize()) as $token)
  {
    $parser->parse($token);
  }

  // Done parsing.
  return $parser->parse(NULL)->compile($options);
}

?>
