<?php

namespace CoffeeScript;

require_once 'classes/lexer.php';
require_once 'classes/parser.php';

/**
 * Compile some CoffeeScript.
 *
 * @param  string  The source CoffeeScript code
 * @param  array   Compiler options
 */
function compile($code, $options = array())
{
  $lexer = new Lexer($code, $options);

  if (isset($options['file']))
  {
    // Set the source filename for debugging messages
    Parser::$FILE = $options['file'];
  }

  if (isset($options['tokens']))
  {
    $tokens = & $options['tokens'];
  }

  if (isset($options['trace']))
  {
    // Parser tracing
    Parser::Trace(fopen($options['trace'], 'w', TRUE), '> ');
  }

  $parser = new Parser();

  foreach (($tokens = $lexer->tokenize()) as $token)
  {
    $parser->parse($token);
  }

  return $parser->parse(NULL)->compile($options);
}

?>
