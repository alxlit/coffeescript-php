<?php

namespace CoffeeScript;

Init::init();

/**
 * CoffeeScript lexer. For the most part it's directly from the original
 * source code, though there are some relatively minor differences in how it
 * works with the parser (since we're using Lemon).
 */
class Lexer
{
  static $COFFEE_ALIASES = array(
    'and'  => '&&',
    'or'   => '||',
    'is'   => '==',
    'isnt' => '!=',
    'not'  => '!',
    'yes'  => 'true',
    'no'   => 'false',
    'on'   => 'true',
    'off'  => 'false'
  );

  static $COFFEE_KEYWORDS = array(
    'by',
    'loop',
    'of',
    'then',
    'undefined',
    'unless',
    'until',
    'when'
  );

  // exports.RESERVED.
  static $COFFEE_RESERVED = array();

  static $JS_KEYWORDS = array(
    'break',
    'catch',
    'class',
    'continue',
    'debugger',
    'delete',
    'do',
    'else',
    'extends',
    'false',
    'finally',
    'for',
    'if',
    'in',
    'instanceof',
    'new',
    'null',
    'this',
    'throw',
    'typeof',
    'return',
    'switch',
    'super',
    'true',
    'try',
    'while',
  );

  // RESERVED.
  static $JS_RESERVED = array(
    '__bind',
    '__extends',
    '__hasProp',
    '__indexOf',
    '__slice',
    'case',
    'const',
    'default',
    'enum',
    'export',
    'function',
    'implements',
    'import',
    'interface',
    'let',
    'native',
    'package',
    'protected',
    'private',
    'public',
    'static',
    'var',
    'void',
    'with',
    'yield',
  );

  static $STRICT_PROSCRIBED = array('arguments', 'eval');

  static $JS_FORBIDDEN = array();

  static $CODE              = '/^[-=]>/';
  static $COMMENT           = '/^###([^#][\s\S]*?)(?:###[^\n\S]*|(?:###)?$)|^(?:\s*#(?!##[^#]).*)+/';
  static $HEREDOC           = '/^("""|\'\'\')([\s\S]*?)(?:\n[^\n\S]*)?\1/';
  static $HEREDOC_INDENT    = '/\n+([^\n\S]*)/';
  static $HEREDOC_ILLEGAL   = '%\*/%';
  static $HEREGEX           = '%^/{3}([\s\S]+?)/{3}([imgy]{0,4})(?!\w)%';
  static $HEREGEX_OMIT      = '/\s+(?:#.*)?/';
  static $IDENTIFIER        = '/^([$A-Za-z_\x7f-\x{ffff}][$\w\x7f-\x{ffff}]*)([^\n\S]*:(?!:))?/u';
  static $JSTOKEN           = '/^`[^\\\\`]*(?:\\\\.[^\\\\`]*)*`/';
  static $LINE_CONTINUER    = '/^\s*(?:,|\??\.(?![.\d])|::)/';
  static $MULTI_DENT        = '/^(?:\n[^\n\S]*)+/';
  static $MULTILINER        = '/\n/';
  static $NUMBER            = '/^0b[01]+|^0o[0-7]+|^0x[\da-f]+|^\d*\.?\d+(?:e[+-]?\d+)?/i';
  static $OPERATOR          = '#^(?:[-=]>|[-+*/%<>&|^!?=]=|>>>=?|([-+:])\1|([&|<>])\2=?|\?\.|\.{2,3})#';
  static $REGEX             = '%^(/(?![\s=])[^[/\n\\\\]*(?:(?:\\\\[\s\S]|\[[^\]\n\\\\]*(?:\\\\[\s\S][^\]\n\\\\]*)*\])[^[/\n\\\\]*)*/)([imgy]{0,4})(?!\w)%';
  static $SIMPLESTR         = '/^\'[^\\\\\']*(?:\\\\.[^\\\\\']*)*\'/i';
  static $TRAILING_SPACES   = '/\s+$/';
  static $WHITESPACE        = '/^[^\n\S]+/';

