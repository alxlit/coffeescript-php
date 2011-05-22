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
    // Change the token tags to their canonical string representations, so that
    // we can compare them to the reference.
    $tokens = CoffeeScript\t_canonical($tokens);
  }
}

?>
<!doctype html>
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
    body { background: #fff; font: 12.8px 'Arial', sans-serif; margin-bottom: 100px; }
    code { display: block; font: 13px 'Inconsolata', monospace; overflow: auto; }
    h1, h2 { clear: both; }
    ins { background: rgba(0, 255, 0, 0.25); }
    del { background: rgba(255, 0, 0, 0.25); }
    .error { background: rgba(255, 0, 0, 0.25); border: 1px solid #800000; padding: 10px; }
  </style>
</head>
<body>
  <div style="overflow: auto;">
  <? if ($run): ?>
    <h1>Test: <a href="<?= $run ?>"><?= $run ?></a></h1>

    <h2>Code</h2>
    <p <? if ($error): ?>class="error"<? endif; ?>><?= $code ?>.</p>

    <h2>Lexical Tokens</h2>
    <p>Tokens in <del>red</del> are in the reference stack, but are missing in ours. Tokens in
    <ins>green</ins> were generated in our stack but are not present in the reference.</p>
    <code>&nbsp;&nbsp;<strong>JS</strong>&nbsp;&nbsp;&nbsp;<strong>PHP</strong></code>
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
