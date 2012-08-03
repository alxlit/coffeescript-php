<?php

namespace CoffeeScript;

class yy_Op extends yy_Base
{
  static $CONVERSIONS = array(
    '==' => '===',
    '!=' => '!==',
    'of' => 'in'
  );

  static $INVERSIONS = array(
    '!==' => '===',
    '===' => '!=='
  );

  public $children = array('first', 'second');

  public $operator = NULL;

  public $invert = TRUE;

  function constructor($op, $first, $second = NULL, $flip = NULL)
  {
    if ($op === 'in')
    {
      return yy('In', $first, $second);
    }

    if ($op === 'do')
    {
      return $this->generate_do($first);
    }

    if ($op === 'new')
    {
      if ($first instanceof yy_Call && ! (isset($first->do) && $first->do) && ! (isset($first->is_new) && $first->is_new))
      {
        return $first->new_instance();
      }

      if ($first instanceof yy_Code && $first->bound || (isset($first->do) && $first->do))
      {
        $first = yy('Parens', $first);
      }
    }

    $this->operator = isset(self::$CONVERSIONS[$op]) ? self::$CONVERSIONS[$op] : $op;
    $this->first = $first;
    $this->second = $second;
    $this->flip = !! $flip;

    return $this;
  }

  function compile_chain($options)
  {
    $tmp = $this->first->second->cache($options);

    $this->first->second = $tmp[0];
    $shared = $tmp[1];

    $fst = $this->first->compile($options, LEVEL_OP);

    $code = "{$fst} ".($this->invert ? '&&' : '||').' '.$shared->compile($options).' '
      .$this->operator.' '.$this->second->compile($options, LEVEL_OP);

    return "({$code})";
  }

  function compile_existence($options)
  {
    if ($this->first->is_complex() && $options['level'] > LEVEL_TOP)
    {
      $ref = yy('Literal', $options['scope']->free_variable('ref'));
      $fst = yy('Parens', yy('Assign', $ref, $this->first));
    }
    else
    {
      $fst = $this->first;
      $ref = $fst;
    }

    $tmp = yy('If', yy('Existence', $fst), $ref, array('type' => 'if'));
    $tmp->add_else($this->second);

    return $tmp->compile($options);
  }

  function compile_node($options, $level = NULL)
  {
    $is_chain = $this->is_chainable() && $this->first->is_chainable();

    if ( ! $is_chain)
    {
      $this->first->front = $this->front;
    }

    $tmp = $this->first->unwrap_all();
    $tmp = isset($tmp->value) ? $tmp->value : NULL;

    if ($this->operator === 'delete' && $options['scope']->check($tmp))
    {
      throw new SyntaxError('delete operand may not be argument or var');
    }

    if (in_array($this->operator, array('--', '++')) && in_array($tmp, Lexer::$STRICT_PROSCRIBED))
    {
      throw new SyntaxError('prefix increment/decrement may not have eval or arguments operand');
    }

    if ($this->is_unary())
    {
      return $this->compile_unary($options);
    }

    if ($is_chain)
    {
      return $this->compile_chain($options);
    }

    if ($this->operator === '?')
    {
      return $this->compile_existence($options);
    }

    $this->first->front = $this->front;

    $code = $this->first->compile($options, LEVEL_OP).' '.$this->operator.' '
      .$this->second->compile($options, LEVEL_OP);

    return $options['level'] <= LEVEL_OP ? $code : "({$code})";
  }

  function compile_unary($options)
  {
    if ($options['level'] >= LEVEL_ACCESS)
    {
      return yy('Parens', $this)->compile($options);
    }

    $parts = array($op = $this->operator);
    $plus_minus = in_array($op, array('+', '-'), TRUE);

    if (in_array($op, array('new', 'typeof', 'delete'), TRUE) ||
        $plus_minus &&
        $this->first instanceof yy_Op && $this->first->operator === $op)
    {
      $parts[] = ' ';
    }

    if (($plus_minus && $this->first instanceof yy_Op) || ($op === 'new' && $this->first->is_statement($options)))
    {
      $this->first = yy('Parens', $this->first);
    }

    $parts[] = $this->first->compile($options, LEVEL_OP);

    if ($this->flip)
    {
      $parts = array_reverse($parts);
    }

    return implode('', $parts);
  }

  function is_chainable()
  {
    return in_array($this->operator, array('<', '>', '>=', '<=', '===', '!=='), TRUE);
  }

  function is_complex()
  {
    return ! ($this->is_unary() && in_array($this->operator, array('+', '-'))) || $this->first->is_complex();
  }

  function invert()
  {
    if ($this->is_chainable() && $this->first->is_chainable())
    {
      $all_invertable = TRUE;
      $curr = $this;

      while ($curr && (isset($curr->operator) && $curr->operator))
      {
        if ($all_invertable)
        {
          $all_invertable = isset(self::$INVERSIONS[$curr->operator]);
        }

        $curr = $curr->first;
      }

      if ( ! $all_invertable)
      {
        return yy('Parens', $this)->invert();
      }

      $curr = $this;

      while ($curr && (isset($curr->operator) && $curr->operator))
      {
        $curr->invert = ! $curr->invert;
        $curr->operator = self::$INVERSIONS[$curr->operator];
        $curr = $curr->first;
      }

      return $this;
    }
    else if (isset(self::$INVERSIONS[$this->operator]) && ($op = self::$INVERSIONS[$this->operator]))
    {
      $this->operator = $op;

      if ($this->first->unwrap() instanceof yy_Op)
      {
        $this->first->invert();
      }

      return $this;
    }
    else if ($this->second)
    {
      return yy('Parens', $this)->invert();
    }
    else if ($this->operator === '!' && (($fst = $this->first->unwrap()) instanceof yy_Op) &&
      in_array($fst->operator, array('!', 'in', 'instanceof'), TRUE))
    {
      return $fst;
    }
    else
    {
      return yy('Op', '!', $this);
    }
  }

  function generate_do($exp)
  {
    $passed_params = array();
    $func = $exp;

    if ($exp instanceof yy_Assign && ($ref = $exp->value->unwrap()) instanceof yy_Code)
    {
      $func = $ref;
    }

    foreach ((isset($func->params) && $func->params ? $func->params : array()) as $param)
    {
      if (isset($param->value) && $param->value)
      {
        $passed_params[] = $param->value;
        unset($param->value);
      }
      else
      {
        $passed_params[] = $param;
      }
    }

    $call = yy('Call', $exp, $passed_params);
    $call->do = TRUE;

    return $call;
  }

  function is_simple_number()
  {
    return FALSE;
  }

  function is_unary()
  {
    return ! (isset($this->second) && $this->second);
  }

  function unfold_soak($options = NULL)
  {
    if (in_array($this->operator, array('++', '--', 'delete'), TRUE))
    {
      return unfold_soak($options, $this, 'first');
    }

    return NULL;
  }

  function to_string($idt = '', $name = __CLASS__)
  {
    return parent::to_string($idt, $name.' '.$this->operator);
  }
}

?>
