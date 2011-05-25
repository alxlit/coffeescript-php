<?

// ----------------------------------------------------------------------------
// Quick and dirty test driver for CoffeeScript PHP; for each test case we can
// check two things, the tokens produced by the lexer/rewriter, and the
// compiled code. Note that the actual tests are not run.
// ----------------------------------------------------------------------------

error_reporting(E_ALL);
set_time_limit(30);
define('ROOT', realpath(dirname(__FILE__)).'/');

require '../coffeescript/coffeescript.php';

// Test cases.
$tests = glob(ROOT.'cases/*.coffee');
foreach ($tests as & $test) { $test = basename($test); }

// Test case to run.
$run = isset($_GET['run']) ? $_GET['run'] : FALSE;

// Enable rewriting.
$rewrite = isset($_GET['rewrite']) ? (bool) $_GET['rewrite'] : TRUE;

if ($run)
{
  $error = FALSE;

  $code = '';
  $tokens = array();

  CoffeeScript\Parser::Trace(fopen('trace.txt', 'w'), '> ');

  try
  {
    $code = CoffeeScript\compile('cases/'.$run, array('rewrite' => $rewrite), $tokens);
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
  <title>Tests <?= $run ? ' - '.$run : '' ?></title>
  <? if ($run): ?>
    <script src="js/lib/coffeescript-1.1.1.js"></script>
    <script src="js/lib/diff.js"></script>
    <script src="js/helpers.js"></script>
    <script>
      window.addEventListener('load', function() {
        get('cases/<?= $run ?>', function(code) {
          var tmp = tokenize(code, <?= (int) $rewrite; ?>);

          // Diff between the reference and our result.
          var diff = JsDiff.diffLines(
                formatTokens(tmp), 
                formatTokens(<?= json_encode(array('tokens' => $tokens)) ?>.tokens)
              );

          write('tokens', formatLineDiff(diff));

          // Result of the test.
          show('tokens-' + (diff.length > 1 || diff[0].removed ? 'fail' : 'pass'));
        });
      },
      false);
    </script>
  <? endif; ?>
  <style>
    body { background: #fff; font: 12.8px 'Arial', sans-serif; margin-bottom: 100px; }
    code { display: block; font: 13px 'Inconsolata', monospace; overflow: auto; }
    h1, h2 { clear: both; }
    ins { background: rgba(0, 255, 0, 0.25); text-decoration: none; }
    del { background: rgba(255, 0, 0, 0.25); text-decoration: none; }
    .error { background: rgba(255, 0, 0, 0.25); border: 1px solid #800000; padding: 10px; }
    .fail { color: red; display: none; font-weight: bold; }
    .pass { color: green; display: none; font-weight: bold; }
  </style>
</head>
<body>
  <div style="overflow: auto;">
  <? if ($run): ?>
    <a href="index.php">Back</a>
    <h1>Test: <a href="<?= $run ?>"><?= $run ?></a></h1>
    <h2>Code</h2>
    <p <? if ($error): ?>class="error"<? endif; ?>><?= $code ?></p>

    <h2>Lexical Tokens (rewriting <?= $rewrite ? 'on' : 'off' ?>)</h2>
    <p>Tokens in <del>red</del> are in the reference stack, but are missing in ours. Tokens in <ins>green</ins> were generated in our stack but are not present in the reference.</p>

    <p id="tokens-fail" class="fail">Failed.</p>
    <p id="tokens-pass" class="pass">Passed!</p>

    <code>&nbsp;&nbsp;<strong>JS</strong>&nbsp;&nbsp;&nbsp;<strong>PHP</strong></code>
    <code id="tokens" style=""></code>
  <? else: ?>
    <h1>Tests</h1>
    <p>Pick a test case to run. Make sure that you're using a capable browser.</p>
    <? for ($i = 0; $i < count($tests); $i++): ?>
      <a href="?run=<?= $tests[$i] ?>"><?= $tests[$i]; ?></a><br />
    <? endfor; ?>
  <? endif; ?>
  </div>
</body>
</html>