  static $BOOL              = array('TRUE', 'FALSE', 'NULL', 'UNDEFINED');
  static $CALLABLE          = array('IDENTIFIER', 'STRING', 'REGEX', ')', ']', '}', '?', '::', '@', 'THIS', 'SUPER');
  static $COMPARE           = array('==', '!=', '<', '>', '<=', '>=');
  static $COMPOUND_ASSIGN   = array('-=', '+=', '/=', '*=', '%=', '||=', '&&=', '?=', '<<=', '>>=', '>>>=', '&=', '^=', '|=' );
  static $INDEXABLE         = array('NUMBER', 'BOOL');
  static $LINE_BREAK        = array('INDENT', 'OUTDENT', 'TERMINATOR');
  static $LOGIC             = array('&&', '||', '&', '|', '^');
  static $MATH              = array('*', '/', '%');
  static $NOT_REGEX         = array('NUMBER', 'REGEX', 'BOOL', '++', '--', ']');
  static $NOT_SPACED_REGEX  = array(')', '}', 'THIS', 'IDENTIFIER', 'STRING');
  static $RELATION          = array('IN', 'OF', 'INSTANCEOF');
  static $SHIFT             = array('<<', '>>', '>>>');
  static $UNARY             = array('!', '~', 'NEW', 'TYPEOF', 'DELETE', 'DO');

  static $INVERSES          = array();

  static $initialized = FALSE;

  /**
   * Initialize some static variables (called at the end of this file).
   */
  static function init()
  {
    if (self::$initialized) return;

    self::$initialized = TRUE;

    self::$COFFEE_KEYWORDS  = array_merge(self::$COFFEE_KEYWORDS, array_keys(self::$COFFEE_ALIASES));
    self::$COFFEE_RESERVED  = array_merge(self::$JS_RESERVED, self::$JS_KEYWORDS, self::$COFFEE_KEYWORDS, self::$STRICT_PROSCRIBED);
    self::$JS_FORBIDDEN     = array_merge(self::$JS_KEYWORDS, self::$JS_RESERVED, self::$STRICT_PROSCRIBED);
    self::$INDEXABLE        = array_merge(self::$CALLABLE, self::$INDEXABLE);
    self::$NOT_SPACED_REGEX = array_merge(self::$NOT_REGEX, self::$NOT_SPACED_REGEX);

    Rewriter::init();

    self::$INVERSES         = Rewriter::$INVERSES;
  }

  /**
   * In Jison, token tags can be represented simply using strings, whereas with
   * ParserGenerator (a port of Lemon) we're stuck using numeric constants for
   * everything.
   *
   * This static function maps those string representations to their numeric constants,
   * making it easier to port directly from the CoffeeScript source.
   */
  static function t($name)
  {
    static $map =  array(
      '.'   => 'ACCESSOR',
      '['   => 'ARRAY_START',
      ']'   => 'ARRAY_END',
      '@'   => 'AT_SIGN',
      '=>'  => 'BOUND_FUNC',
      ':'   => 'COLON',
      ','   => 'COMMA',
      '--'  => 'DECREMENT',
      '='   => 'EQUALS',
      '?'   => 'EXISTENTIAL',
      '?.'  => 'EXISTENTIAL_ACCESSOR',
      '->'  => 'FUNC',
      '++'  => 'INCREMENT',
      '&'   => 'LOGIC',
      '&&'  => 'LOGIC',
      '||'  => 'LOGIC',
      '-'   => 'MINUS',
      '{'   => 'OBJECT_START',
      '}'   => 'OBJECT_END',
      '('   => 'PAREN_START',
      ')'   => 'PAREN_END',
      '+'   => 'PLUS',
      '::'  => 'PROTOTYPE',
      '...' => 'RANGE_EXCLUSIVE',
      '..'  => 'RANGE_INCLUSIVE',
    );

    if (is_array($name) || (func_num_args() > 1 && $name = func_get_args()))
    {
      $tags = array();

      foreach ($name as $v)
      {
        $tags[] = t($v);
      }

      return $tags;
    }

    $name = 'CoffeeScript\Parser::YY_'.(isset($map[$name]) ? $map[$name] : $name);

    // Don't return the original name if there's no matching constant, in some
    // cases intermediate token types are created and the value returned by this
    // static function still needs to be unique.
    return defined($name) ? constant($name) : $name;
  }

