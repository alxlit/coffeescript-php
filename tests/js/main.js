
function init(PHP) {
  var JS = {}, d;

  // Tokenize
  JS.tokens = CoffeeScript.tokens(PHP.coffee, { rewrite: PHP.rewrite });

  // Tokens diff
  d = JsDiff.diffLines(formatTokens(JS.tokens), formatTokens(PHP.tokens));

  $('#tokens .result').innerHTML = formatDiffLines(d);
  $('#tokens .' + (d.length > 1 || d[0].removed ? 'fail' : 'pass')).style.display = 'block';

  // Compile
  JS.js = CoffeeScript.require['./parser'].parse(JS.tokens).compile();

  // Code diff
  d = JsDiff.diffLines(JS.js, PHP.js);

  var failed = d.length > 1 || d[0].removed;

  $('#code .result').innerHTML = formatDiffLines(d);

  var result = $('#code .' + (d.length > 1 || d[0].removed ? 'fail' : 'pass'));
  result.style.display = 'block';

  if (PHP.error) {
    result.innerHTML += '<br /><span style="font-weight: normal;">' + PHP.error + '</span>';
  }
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
  var html = [], props = [0, 1, 2, 'call', 'fromThen', 'generated', 'newLine', 'spaced'];

  for (var i = 0; i < tokens.length; i++) {
    var token = [];

    for (var j = 0; j < props.length; j++) {
      var key = props[j], value = tokens[i] && tokens[i][key];

      if (typeof value == 'string') {
        value = value.replace(/\n/g, '\\n');
      }

      if (typeof key == 'string') {
        if ( !! value) {
          token.push(k);
        }
      }
      else {
        if (key === 1) {
          var tmp = '', _props = ['generated', 'reserved'];

          for (var k = 0; k < _props.length; k++) {
            if (value && value[ _props[k] ]) {
              tmp += ' ' + _props[k];
            }
          }

          if (tmp) {
            value = '< ' + value + tmp + ' >';
          }
        }

        token.push('"' + value + '"');
      }
    }

    html.push('[' + token.join(', ') + ']');
  }

  return html.join('\n');
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

