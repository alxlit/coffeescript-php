function init() {
  get(PHP.run, function(code) {
    var tokens = tokenize(code, PHP.rewrite);
    var diff;

    diff = JsDiff.diffLines(formatTokens(tokens), formatTokens(PHP.tokens));

    $('#tokens code').innerHTML = formatLineDiff(diff);

    // Result of the test.
    show('#tokens .' + (diff.length > 1 || diff[0].removed ? 'fail' : 'pass'));

    var js = compile(tokens);

    diff = JsDiff.diffLines(js, PHP.js);

    $('#code code').innerHTML = formatLineDiff(diff);

    show('#code .' + (diff.length > 1 || diff[0].removed ? 'fail' : 'pass'));
  });
}

window.addEventListener('load', init, false);