  /**
   * Change a CoffeeScript PHP token tag to it's equivalent canonical form (the
   * form used in the JavaScript version).
   *
   * This static function is used for testing purposes only.
   */
  static function t_canonical($token)
  {
    static $map = array(
      'ACCESSOR'              => '.',

      // These are separate from INDEX_START and INDEX_END.
      'ARRAY_START'           => '[',
      'ARRAY_END'             => ']',

      'AT_SIGN'               => '@',
      'BOUND_FUNC'            => '=>',
      'COLON'                 => ':',
      'COMMA'                 => ',',
      'DECREMENT'             => '--',
      'EQUALS'                => '=',
      'EXISTENTIAL'           => '?',
      'EXISTENTIAL_ACCESSOR'  => '?.',
      'FUNC'                  => '->',
      'INCREMENT'             => '++',
      'MINUS'                 => '-',
      'OBJECT_START'          => '{',
      'OBJECT_END'            => '}',

      // These are separate from CALL_START and CALL_END.
      'PAREN_START'           => '(',
      'PAREN_END'             => ')',

      'PLUS'                  => '+',
      'PROTOTYPE'             => '::',
      'RANGE_EXCLUSIVE'       => '...',
      'RANGE_INCLUSIVE'       => '..'
    );

    if (is_array($token))
    {
      if (is_array($token[0]))
      {
        foreach ($token as & $t)
        {
          $t = t_canonical($t);
        }
      }
      else
      {
        // Single token.
        $token[0] = t_canonical($token[0]);

        if (is_object($token[1]))
        {
          $str = "< {$token[1]} ";

          foreach ($token[1] as $k => $v)
          {
            if ($k !== 'v' && $v)
            {
              $str.= $k.' ';
            }
          }

          $token[1] = $str.'>';
        }
      }

      return $token;
    }
    else if (is_numeric($token))
    {
      $token = substr(Parser::tokenName($token), 3);
    }
    else if (is_string($token))
    {
      // The token type isn't known to the parser, so t() returned a unique
      // string to use instead.
      $token = substr($token, strlen('CoffeeScript\Parser::YY_'));
    }

    return isset($map[$token]) ? $map[$token] : $token;
  }


  function __construct($code, $options)
  {
    self::init();

    if (preg_match(self::$WHITESPACE, $code))
    {
      $code = "\n{$code}";
    }

    $code = preg_replace(self::$TRAILING_SPACES, '', str_replace("\r", '', $code));

    $options = array_merge(array(
      'indent'  => 0,
      'index'   => 0,
      'line'    => 0,
      'rewrite' => TRUE
    ),
    $options);

    $this->code     = $code;
    $this->chunk    = $code;
    $this->ends     = array();
    $this->indent   = 0;
    $this->indents  = array();
    $this->indebt   = 0;
    $this->index    = $options['index'];
    $this->length   = strlen($this->code);
    $this->line     = $options['line'];
    $this->outdebt  = 0;
    $this->options  = $options;
    $this->tokens   = array();
  }

  function balanced_string($str, $end)
  {
    $continue_count = 0;

    $stack = array($end);
    $prev = NULL;

    $len = strlen($str);

    for ($i = 1; $i < $len; $i++)
    {
      if ($continue_count)
      {
        --$continue_count;
        continue;
      }

      switch ($letter = $str{$i})
      {
      case '\\':
        ++$continue_count;
        continue 2;

      case $end:
        array_pop($stack);

        if (count($stack) === 0)
        {
          return substr($str, 0, $i + 1);
        }

        $end = $stack[count($stack) - 1];
        continue 2;
      }

      if ($end === '}' && ($letter === '"' || $letter === "'"))
      {
        $stack[] = $end = $letter;
      }
      else if ($end === '}' && $letter === '/' && (preg_match(self::$HEREGEX, substr($str, $i), $match) || preg_match(self::$REGEX, substr($str, $i), $match)))
      {
        $continue_count += strlen($match[0]) - 1;
      }
      else if ($end === '}' && $letter === '{')
      {
        $stack[] = $end = '}';
      }
      else if ($end === '"' && $prev === '#' && $letter === '{')
      {
        $stack[] = $end = '}';
      }

      $prev = $letter;
    }

    $this->error('missing '.array_pop($stack).', starting');
  }

  function close_indentation()
  {
    $this->outdent_token($this->indent);
  }

  function comment_token()
  {
    if ( ! preg_match(self::$COMMENT, $this->chunk, $match))
    {
      return 0;
    }

    $comment = $match[0];

    if (isset($match[1]) && ($here = $match[1]))
    {
      $this->token('HERECOMMENT', $this->sanitize_heredoc($here, array(
        'herecomment' =>  TRUE,
        'indent'      =>  str_pad('', $this->indent)
      )));
    }

    $this->line += substr_count($comment, "\n");

    return strlen($comment);
  }

