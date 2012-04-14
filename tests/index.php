<?

/**
 * Quick and dirty test driver for CoffeeScript PHP.
 *
 * For each test we check two things, the tokens produced by the lexer/rewriter,
 * and the compiled JavaScript, by comparing them against references produced
 * by the original compiler.
 */

ini_set('display_errors', '1');
error_reporting(E_ALL);

// Test case to run
$case = isset($_GET['case']) ? $_GET['case'] : FALSE;

if ($case)
{
  $PHP = array(
    'coffee'  => file_get_contents($case),
    'error'   => NULL,
    'js'      => NULL,
    'rewrite' => ! (isset($_GET['rewrite']) && $_GET['rewrite'] === 'off'),
    'tokens'  => array()
  );

  require '../src/CoffeeScript/Init.php';
  CoffeeScript\Init::load();

  $options = array(
    'filename' => $case,
    'header'   => FALSE,
    'rewrite'  => $PHP['rewrite'],
    'tokens'   => & $PHP['tokens'],
  );

  try
  {
    $PHP['js'] = CoffeeScript\Compiler::compile($PHP['coffee'], $options);
  }
  catch (Exception $e)
  {
    $PHP['error'] = $e->getMessage();
  }

  if ($PHP['tokens'])
  {
    // Change the tokens to their canonical form so we can compare them against
    // those produced by the reference.
    $PHP['tokens'] = CoffeeScript\Lexer::t_canonical($PHP['tokens']);
  }
}
?>
<!doctype html>
<html>
<head>
  <link type="text/css" href="css/style.css" rel="stylesheet" />

  <title>Tests <?= $case ? "($case)" : '' ?></title>

  <? if ($case): ?>
    <script src="js/lib/coffeescript_1.3.1.js"></script>
    <script src="js/lib/diff.js"></script>
    <script src="js/main.js"></script>
    <script>window.addEventListener('load', function() { init(<?= json_encode($PHP) ?>); }, false);</script>
  <? endif; ?>
</head>
<body>
  <div id="page">

    <? if ($case): ?>

      <a href="index.php">Back</a>
      <h1><a href="<?= $case ?>"><?= basename($case) ?></a></h1>

      <h2>Code</h2>

      <p>Lines in <del>red</del> are in the reference code, but are missing in ours. Lines in
      <ins>green</ins> were generated in our code but are not present in the reference.</p>

      <div id="code">
        <? if (isset($error)): ?>
          <p class="error"><?= $error ?></p>
        <? else: ?>
          <p class="fail">Failed.</p>
          <p class="pass">Passed!</p>

          <pre>  <strong>JS</strong>   <strong>PHP</strong></pre>
          <pre class="result"></pre></code>
        <? endif; ?>
      </div>

      <h2>Lexical Tokens (rewriting <?= $PHP['rewrite'] ? 'on' : 'off' ?>)</h2>

      <div id="tokens">
        <p>Tokens in <del>red</del> are in the reference stack, but are missing in ours. Tokens in
        <ins>green</ins> were generated in our stack but are not present in the reference.</p>

        <p class="fail">Failed.</p>
        <p class="pass">Passed!</p>

        <pre>  <strong>JS</strong>   <strong>PHP</strong></pre>
        <pre class="result"></pre>
      </div>

    <? else: ?>

      <h1>Tests</h1>
      <p>Pick a test case to run. Make sure that you're using a capable browser.</p>

      <? foreach ((array) glob('cases/*.coffee') as $case): ?>
        <a href="?case=<?= $case ?>"><?= basename($case) ?></a><br />
      <? endforeach; ?>

    <?endif; ?>

  </div>
</body>
</html>
