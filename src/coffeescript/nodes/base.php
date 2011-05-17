<?php

namespace CoffeeScript;

class yyBase
{
  public $assigns = FALSE;
  public $children = array();
  public $soak = FALSE;
  public $unfold_soak = FALSE;

  function __toString()
  {
    return $this->to_string();
  }

  function cache($options, $level = NULL, $reused = NULL)
  {
    if ( ! $this->is_complex())
    {
      $ref = $level ? $this->compile($options, $level) : $this;
      return array($ref, $ref);
    }
    else
    {
      $ref = new yyLiteral($reused ? $reused : $options['scope']->free_variable('ref'));
      $sub = new yyAssign($ref, $this);

      if ($level)
      {
        return array($sub->compile($options, $level), $ref->value);
      }
      else
      {
        return array($sub, $ref);
      }
    }
  }

  function compile($options, $level = NULL)
  {
    if ($level)
    {
      $options->level = $level;
    }

    if ( ! ($node = $this->unfold_soak($options)))
    {
      $node = $this;
    }

    $node->tab = $options->indent;

    if ($options->level === LEVEL_TOP || ! $node->is_statement($options))
    {
      return $node->compile_node($options);
    }

    return $node->compile_closure($options);
  }

  function compile_closure($options)
  {
    if ($this->jumps() || ($this instanceof yyThrow))
    {
      throw new SyntaxError('cannot use a pure statement in an expression');
    }

    $options->shared_scope = TRUE;

    $closure = Closure::wrap($this);
    return $closure->compile_node($options);
  }

  function compile_loop_reference($options, $name)
  {
    $src = $tmp = $this->compile($options, LEVEL_LIST);

    if ( ! (abs($src) < INF || preg_match(IDENTIFIER, $src) && 
      $options['scope']->check($src, TRUE)))
    {
      $src = ($tmp = $options['scope']->free_variable($name)).' = '.$src;
    }

    return array($src, $tmp);
  }

  function contains($pred)
  {
    $contains = FALSE;

    $this->traverse_children(FALSE, function($node) use ( & $contains)
    {
      if ($pred(node))
      {
        $contains = TRUE;
        return FALSE;
      }
    });
  }

  function contains_type($type)
  {
    return $this instanceof $type || $this->contains(function($node) use ( & $type)
    {
      return $node instanceof $type;
    });
  }

  function each_child($func)
  {
    if ( ! ($this->children))
    {
      return $this;
    }

    foreach ($this->children as $i => $attr)
    {
      if (isset($this->{$attr}))
      {
        foreach (flatten(array($this->{$attr})) as $i => $child)
        {
          if ( ! $func($child))
          {
            break 2;
          }
        }
      }
    }

    return $this;
  }

  function invert()
  {
    return new yyOp('!', $this);
  }

  function is_assignable()
  {
    return FALSE;
  }

  function is_complex()
  {
    return FALSE;
  }

  function is_chainable()
  {
    return FALSE;
  }

  function is_statement()
  {
    return FALSE;
  }

  function jumps()
  {
    return FALSE;
  }

  function last_non_comment($list)
  {
    $i = count($list);

    while ($i--)
    {
      if ( ! ($list[$i] instanceof yyComment))
      {
        return $list[$i];
      }
    }

    return NULL;
  }

  function make_return()
  {
    return new yyReturn($this);
  }

  function to_string($idt = '', $name = __CLASS__)
  {
    $tree = "\n{$idt}{$name}";

    if ($this->soak)
    {
      $tree .= '?';
    }

    $this->each_child(function($node) use ( & $tree)
    {
      $tree .= $node.to_string($idt + TAB);
    });

    return $tree;
  }

  function traverse_children($cross_scope, $func)
  {
    $this->each_child(function($child) use ( & $func, & $cross_scope)
    {
      if ( ! $func($child))
      {
        return false;
      }

      return $child->traverse_children($cross_scope, $func);
    });
  }

  function unwrap()
  {
    return NULL;
  }

  function unwrap_all()
  {
    $node = $this;

    while (($tmp = $node->unwrap()) && $node != $tmp) {}

    return $node;
  }
}

?>