  function error($message)
  {
    throw new SyntaxError($message.' on line '.($this->line + 1));
  }

  function escape_lines($str, $heredoc = NULL)
  {
    return preg_replace(self::$MULTILINER, $heredoc ? '\\n' : '', $str);
  }

  function heredoc_token()
  {
    if ( ! preg_match(self::$HEREDOC, $this->chunk, $match))
    {
      return 0;
    }

    $heredoc = $match[0];
    $quote = $heredoc{0};
    $doc = $this->sanitize_heredoc($match[2], array('quote' => $quote, 'indent' => NULL));

    if ($quote === '"' && strpos($doc, '#{') !== FALSE)
    {
      $this->interpolate_string($doc, array('heredoc' => TRUE));
    }
    else
    {
      $this->token('STRING', $this->make_string($doc, $quote, TRUE));
    }

    $this->line += substr_count($heredoc, "\n");

    return strlen($heredoc);
  }

  function heregex_token($match)
  {
    list($heregex, $body, $flags) = $match;

    if (strpos($body, '#{') === FALSE)
    {
      $re = preg_replace(self::$HEREGEX_OMIT, '', $body);
      $re = preg_replace('/\//', '\\/', $re);

      if (preg_match('/^\*/', $re))
      {
        $this->error('regular expressions cannot begin with `*`');
      }

      $this->token('REGEX', '/'.($re ? $re : '(?:)').'/'.$flags);

      return strlen($heregex);
    }

    $this->token('IDENTIFIER', 'RegExp');
    $this->tokens[] = array(t('CALL_START'), '(');

    $tokens = array();

    foreach ($this->interpolate_string($body, array('regex' => TRUE)) as $token)
    {
      list($tag, $value) = $token;

      if ($tag === 'TOKENS')
      {
        $tokens = array_merge($tokens, (array) $value);
      }
      else
      {
        if ( ! ($value = preg_replace(self::$HEREGEX_OMIT, '', $value)))
        {
          continue;
        }

        $value = preg_replace('/\\\\/', '\\\\\\\\', $value);
        $tokens[] = array(t('STRING'), $this->make_string($value, '"', TRUE));
      }

      $tokens[] = array(t('+'), '+');
    }

    array_pop($tokens);

    if ( ! (isset($tokens[0]) && $tokens[0][0] === 'STRING'))
    {
      array_push($this->tokens, array(t('STRING'), '""'), array(t('+'), '+'));
    }

    $this->tokens = array_merge($this->tokens, $tokens);

    if ($flags)
    {
      array_push($this->tokens, array(t(','), ','), array(t('STRING'), "\"{$flags}\""));
    }

    $this->token(')', ')');

    return strlen($heregex);
  }

  function identifier_token()
  {
    if ( ! preg_match(self::$IDENTIFIER, $this->chunk, $match))
    {
      return 0;
    }

    list($input, $id) = $match;

    $colon = isset($match[2]) ? $match[2] : NULL;

    if ($id === 'own' && $this->tag() === t('FOR'))
    {
      $this->token('OWN', $id);

      return strlen($id);
    }

    $forced_identifier = $colon || ($prev = last($this->tokens)) &&
      (in_array($prev[0], t('.', '?.', '::')) ||
      ( ! (isset($prev['spaced']) && $prev['spaced']) && $prev[0] === t('@')));

    $tag = 'IDENTIFIER';

    if ( ! $forced_identifier and (in_array($id, self::$JS_KEYWORDS) || in_array($id, self::$COFFEE_KEYWORDS)))
    {
      $tag = strtoupper($id);

      if ($tag === 'WHEN' && in_array($this->tag(), t(self::$LINE_BREAK)))
      {
        $tag = 'LEADING_WHEN';
      }
      else if ($tag === 'FOR')
      {
        $this->seen_for = TRUE;
      }
      else if ($tag === 'UNLESS')
      {
        $tag = 'IF';
      }
      else if (in_array($tag, self::$UNARY))
      {
        $tag = 'UNARY';
      }
      else if (in_array($tag, self::$RELATION))
      {
        if ($tag !== 'INSTANCEOF' && (isset($this->seen_for) && $this->seen_for))
        {
          $tag = 'FOR'.$tag;
          $this->seen_for = FALSE;
        }
        else
        {
          $tag = 'RELATION';

          if ($this->value() === '!')
          {
            array_pop($this->tokens);
            $id = '!'. $id;
          }
        }
      }
    }

    if (in_array($id, self::$JS_FORBIDDEN, TRUE))
    {
      if ($forced_identifier)
      {
        $id = wrap($id);
        $id->reserved = TRUE;

        $tag = 'IDENTIFIER';
      }
      else if (in_array($id, self::$JS_RESERVED, TRUE))
      {
        $this->error("reserved word $id");
      }
    }

    if ( ! $forced_identifier)
    {
      if (isset(self::$COFFEE_ALIASES[$id]))
      {
        $id = self::$COFFEE_ALIASES[$id];
      }

      $map = array(
        'UNARY'     => array('!'),
        'COMPARE'   => array('==', '!='),
        'LOGIC'     => array('&&', '||'),
        'BOOL'      => array('true', 'false', 'null', 'undefined'),
        'STATEMENT' => array('break', 'continue')
      );

      foreach ($map as $k => $v)
      {
        if (in_array($id, $v))
        {
          $tag = $k;
          break;
        }
      }
    }

    $this->token($tag, $id);

    if ($colon)
    {
      $this->token(':', ':');
    }

    return strlen($input);
  }

