<?

ini_set('display_errors', '1');

/**
 * Quick and dirty test driver for CoffeeScript PHP.
 *
 * For each test we check two things, the tokens produced by the lexer/rewriter,
 * and the compiled JavaScript, by comparing them against references produced
 * by the original compiler.
 */

require '../coffeescript/coffeescript.php';
define('CASE_DIR', 'cases/');

$tests    = glob(CASE_DIR.'*.coffee');
$rewrite  = isset($_GET['rewrite']) ? !! $_GET['rewrite'] : TRUE;
$run      = isset($_GET['run']) ? $_GET['run'] : FALSE;

if ($run)
{
  $coffee = file_get_contents($run);
  $error  = FALSE;
  $js     = '';
  $tokens = array();

  try
  {
    $options = array(
      'file'    => $run,
      'rewrite' => $rewrite,
      // 'trace'   => realpath(__DIR__).'/trace.txt',
    );

    $js = CoffeeScript\compile($coffee, $options, $tokens);
  }
  catch (Exception $e)
  {
    $error = get_class($e).': '.ucfirst($e->getMessage());
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
  <title>Test<?= $run ? ' - '.basename($run) : 's' ?></title>
  <style>@import url(css/default.css);</style>

  <? if ($run): ?>
    <script src="js/lib/coffeescript-1.1.1.js"></script>
    <script src="js/lib/diff.js"></script>
    <script src="js/init.js"></script>
    <script src="js/helpers.js"></script>
    <script>
      var PHP = <?= json_encode(array(
        'error'   => $error,
        'js'      => $js, 
        'rewrite' => $rewrite,
        'run'     => $run,
        'tokens'  => $tokens
      )) ?>;
    </script>
  <? endif; ?>
</head>
<body>
  <div id="page">
  <? if ($run): ?>
    <a href="index.php">Back</a>
    <h1><a href="<?= $run ?>"><?= basename($run) ?></a></h1>
    <h2>Code</h2>
    <p>Lines in <del>red</del> are in the reference code, but are missing in ours. Lines in <ins>green</ins> were generated in our code but are not present in the reference.</p>
    <div id="code">
    <? if ($error): ?>
      <p class="error"><?= $error ?></p>
    <? else: ?>
      <p class="fail">Failed.</p>
      <p class="pass">Passed!</p>

      <code>&nbsp;&nbsp;<strong>JS</strong>&nbsp;&nbsp;&nbsp;<strong>PHP</strong></code>
      <code class="result"></code>
    <? endif; ?>
    </div>

    <h2>Lexical Tokens (rewriting <?= $rewrite ? 'on' : 'off' ?>)</h2>
    <div id="tokens">
      <p>Tokens in <del>red</del> are in the reference stack, but are missing in ours. Tokens in <ins>green</ins> were generated in our stack but are not present in the reference.</p>

      <p class="fail">Failed.</p>
      <p class="pass">Passed!</p>

      <code>&nbsp;&nbsp;<strong>JS</strong>&nbsp;&nbsp;&nbsp;<strong>PHP</strong></code>
      <code class="result"></code>
    </div>
  <? else: ?>
    <h1>Tests</h1>
    <p>Pick a test case to run. Make sure that you're using a capable browser.</p>
    <? for ($i = 0; $i < count($tests); $i++): ?>
      <a href="?run=<?= $tests[$i] ?>"><?= basename($tests[$i]) ?></a><br />
    <? endfor; ?>
  <? endif; ?>
  </div>
</body>
</html>
