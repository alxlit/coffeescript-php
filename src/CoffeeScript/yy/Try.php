<?php

namespace CoffeeScript;

class yy_Try extends yy_Base
{
  public $children = array('attempt', 'recovery', 'ensure');

  function constructor($attempt = NULL, $error = NULL, $recovery = NULL, $ensure = NULL)
  {
    $this->attempt = $attempt;
    $this->error = $error;
    $this->recovery = $recovery;
    $this->ensure = $ensure;

    return $this;
  }

  function compile_node($options = array())
  {
    $options['indent'] .= TAB;
    $error_part = $this->error ? ' ('.$this->error->compile($options).') ' : ' ';
    $try_part = $this->attempt->compile($options, LEVEL_TOP);
    $catch_part = '';

    if ($this->recovery)
    {
      if (in_array($this->error, Lexer::$STRICT_PROSCRIBED))
      {
        throw new SyntaxError('catch variable may not be "'.$this->error->value.'"');
      }

      if ( ! $options['scope']->check($this->error->value))
      {
        $options['scope']->add($this->error->value, 'param');
      }

      $catch_part = " catch{$error_part}{\n".$this->recovery->compile($options, LEVEL_TOP)."\n{$this->tab}}";
    }
    else if ( ! ($this->ensure || $this->recovery))
    {
      $catch_part = ' catch (_error) {}';
    }

    $ensure_part = isset($this->ensure) && $this->ensure ? " finally {\n".$this->ensure->compile($options, LEVEL_TOP)."\n{$this->tab}}" : '';

    return
      "{$this->tab}try {\n"
    . $try_part."\n"
    . "{$this->tab}}{$catch_part}{$ensure_part}";
  }

  function is_statement()
  {
    return TRUE;
  }

  function jumps($options = array())
  {
    return $this->attempt->jumps($options) || (isset($this->recovery) && $this->recovery->jumps($options));
  }

  function make_return($res)
  {
    if ($this->attempt)
    {
      $this->attempt = $this->attempt->make_return($res);
    }

    if ($this->recovery)
    {
      $this->recovery = $this->recovery->make_return($res);
    }

    return $this;
  }
}

?>