  function interpolate_string($str, array $options = array()) // #{0}
  {
    $options = array_merge(array(
      'heredoc'   => '',
      'regex'     => NULL
    ),
    $options);

    $tokens = array();
    $pi = 0;
    $i = -1;

    while ( isset($str{++$i}) )
    {
      $letter = $str{$i};

      if ($letter === '\\')
      {
        $i++;
        continue;
      }

      if ( ! ($letter === '#' && (substr($str, $i + 1, 1) === '{') &&
        ($expr = $this->balanced_string(substr($str, $i + 1), '}'))) )
      {
        continue;
      }

      if ($pi < $i)
      {
        $tokens[] = array('NEOSTRING', substr($str, $pi, $i - $pi));
      }

      $inner = substr($expr, 1, -1);

      if (strlen($inner))
      {
        $lexer = new Lexer($inner, array(
          'line'    => $this->line,
          'rewrite' => FALSE,
        ));

        $nested = $lexer->tokenize();

        array_pop($nested);

        if (isset($nested[0]) && $nested[0][0] === t('TERMINATOR'))
        {
          array_shift($nested);
        }

        if ( ($length = count($nested)) )
        {
          if ($length > 1)
          {
            array_unshift($nested, array(t('('), '(', $this->line));
            $nested[] = array(t(')'), ')', $this->line);
          }

          $tokens[] = array('TOKENS', $nested);
        }
      }

      $i += strlen($expr);
      $pi = $i + 1;
    }

    if ($i > $pi && $pi < strlen($str))
    {
      $tokens[] = array('NEOSTRING', substr($str, $pi));
    }

    if ($options['regex'])
    {
      return $tokens;
    }

    if ( ! count($tokens))
    {
      return $this->token('STRING', '""');
    }

    if ( ! ($tokens[0][0] === 'NEOSTRING'))
    {
      array_unshift($tokens, array('', ''));
    }

    if ( ($interpolated = count($tokens) > 1) )
    {
      $this->token('(', '(');
    }

    for ($i = 0; $i < count($tokens); $i++)
    {
      list($tag, $value) = $tokens[$i];

      if ($i)
      {
        $this->token('+', '+');
      }

      if ($tag === 'TOKENS')
      {
        $this->tokens = array_merge($this->tokens, $value);
      }
      else
      {
        $this->token('STRING', $this->make_string($value, '"', $options['heredoc']));
      }
    }

    if ($interpolated)
    {
      $this->token(')', ')');
    }

    return $tokens;
  }

  function js_token()
  {
    if ( ! ($this->chunk{0} === '`' && preg_match(self::$JSTOKEN, $this->chunk, $match)))
    {
      return 0;
    }

    $this->token('JS', substr($script = $match[0], 1, -1));

    return strlen($script);
  }

