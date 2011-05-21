<?

// ----------------------------------------------------------------------------
// Quick and dirty test driver for CoffeeScript PHP; for each test case we can
// check two things, the tokens produced by the lexer/rewriter, and the
// compiled code.
// ----------------------------------------------------------------------------

error_reporting(E_ALL);
set_time_limit(30);
define('ROOT', realpath(dirname(__FILE__)).'/');
require '../coffeescript/coffeescript.php';

// Test cases.
$tests = array('arrays');

// Test case to run.
$run = isset($_GET['run']) ? $_GET['run'].'.coffee' : FALSE;

if ($run)
{
  $error = FALSE;

  $code = '';
  $tokens = array();

  try
  {
    $code = CoffeeScript\compile($run, array(), $tokens);
  }
  catch (Exception $e) 
  {
    $code = get_class($e).': '.ucfirst($e->getMessage());
    $error = TRUE; 
  }

  if ($tokens)
  {
    $map = array_flip(CoffeeScript\t());

    foreach ($tokens as & $tok)
    {
      $tok[0] = substr(CoffeeScript\Parser::tokenName($tok[0]), 3);

      // Change back to canonical form.
      $tok[0] = isset($map[$tok[0]]) ? $map[$tok[0]] : $tok[0];
    }
  }
}

?>
<html>
<head>
  <title>CoffeeScript PHP Tests</title>
  <? if ($run): ?>
  <script src="js/lib/coffee-script.js"></script>
  <script src="js/lib/diff.js"></script>
  <script src="js/helpers.js"></script>
  <script>
    window.addEventListener('load', function() {
      get('<?= $run ?>', function(code) {
        var tmp = tokenize(code);

        // Diff between the reference and our result.
        var diff = JsDiff.diffLines(
              formatTokens(tmp), 
              formatTokens(<?= json_encode(array('tokens' => $tokens)) ?>.tokens)
            );

        write('tokens', formatLineDiff(diff));
      });
    },
    false);
  </script>
  <? endif; ?>
  <style>
    body { font: 12.8px 'Arial', sans-serif; margin-bottom: 100px; }
    code { display: block; font: 13px 'Inconsolata', monospace; overflow: auto; }
    h1, h2 { clear: both; }
    ins { background-color: rgba(0, 255, 0, 0.25); }
    del { background-color: rgba(255, 0, 0, 0.25); }
  </style>
</head>
<body>
  <div style="overflow: auto;">
  <? if ($run): ?>
    <h1>Test: <?= $run ?></h1>

    <h2>Code</h2>
    <p><?= $code ?>.</p>

    <h2>Lexical Tokens</h2>
    <p>Lines in red are in the reference stack of tokens, but missing in ours. Likewise, lines in
    green were generated in our stack but are not present in the reference.</p>
    <code id="tokens" style=""></code>
  <? else: ?>
    <h1>Tests</h1>
    <p>Pick a test case to run. Make sure that you're using a capable browser.</p>
    <? foreach ($tests as $test): ?>
      <a href="?run=<?= $test ?>"><?= ucfirst($test); ?></a><br />
    <? endforeach; ?>
  <? endif; ?>
  </div>
</body>
</html>
