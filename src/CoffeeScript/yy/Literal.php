<?php

namespace CoffeeScript;

class yy_Literal extends yy_Base
{
  public $is_undefined = FALSE;

  function constructor($value)
  {
    $this->value = $value;

    return $this;
  }

  function assigns($name)
  {
    return $name === $this->value;
  }

  function compile_node($options)
  {
    if ($this->is_undefined())
    {
      $code = $options['level'] >= LEVEL_ACCESS ? '(void 0)' : 'void 0';
    }
    else if ($this->value === 'this')
    {
      if ( (isset($options['scope']->method->bound) && $options['scope']->method->bound) )
      {
        $code = $options['scope']->method->context;
      }
      else
      {
        $code = $this->value;
      }
    }
    else if (isset($this->value->reserved) && $this->value->reserved)
    {
      $code = '"'.$this->value.'"';
    }
    else
    {
      $code = ''.$this->value;
    }

    return $this->is_statement() ? "{$this->tab}{$code};" : $code;
  }

  function is_assignable()
  {
    return preg_match(IDENTIFIER, ''.$this->value);
  }

  function is_complex()
  {
    return FALSE;
  }

  function is_statement()
  {
    return in_array(''.$this->value, array('break', 'continue', 'debugger'), TRUE);
  }

  function is_undefined()
  {
    return $this->is_undefined;
  }

  function jumps($options = array())
  {
    if ($this->value === 'break' && ! ( (isset($options['loop']) && $options['loop']) || (isset($options['block']) && $options['block']) ))
    {
      return $this;
    }

    if ($this->value === 'continue' && ! (isset($options['loop']) && $options['loop']))
    {
      return $this;
    }

    return FALSE;
  }

  function make_return()
  {
    return $this->is_statement() ? $this : parent::make_return();
  }

  function to_string($idt = '', $name = __CLASS__)
  {
    return ' "'.$this->value.'"';
  }
}

?>
