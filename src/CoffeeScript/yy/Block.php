<?php

namespace CoffeeScript;

class yy_Block extends yy_Base
{
  public $children = array('expressions');

  function constructor($nodes = array())
  {
    $this->expressions = compact(flatten($nodes));

    return $this;
  }

  function compile($options, $level = NULL)
  {
    if (isset($options['scope']))
    {
      return parent::compile($options, $level);
    }
    else
    {
      return $this->compile_root($options);
    }
  }

  function compile_node($options)
  {
    $this->tab = $options['indent'];

    $top = $options['level'] === LEVEL_TOP;
    $codes = array();

    foreach ($this->expressions as $i => $node)
    {
      $node = $node->unwrap_all();
      $node = ($tmp = $node->unfold_soak($options)) ? $tmp : $node;

      if ($node instanceof yy_Block)
      {
        $codes[] = $node->compile_node($options);
      }
      else if ($top)
      {
        $node->front = TRUE;
        $code = $node->compile($options);

        if ( ! $node->is_statement($options))
        {
          $code = "{$this->tab}{$code};";

          if ($node instanceof yy_Literal)
          {
            $code = "{$code}\n";
          }
        }

        $codes[] = $code;
      }
      else
      {
        $codes[] = $node->compile($options, LEVEL_LIST);
      }
    }

    if ($top)
    {
      if (isset($this->spaced) && $this->spaced)
      {
        return "\n".implode("\n\n", $codes)."\n";
      }
      else
      {
        return implode("\n", $codes);
      }
    }

    $code = ($tmp = implode(', ', $codes)) ? $tmp : 'void 0';

    if (count($codes) && $options['level'] >= LEVEL_LIST)
    {
      return "({$code})";
    }
    else
    {
      return $code;
    }
  }

  function compile_root($options)
  {
    $options['indent'] = isset($options['bare']) && $options['bare'] ? '' : TAB;
    $options['scope'] = new Scope(NULL, $this, NULL);
    $options['level'] = LEVEL_TOP;

    $this->spaced = TRUE;
    $prelude = '';

    if ( ! (isset($options['bare']) && $options['bare']))
    {
      $prelude_exps = array();

      foreach ($this->expressions as $i => $exp)
      {
        if ( ! ($exp->unwrap() instanceof yy_Comment))
        {
          break;
        }

        $prelude_exps[] = $exp;
      }

      $rest = array_slice($this->expressions, count($prelude_exps));
      $this->expressions = $prelude_exps;

      if ($prelude_exps)
      {
        $prelude = $this->compile_node(array_merge($options, array('indent' => '')))."\n";
      }

      $this->expressions = $rest;
    }

    $code = $this->compile_with_declarations($options);

    if (isset($options['bare']) && $options['bare'])
    {
      return $code;
    }

    return "{$prelude}(function() {\n{$code}\n}).call(this);\n";
  }

  function compile_with_declarations($options)
  {
    $code = $post = '';

    foreach ($this->expressions as $i => & $expr)
    {
      $expr = $expr->unwrap();

      if ( ! ($expr instanceof yy_Comment || $expr instanceof yy_Literal))
      {
        break;
      }
    }

    $options = array_merge($options, array('level' => LEVEL_TOP));

    if ($i)
    {
      $rest = array_splice($this->expressions, $i, count($this->expressions));

      list($spaced, $this->spaced) = array(isset($this->spaced) && $this->spaced, FALSE);
      list($code, $this->spaced) = array($this->compile_node($options), $spaced);

      $this->expressions = $rest;
    }

    $post = $this->compile_node($options);

    $scope = $options['scope'];

    if ($scope->expressions === $this)
    {
      $declars = $scope->has_declarations();
      $assigns = $scope->has_assignments();

      if ($declars or $assigns)
      {
        if ($i)
        {
          $code .= "\n";
        }

        $code .= $this->tab.'var ';

        if ($declars)
        {
          $code .= implode(', ', $scope->declared_variables());
        }

        if ($assigns)
        {
          if ($declars)
          {
            $code .= ",\n{$this->tab}".TAB;
          }

          $code .= implode(",\n{$this->tab}".TAB, $scope->assigned_variables());
        }

        $code .= ";\n";
      }
    }

    return $code.$post;
  }

  function is_empty()
  {
    return ! count($this->expressions);
  }

  function is_statement($options)
  {
    foreach ($this->expressions as $i => $expr)
    {
      if ($expr->is_statement($options))
      {
        return TRUE;
      }
    }

    return FALSE;
  }

  function jumps($options = array())
  {
    foreach ($this->expressions as $i => $expr)
    {
      if ($expr->jumps($options))
      {
        return $expr;
      }
    }

    return FALSE;
  }

  function make_return($res = NULL)
  {
    $len = count($this->expressions);

    while ($len--)
    {
      $expr = $this->expressions[$len];

      if ( ! ($expr instanceof yy_Comment))
      {
        $this->expressions[$len] = $expr->make_return($res);

        if ($expr instanceof yy_Return && ! $expr->expression)
        {
          return array_splice($this->expressions, $len, 1);
        }

        break;
      }
    }

    return $this;
  }

  function pop()
  {
    return array_pop($this->expressions);
  }

  function push($node)
  {
    $this->expressions[] = $node;
    return $this;
  }

  function unshift($node)
  {
    array_unshift($this->expressions, $node);
    return $this;
  }

  function unwrap()
  {
    return count($this->expressions) === 1 ? $this->expressions[0] : $this;
  }

  static function wrap($nodes)
  {
    if ( ! is_array($nodes))
    {
      $nodes = array($nodes);
    }

    if (count($nodes) === 1 && $nodes[0] instanceof yy_Block)
    {
      return $nodes[0];
    }

    return yy('Block', $nodes);
  }
}

?>
