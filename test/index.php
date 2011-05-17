<?php

require '../src/coffeescript.php';
define('ROOT', realpath(dirname(__FILE__)).'/');

$tests = array('arrays');

?>
<html>
<head>
  <title>CoffeeScript PHP Tests</title>
</head>
<body>
<pre>
<?php

CoffeeScript\Parser::Trace(fopen('trace.txt', 'w'), '> ');

foreach ($tests as $test)
{
  print_r(CoffeeScript\compile(ROOT.$test.'.coffee'));
}

?>
</pre>
</body>
</html>
