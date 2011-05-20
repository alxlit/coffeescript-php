<?php

require '../coffeescript/coffeescript.php';
define('ROOT', realpath(dirname(__FILE__)).'/');

$tests = array('arrays');

?>
<html>
<head>
  <title>CoffeeScript PHP Tests</title>
  <style>body { font: 14px 'Inconsolata', monospace; }</style>
</head>
<body>
<?php

CoffeeScript\Parser::Trace(fopen('debug/trace', 'w'), '> ');

foreach ($tests as $test)
{
  try 
  {
    $tokens = CoffeeScript\compile(ROOT.$test.'.coffee');
  }
  catch (Exception $e)
  {
    echo '<p>'.get_class($e).': '.ucfirst($e->getMessage()).'.</p>';
    break;
  }

  echo '<pre>'.print_r($tokens, TRUE).'</pre>';
}

?>
</body>
</html>
