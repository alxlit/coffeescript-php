<?php

define('ROOT', realpath(dirname(__FILE__)).'/');

// Disable output.
ini_set('implicit_flush', false);

function clean()
{
}

function init()
{
  $arg = array_slice($_SERVER['argv'], 1);
  $arg = $arg ? $arg[0] : '';

  if (in_array($arg, array('?', 'help')))
  {
    echo "make.php [all|test]\n";
    exit(1);
  }

  $all = $arg === 'all';

  if ($all || $arg === '')      { make(); }
  if ($all || $arg === 'test')  { make_test(); }

  clean();
}

function make()
{
  // Included locally because the PEAR package doesn't seem to work. Also, some
  // minor changes were made to the source and template.
  require 'vendor/ParserGenerator/ParserGenerator.php';

  $source = 'grammar';
  $target = 'coffeescript/classes/parser.php';

  // Lemon takes arguments on the command line.
  $_SERVER['argv'] = $argv = array('-s', ROOT.$source.'.y');

  echo "Attempting to build \"{$target}\" from \"{$source}.y\".\n";
  echo "This could take a few minutes...\n";

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

  // Check for errors.
  if (strpos($reply, $argv[1]) > -1)
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

  // Build was a success!
  if ( ! file_exists(ROOT.$target) || @unlink(ROOT.$target))
  {
    $content = file_get_contents(ROOT.$source.'.php');

    // Add namespace declaration.
    $content = str_replace('<?php', "<?php\nnamespace CoffeeScript;\nuse \ArrayAccess as ArrayAccess;", $content);

    // Write.
    file_put_contents(ROOT.$target, $content);

    echo "Success!\n";

    // Clean up.
    unlink(ROOT.$source.'.php');
    unlink(ROOT.$source.'.out');

    exit(0);
  }
  else
  {
    // Bad permissions.
    echo "Failed!\n";
    echo "Couldn't remove {$target}. Check your permissions.\n";

    exit(1);
  }
}

function make_test()
{
}

init();

?>
