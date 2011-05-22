function $(id) {
  return typeof id == 'string' ? document.getElementById(id) : id;
}

function compile(tokens) {
  if (typeof tokens == 'string') {
    tokens = tokenize(tokens);
  }

  return CoffeeScript.Parser.parse(tokens).compile();
}

function count(str, substr) {
  return CoffeeScript.count(str, substr);
}

function formatLineDiff(diff) {
  var html = '', line = [1, 1];

  for (var i = 0; i < diff.length; i++) {
    var d; 

    d = diff[i];
    d.value = trim(d.value).split('\n');

    for (var j = 0; j < d.value.length; j++) {
      if (d.added) {
        html += lpad('', 5) + lpad(line[1]++, 4) + '&nbsp;+';
        d.value[j] = '<ins>' + d.value[j] + '</ins>';
      }
      else if (d.removed) {
        html += lpad(line[0]++, 4) + lpad('', 5) + '&nbsp;-';
        d.value[j] = '<del>' + d.value[j] + '</del>';
      }
      else {
        html += lpad(line[0]++, 4) + '&nbsp;' + lpad(line[1]++, 4);
        html += '&nbsp;&nbsp;';
      }

      html += '&nbsp;' + d.value[j] + '<br />';
    }
  }

  return html;
}

function formatTokens(tokens) {
  var properties = [0, 1, 2, 'call', 'fromThen', 'generated', 'newLine', 'noNewlines', 'reserved', 'spaced'];
  var result = [], html = '';

  for (var i = 0; i < tokens.length - 1; i++) {
    var token = [];

    for (var j = 0; j < properties.length; j++) {
      var k = properties[j], v = tokens[i][k];

      if (v === '\n') {
        v = '\\n';
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

function get(url, callback) {
  var req = new XMLHttpRequest();

  req.onreadystatechange = function() {
    if (req.readyState == 4) {
      callback(req.responseText);
    }
  };

  req.open('GET', url);
  req.send(null);
}

function getJSON(url, callback) {
}

function lpad(str, length) {
  var d = length - (str + '').length;
  while (d-- > 0) { str = '&nbsp;' + str; }

  return str;
}

function tokenize(code) {
  return CoffeeScript.tokens(code);
}

function trim(str) {
  return str.replace(/^\s+|\s+$/g,'');
}

function write(elem, html, replaceNewlines) {
  $(elem).innerHTML = replaceNewlines ? html.replace(/\n/g, '<br />') : html;
}

