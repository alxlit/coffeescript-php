<?php

namespace CoffeeScript;

require 'classes/lexer.php';
require 'classes/parser.php';

/**
 * Compile some CoffeeScript.
 *
 * @param   $code     The source CoffeeScript code.
 * @param   $options  Compiler options.
 */
function compile($code, $options = array(), & $tokens = NULL)
{
  $lexer = new Lexer($code, $options);

  if (isset($options['file']))
  {
    Parser::$FILE = $options['file'];
  }

  if (isset($options['trace']))
  {
    Parser::Trace(fopen($options['trace'], TRUE), '> ');
  }

  $parser = new Parser();

  foreach (($tokens = $lexer->tokenize()) as $token)
  {
    $parser->parse($token);
  }

  // Done parsing.
  return $parser->parse(NULL)->compile($options);
}

?>