  function line_token()
  {
    if ( ! preg_match(self::$MULTI_DENT, $this->chunk, $match))
    {
      return 0;
    }

    $indent = $match[0];
    $this->line += substr_count($indent, "\n");
    $this->seen_for = FALSE;

    // $prev = & last($this->tokens, 1);
    $size = strlen($indent) - 1 - strrpos($indent, "\n");

    $no_newlines = $this->unfinished();

    if (($size - $this->indebt) === $this->indent)
    {
      if ($no_newlines)
      {
        $this->suppress_newlines();
      }
      else
      {
        $this->newline_token();
      }

      return strlen($indent);
    }

    if ($size > $this->indent)
    {
      if ($no_newlines)
      {
        $this->indebt = $size - $this->indent;
        $this->suppress_newlines();

        return strlen($indent);
      }

      $diff = $size - $this->indent + $this->outdebt;

      $this->token('INDENT', $diff);
      $this->indents[] = $diff;
      $this->ends[] = 'OUTDENT';
      $this->outdebt = $this->indebt = 0;
    }
    else
    {
      $this->indebt = 0;
      $this->outdent_token($this->indent - $size, $no_newlines);
    }

    $this->indent = $size;

    return strlen($indent);
  }

  function literal_token()
  {
    if (preg_match(self::$OPERATOR, $this->chunk, $match))
    {
      list($value) = $match;

      if (preg_match(self::$CODE, $value))
      {
        $this->tag_parameters();
      }
    }
    else
    {
      $value = $this->chunk{0};
    }

    $tag = t($value);
    $prev = & last($this->tokens);

    if ($value === '=' && $prev)
    {
      if ( ! (isset($prev[1]->reserved) && $prev[1]->reserved) && in_array(''.$prev[1], self::$JS_FORBIDDEN))
      {
        $this->error('reserved word "'.$this->value().'" can\'t be assigned');
      }

      if (in_array($prev[1], array('||', '&&')))
      {
        $prev[0] = t('COMPOUND_ASSIGN');
        $prev[1] .= '=';

        return 1;
      }
    }

    if ($value === ';')
    {
      $this->seen_for = FALSE;
      $tag = t('TERMINATOR');
    }
    else if (in_array($value, self::$MATH))
    {
      $tag = t('MATH');
    }
    else if (in_array($value, self::$COMPARE))
    {
      $tag = t('COMPARE');
    }
    else if (in_array($value, self::$COMPOUND_ASSIGN))
    {
      $tag = t('COMPOUND_ASSIGN');
    }
    else if (in_array($value, self::$UNARY))
    {
      $tag = t('UNARY');
    }
    else if (in_array($value, self::$SHIFT))
    {
      $tag = t('SHIFT');
    }
    else if (in_array($value, self::$LOGIC) || $value === '?' && (isset($prev['spaced']) && $prev['spaced']))
    {
      $tag = t('LOGIC');
    }
    else if ($prev && ! (isset($prev['spaced']) && $prev['spaced']))
    {
      if ($value === '(' && in_array($prev[0], t(self::$CALLABLE)))
      {
        if ($prev[0] === t('?'))
        {
          $prev[0] = t('FUNC_EXIST');
        }

        $tag = t('CALL_START');
      }
      else if ($value === '[' && in_array($prev[0], t(self::$INDEXABLE)))
      {
        $tag = t('INDEX_START');

        if ($prev[0] === t('?'))
        {
          $prev[0] = t('INDEX_SOAK');
        }
      }
    }

    if (in_array($value, array('(', '{', '[')))
    {
      $this->ends[] = self::$INVERSES[$value];
    }
    else if (in_array($value, array(')', '}', ']')))
    {
      $this->pair($value);
    }

    $this->token($tag, $value);

    return strlen($value);
  }

  function make_string($body, $quote, $heredoc = NULL)
  {
    if (!strlen($body))
    {
      return $quote.$quote;
    }

    $body = preg_replace_callback('/\\\\([\s\S])/', function($match) use ($quote)
    {
      $contents = $match[1];

      if (in_array($contents, array("\n", $quote)))
      {
        return $contents;
      }

      return $match[0];
    },
    $body);

    $body = preg_replace('/'.$quote.'/', '\\\\$0', $body);

    return $quote.$this->escape_lines($body, $heredoc).$quote;
  }

  function newline_token()
  {
    while ($this->value() === ';')
    {
      array_pop($this->tokens);
    }

    if ($this->tag() !== t('TERMINATOR'))
    {
      $this->token('TERMINATOR', "\n");
    }
  }

