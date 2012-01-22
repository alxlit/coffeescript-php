
function init(PHP) {
  var JS = {}, d;

  // Tokenize
  JS.tokens = CoffeeScript.tokens(PHP.coffee, { rewrite: PHP.rewrite });

  // Tokens diff
  d = JsDiff.diffLines(formatTokens(JS.tokens), formatTokens(PHP.tokens));

  console.log(d);

  $('#tokens .result').innerHTML = formatDiffLines(d);
  $('#tokens .' + (d.length > 1 || d[0].removed ? 'fail' : 'pass')).style.display = 'block';

  // Compile
  JS.js = CoffeeScript.require['./parser'].parse(JS.tokens).compile();

  // Code diff
  d = JsDiff.diffLines(JS.js, PHP.js);

  $('#code .result').innerHTML = formatDiffLines(d);
  $('#code .' + (d.length > 1 || d[0].removed ? 'fail' : 'pass')).style.display = 'block';
}

function $(elem) {
  return typeof elem == 'string' ? document.querySelector(elem) : elem;
}

function formatDiffLines(diff) {
  var html = '', line = [1, 1];

  for (var i = 0; i < diff.length; i++) {
    var d = diff[i], v = trim(d.value).split('\n');

    for (var j = 0; j < v.length; j++) {
      if (d.added) {
        html += lpad(5) + lpad(line[1]++, 4) + ' +';
        v[j] = '<ins>' + v[j] + '</ins>';
      }
      else if (d.removed) {
        html += lpad(line[0]++, 4) + lpad(5) + ' -';
        v[j] = '<del>' + v[j] + '</del>';
      }
      else {
        html += lpad(line[0]++, 4) + lpad(line[1]++, 5) + '  ';
      }

      html += lpad(1) + v[j] + '<br />';
    }
  }

  return html;
}

function formatTokens(tokens) {
  var properties = [0, 1, 2, 'call', 'fromThen', 'generated', 'newLine', 'spaced'];
  var result = [], html = '';

  for (var i = 0; i < tokens.length - 1; i++) {
    var token = [];

    for (var j = 0; j < properties.length; j++) {
      var k = properties[j], v = tokens[i][k];

      if (typeof v == 'string') {
        v = v.replace(/\n/g, '\\n');
      }

      if (typeof k == 'string') {
        if ( !! v) {
          token.push(k);
        }
      }
      else {
        token.push('"' + v + '"');
      }
    }

    result.push('[' + token.join(', ') + ']');
  }

  return result.join('\n');
}

// Add padding to the left of str until str.length is length.
function lpad(str, length) {
  if (arguments.length === 1) {
    length = str;
    str = '';
  }

  var d = length - (str + '').length;

  while (d-- > 0) {
    str = ' ' + str;
  }

  return str;
}

// Remove writespace from both sides
function trim(str) { return str.replace(/^\n+|\n+$/g,''); }

