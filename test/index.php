<?php

require '../build/coffeescript.php';

define('ROOT', realpath(dirname(__FILE__)).'/');

$tests = array(
  'arrays'
);
?>
<html>
<head>
  <title>CoffeeScript PHP Tests</title>
</head>
<body>
<?php

foreach ($tests as $test)
{
  print('<pre>');
  print_r(CoffeeScript\compile(ROOT.$test.'.coffee'));
  print('</pre>');
}

?>
</body>
</html>