  function number_token()
  {
    if ( ! preg_match(self::$NUMBER, $this->chunk, $match))
    {
      return 0;
    }

    $number = $match[0];

    if (preg_match('/^0[BOX]/', $number))
    {
      $this->error("radix prefix '$number' must be lowercase");
    }
    else if (preg_match('/E/', $number) && ! preg_match('/^0x/', $number))
    {
      $this->error("exponential notation '$number' must be indicated with a lowercase 'e'");
    }
    else if (preg_match('/^0\d*[89]/', $number))
    {
      $this->error("decimal literal '$number' must not be prefixed with '0'");
    }
    else if (preg_match('/^0\d+/', $number))
    {
      $this->error("octal literal '$number' must be prefixed with 0o");
    }

    $lexed_length = strlen($number);

    if (preg_match('/^0o([0-7]+)/', $number, $octal_literal))
    {
      $number = '0x'.base_convert(intval($octal_literal[1], 8), 8, 16);
    }

    if (preg_match('/^0b([01]+)/', $number, $binary_literal))
    {
      $number = '0x'.base_convert(intval($binary_literal[1], 2), 2, 16);
    }

    $this->token('NUMBER', $number);

    return $lexed_length;
  }

  function outdent_token($move_out, $no_newlines = FALSE)
  {
    while ($move_out > 0)
    {
      $len = count($this->indents) - 1;

      if ( ! isset($this->indents[$len]))
      {
        $move_out = 0;
      }
      else if ($this->indents[$len] === $this->outdebt)
      {
        $move_out -= $this->outdebt;
        $this->outdebt = 0;
      }
      else if ($this->indents[$len] < $this->outdebt)
      {
        $this->outdebt -= $this->indents[$len];
        $move_out -= $this->indents[$len];
      }
      else
      {
        $dent = array_pop($this->indents) - $this->outdebt;
        $move_out -= $dent;
        $this->outdebt = 0;
        $this->pair('OUTDENT');
        $this->token('OUTDENT', $dent);
      }
    }

    if (isset($dent) && $dent)
    {
      $this->outdebt -= $move_out;
    }

    while ($this->value() == ';')
    {
      array_pop($this->tokens);
    }

    if ( ! ($this->tag() === t('TERMINATOR') || $no_newlines))
    {
      $this->token('TERMINATOR', "\n");
    }

    return $this;
  }

  function pair($tag)
  {
    if ( ! ($tag === ($wanted = last($this->ends))))
    {
      if ($wanted !== 'OUTDENT')
      {
        $this->error("unmateched $tag");
      }

      $this->indent -= $size = last($this->indents);
      $this->outdent_token($size, TRUE);

      return $this->pair($tag);
    }

    return array_pop($this->ends);
  }

  function regex_token()
  {
    if ($this->chunk{0} !== '/')
    {
      return 0;
    }

    if (preg_match(self::$HEREGEX, $this->chunk, $match))
    {
      $length = $this->heregex_token($match);
      $this->line += substr_count($match[0], "\n");

      return $length;
    }

    $prev = last($this->tokens);

    if ($prev)
    {
      if (in_array($prev[0], t((isset($prev['spaced']) && $prev['spaced']) ? 
        self::$NOT_REGEX : self::$NOT_SPACED_REGEX)))
      {
        return 0;
      }
    }

    if ( ! preg_match(self::$REGEX, $this->chunk, $match))
    {
      return 0;
    }

    list($match, $regex, $flags) = $match;

    if (substr($regex, 0, -1) === '/*')
    {
      $this->error('regular expressions cannot begin with `*`');
    }

    $regex = $regex === '//' ? '/(?:)/' : $regex;

    $this->token('REGEX', "{$regex}{$flags}");

    return strlen($match);
  }

  function sanitize_heredoc($doc, array $options)
  {
    $herecomment = isset($options['herecomment']) ? $options['herecomment'] : NULL;
    $indent = isset($options['indent']) ? $options['indent'] : NULL;

    if ($herecomment)
    {
      if (preg_match(self::$HEREDOC_ILLEGAL, $doc))
      {
        $this->error('block comment cannot contain "*/*, starting');
      }

      if ( ! strpos($doc, "\n"))
      {
        return $doc;
      }
    }
    else
    {
      $offset = 0;

      while (preg_match(self::$HEREDOC_INDENT, $doc, $match, PREG_OFFSET_CAPTURE, $offset))
      {
        $attempt = $match[1][0];
        $offset = strlen($match[0][0]) + $match[0][1];

        if ( is_null($indent) || (strlen($indent) > strlen($attempt) && strlen($attempt) > 0))
        {
          $indent = $attempt;
        }
      }
    }

    if ($indent)
    {
      $doc = preg_replace('/\n'.$indent.'/', "\n", $doc);
    }

    if ( ! $herecomment)
    {
      $doc = preg_replace('/^\n/', '', $doc);
    }

    return $doc;
  }

