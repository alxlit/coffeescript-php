<?php

namespace CoffeeScript;

class yy_Switch extends yy_Base
{
  public $children = array('subject', 'cases', 'otherwise');

  function constructor($subject = NULL, $cases = array(), $otherwise = NULL)
  {
    $this->subject = $subject;
    $this->cases = $cases;
    $this->otherwise = $otherwise;

    return $this;
  }

  function compile_node($options)
  {
    $idt1 = $options['indent'].TAB;
    $idt2 = $options['indent'] = $idt1.TAB;

    $code = $this->tab.'switch ('
      .($this->subject ? $this->subject->compile($options, LEVEL_PAREN) : 'false')
      .") {\n";

    foreach ($this->cases as $i => $case)
    {
      list($conditions, $block) = $case;

      foreach (flatten(array($conditions)) as $cond)
      {
        if ( ! $this->subject)
        {
          $cond = $cond->invert();
        }

        $code .= $idt1.'case '.$cond->compile($options, LEVEL_PAREN).":\n";
      }

      if ($body = $block->compile($options, LEVEL_TOP))
      {
        $code .= $body."\n";
      }

      if ($i === (count($this->cases) - 1) && ! $this->otherwise)
      {
        break;
      }

      $expr = $this->last_non_comment($block->expressions);

      if ($expr instanceof yy_Return || 
         ($expr instanceof yy_Literal && $expr->jumps() && ''.$expr->value !== 'debugger'))
      {
        continue;
      }

      $code .= $idt2."break;\n";
    }

    if ($this->otherwise && count($this->otherwise->expressions))
    {
      $code .= $idt1."default:\n".$this->otherwise->compile($options, LEVEL_TOP)."\n";
    }

    return $code.$this->tab.'}';
  }

  function is_statement($options = NULL)
  {
    return TRUE;
  }

  function jumps($options = array())
  {
    if ( ! isset($options['block']))
    {
      $options['block'] = TRUE;
    }

    foreach ($this->cases as $case)
    {
      list($conds, $block) = $case;

      if ($block->jumps($options))
      {
        return $block;
      }
    }

    if (isset($this->otherwise) && $this->otherwise)
    {
      return $this->otherwise->jumps($options);
    }

    return FALSE;
  }

  function make_return($res = NULL)
  {
    foreach ($this->cases as $pair)
    {
      $pair[1]->make_return($res);
    }

    if ($res && ! $this->otherwise)
    {
      $this->otherwise = yy('Block', array(yy('Literal', 'void 0')));
    }

    if ($this->otherwise)
    {
      $this->otherwise->make_return($res);
    }

    return $this;
  }
}

?>
