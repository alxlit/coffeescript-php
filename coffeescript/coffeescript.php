<?php

namespace CoffeeScript;

require_once 'classes/lexer.php';
require_once 'classes/parser.php';

/**
 * Compile some CoffeeScript.
 *
 * Available options:
 *
 *  'file'    => The source file, for debugging (formatted into error messages)
 *  'tokens'  => Reference to token stream
 *  'trace'   => File to write parser trace to
 *
 * @param  string  The source CoffeeScript code
 * @param  array   Options (see above)
 *
 * @return string  The resulting JavaScript (if there were no errors)
 */
function compile($code, $options = array())
{
  $lexer = new Lexer($code, $options);

  if (isset($options['file']))
  {
    Parser::$FILE = $options['file'];
  }

  if (isset($options['tokens']))
  {
    $tokens = & $options['tokens'];
  }

  if (isset($options['trace']))
  {
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