  function string_token()
  {
    switch ($this->chunk{0})
    {
    case "'":
      if ( ! preg_match(self::$SIMPLESTR, $this->chunk, $match))
      {
        return 0;
      }

      $this->token('STRING', preg_replace(self::$MULTILINER, "\\\n", $string = $match[0]));
      break;

    case '"':
      if ( ! ($string = $this->balanced_string($this->chunk, '"')))
      {
        return 0;
      }

      if (strpos($string, '#{', 1) > 0)
      {
        $this->interpolate_string(substr($string, 1, -1));
      }
      else
      {
        $this->token('STRING', $this->escape_lines($string));
      }

      break;

    default:
      return 0;
    }

    if (preg_match('/^(?:\\\\.|[^\\\\])*\\\\[0-7]/', $string, $octal_esc))
    {
      $this->error("octal escape sequences $string are not allowed");
    }

    $this->line += substr_count($string, "\n");

    return strlen($string);
  }

  function suppress_newlines()
  {
    if ($this->value() === '\\')
    {
      array_pop($this->tokens);
    }
  }

  function tag($index = 0, $tag = NULL)
  {
    $token = & last($this->tokens, $index);

    if ( ! is_null($tag))
    {
      $token[0] = $tag;
    }

    return $token[0];
  }

  function tag_parameters()
  {
    if ($this->tag() !== t(')'))
    {
      return $this;
    }

    $stack = array();
    $tokens = &$this->tokens;

    $i = count($tokens);
    $tokens[--$i][0] = t('PARAM_END');

    while ( ($tok = &$tokens[--$i]) )
    {
      if ($tok[0] === t(')'))
      {
        $stack[] = $tok;
      }
      else if (in_array($tok[0], t('(', 'CALL_START')))
      {
        if (count($stack))
        {
          array_pop($stack);
        }
        else if ($tok[0] === t('('))
        {
          $tok[0] = t('PARAM_START');
          return $this;
        }
        else
        {
          return $this;
        }
      }
    }

    return $this;
  }

  function token($tag, $value = NULL)
  {
    if ( ! is_numeric($tag))
    {
      $tag = t($tag);
    }

    $token = array($tag, $value, $this->line);

    return ($this->tokens[] = $token);
  }

  function tokenize()
  {
    while ( ($this->chunk = substr($this->code, $this->index)) !== FALSE )
    {
      $types = array('identifier', 'comment', 'whitespace', 'line', 'heredoc', 
        'string', 'number', 'regex', 'js', 'literal');

      foreach ($types as $type)
      {
        if ( ($d = $this->{$type.'_token'}()) )
        {
          $this->index += $d;
          break;
        }
      }
    }

    $this->close_indentation();

    if (($tag = array_pop($this->ends)) !== NULL)
    {
      $this->error('missing '.t_canonical($tag));
    }

    if ($this->options['rewrite'])
    {
      $rewriter = new Rewriter($this->tokens);
      $this->tokens = $rewriter->rewrite();
    }

    return $this->tokens;
  }

  function value($index = 0, $value = NULL)
  {
    $token = & last($this->tokens, $index);

    if ( ! is_null($value))
    {
      $token[1] = $value;
    }

    return $token[1];
  }

  function unfinished()
  {
    return
      preg_match(self::$LINE_CONTINUER, $this->chunk) ||
      in_array($this->tag(), t('\\', '.', '?.', 'UNARY', 'MATH', '+', '-', 'SHIFT', 'RELATION',
          'COMPARE', 'LOGIC', 'THROW', 'EXTENDS'));
  }

  function whitespace_token()
  {
    if ( ! (preg_match(self::$WHITESPACE, $this->chunk, $match) || ($nline = ($this->chunk{0} === "\n"))))
    {
      return 0;
    }

    $prev = & last($this->tokens);

    if ($prev)
    {
      $prev[$match ? 'spaced' : 'newLine'] = TRUE;
    }

    return $match ? strlen($match[0]) : 0;
  }
}

?>
