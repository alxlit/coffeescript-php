<?php

define('ROOT', realpath(dirname(__FILE__)).'/');

// Disable output.
ini_set('implicit_flush', false);

function build()
{
}

function build_tests()
{
}

function clean()
{
}

// Set options.
$_SERVER['argv'] = $argv = array('-s', ROOT.'src/grammar.y');

// ParserGenerator is included locally since the PEAR package doesn't seem to
// work. Also, some minor changes have been made to the source.
require 'vendor/ParserGenerator/ParserGenerator.php';

// Catch any errors.
ob_start();

$lemon = new PHP_ParserGenerator;
$lemon->main();

$reply = ob_get_contents();

ob_end_clean();

if (substr($reply, -1) !== "\n")
{
  // Sometimes the errors aren't printed with newlines.
  $reply .= "\n";
}

if (($i = strpos($reply, $argv[1])) > -1)
{
  $reply = explode("\n", $reply);

  foreach ($reply as $i => $line)
  {
    // The -q flag doesn't seem to work, but we want only the error messages
    // to be output to the terminal.
    if (strpos($line, $argv[1]) === 0)
    {
      echo str_replace($argv[1], basename($argv[1]), $line) . "\n";
    }
  }

  exit(1);
}
else
{
  $source = ROOT.'src/grammar.php';
  $target = ROOT.'build/coffeescript/parser.php';

  // Build was a success!
  if ( ! file_exists($target) || @unlink($target))
  {
    $content = file_get_contents($source);

    // Add namespace declaration.
    $content = str_replace('<?php', "<?php\nnamespace CoffeeScript;\nuse \ArrayAccess as ArrayAccess;", $content);

    // Write.
    file_put_contents($target, $content);

    // Clean up.
    unlink($source);
    unlink(ROOT.'src/grammar.out');

    exit(0);
  }
  else
  {
    // Bad permissions.
    echo "Couldn't remove {$dest}. Check your user permissions.\n";

    exit(1);
  }
}

?>
