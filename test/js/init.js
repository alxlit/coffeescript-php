function init() {
  get(PHP.run, function(code) {
    var diff;

    var tokens = tokenize(code, PHP.rewrite);
    
    diff = JsDiff.diffLines(formatTokens(tokens), formatTokens(PHP.tokens));
    $('#tokens code.result').innerHTML = formatLineDiff(diff);
    
    show('#tokens .' + (diff.length > 1 || diff[0].removed ? 'fail' : 'pass'));

    var js = compile(tokens);

    diff = JsDiff.diffLines(js, PHP.js);
    $('#code code.result').innerHTML = formatLineDiff(diff);

    show('#code .' + (diff.length > 1 || diff[0].removed ? 'fail' : 'pass'));
  });
}

window.addEventListener('load', init, false);
