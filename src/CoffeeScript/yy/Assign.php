<?php

namespace CoffeeScript;

class yy_Assign extends yy_Base
{
  public $children = array('variable', 'value');

  function constructor($variable, $value, $context = '', $options = NULL)
  {
    $this->variable = $variable;
    $this->value = $value;
    $this->context = $context;
    $this->param = $options ? $options['param'] : NULL;

    $this->subpattern = isset($options['subpattern']) ? $options['subpattern'] : NULL;

    $tmp = $this->variable->unwrap_all();

    $forbidden = in_array($name = isset($tmp->value) ? $tmp->value : NULL, Lexer::$STRICT_PROSCRIBED);

    if ($forbidden && $this->context !== 'object')
    {
      throw new SyntaxError("variable name may not be $name");
    }

    return $this;
  }

  function assigns()
  {
    list($name) = args(func_get_args(), 1);

    if ($this->context === 'object')
    {
      return $this->value->assigns($name);
    }
    else
    {
      return $this->variable->assigns($name);
    }
  }

  function compile_conditional($options)
  {
    list($left, $right) = $this->variable->cache_reference($options);

    if ( ! count($left->properties) && $left->base instanceof yy_Literal &&
      $left->base->value !== 'this' && ! $options['scope']->check($left->base->value))
    {
      throw new Error('the variable "'.$left->base->value.'" can\'t be assigned with '.$this->context.' because it has not been defined.');
    }

    if (strpos($this->context, '?') > -1)
    {
      $options['isExistentialEquals'] = TRUE;
    }

    $tmp = yy('Op', substr($this->context, 0, -1), $left, yy('Assign', $right, $this->value, '='));

    return $tmp->compile($options);
  }

  function compile_node($options)
  {
    if (($is_value = ($this->variable instanceof yy_Value)))
    {
      if ($this->variable->is_array() || $this->variable->is_object())
      {
        return $this->compile_pattern_match($options);
      }

      if ($this->variable->is_splice())
      {
        return $this->compile_splice($options);
      }

      if (in_array($this->context, array('||=', '&&=', '?='), TRUE))
      {
        return $this->compile_conditional($options);
      }
    }

    $name = $this->variable->compile($options, LEVEL_LIST);

    if ( ! $this->context)
    {
      if ( ! ( ($var_base = $this->variable->unwrap_all()) && $var_base->is_assignable()))
      {
        throw new SyntaxError('"'.$this->variable->compile($options).'" cannot be assigned.');
      }

      if ( ! (is_callable(array($var_base, 'has_properties')) && $var_base->has_properties()))
      {
        if ($this->param)
        {
          $options['scope']->add($name, 'var');
        }
        else
        {
          $options['scope']->find($name);
        }
      }
    }

    if ($this->value instanceof yy_Code && preg_match(METHOD_DEF, ''.$name, $match))
    {
      if (isset($match[1]) && $match[1] !== '')
      {
        $this->value->klass = $match[1];
      }

      foreach (range(2, 5) as $i)
      {
        if (isset($match[$i]) && $match[$i] !== '')
        {
          $this->value->name = $match[$i];
          break;
        }
      }
    }

    $val = $this->value->compile($options, LEVEL_LIST);

    if ($this->context === 'object')
    {
      return "{$name}: {$val}";
    }

    $val = $name.' '.($this->context ? $this->context : '=').' '.$val;

    return $options['level'] <= LEVEL_LIST ? $val : "({$val})";
  }

  function compile_pattern_match($options)
  {
    $top = $options['level'] === LEVEL_TOP;
    $value = $this->value;
    $objects = $this->variable->base->objects;

    if ( ! ($olen = count($objects)))
    {
      $code = $value->compile($options);
      return $options['level'] >= LEVEL_OP ? "({$code})" : $code;
    }

    $is_object = $this->variable->is_object();

    if ($top && $olen === 1 && ! (($obj = $objects[0]) instanceof yy_Splat))
    {
      if ($obj instanceof yy_Assign)
      {
        $idx = $obj->variable->base;
        $obj = $obj->value;
      }
      else
      {
        if ($obj->base instanceof yy_Parens)
        {
          $tmp = yy('Value', $obj->unwrap_all());
          list($obj, $idx) = $tmp->cache_reference($options);
        }
        else
        {
          if ($is_object)
          {
            $idx = $obj->this ? $obj->properties[0]->name : $obj;
          }
          else
          {
            $idx = yy('Literal', 0);
          }
        }
      }

      $acc = preg_match(IDENTIFIER, $idx->unwrap()->value);
      $value = yy('Value', $value);

      if ($acc)
      {
        $value->properties[] = yy('Access', $idx);
      }
      else
      {
        $value->properties[] = yy('Index', $idx);
      }

      $tmp = $obj->unwrap();
      $tmp = isset($tmp->value) ? $tmp->value : NULL;

      if (in_array($tmp, Lexer::$COFFEE_RESERVED))
      {
        throw new SyntaxError('assignment to a reserved word: '.$obj->compile($options).' = '.$value->compile($options));
      }

      return yy('Assign', $obj, $value, NULL, array('param' => $this->param))->compile($options, LEVEL_TOP);
    }

    $vvar = $value->compile($options, LEVEL_LIST);
    $assigns = array();
    $splat = FALSE;

    if ( ! preg_match(IDENTIFIER, $vvar) || $this->variable->assigns($vvar))
    {
      $assigns[] = ($ref = $options['scope']->free_variable('ref')).' = '.$vvar;
      $vvar = $ref;
    }

    foreach ($objects as $i => $obj)
    {
      $idx = $i;

      if ($is_object)
      {
        if ($obj instanceof yy_Assign)
        {
          $idx = $obj->variable->base;
          $obj = $obj->value;
        }
        else
        {
          if ($obj->base instanceof yy_Parens)
          {
            $tmp = yy('Value', $obj->unwrap_all());
            list($obj, $idx) = $tmp->cache_reference($options);
          }
          else
          {
            $idx = $obj->this ? $obj->properties[0]->name : $obj;
          }
        }
      }

      if ( ! $splat && ($obj instanceof yy_Splat))
      {
        $name = $obj->name->unwrap()->value;
        $obj = $obj->unwrap();

        $val = "{$olen} <= {$vvar}.length ? ".utility('slice').".call({$vvar}, {$i}";
        $ivar = 'undefined';

        if (($rest = $olen - $i - 1))
        {
          $ivar = $options['scope']->free_variable('i');
          $val .= ", {$ivar} = {$vvar}.length - {$rest}) : ({$ivar} = {$i}, [])";
        }
        else
        {
          $val .= ') : []';
        }

        $val = yy('Literal', $val);
        $splat = "{$ivar}++";
      }
      else
      {
        $name = $obj->unwrap();
        $name = isset($name->value) ? $name->value : NULL;

        if ($obj instanceof yy_Splat)
        {
          $obj = $obj->name->compile($options);
          throw new SyntaxError("multiple splats are disallowed in an assignment: {$obj}...");
        }

        if (is_numeric($idx))
        {
          $idx = yy('Literal', $splat ? $splat : $idx);
          $acc = FALSE;
        }
        else
        {
          $acc = $is_object ? preg_match(IDENTIFIER, $idx->unwrap()->value) : 0;
        }

        $val = yy('Value', yy('Literal', $vvar), array($acc ? yy('Access', $idx) : yy('Index', $idx)));
      }

      if (isset($name) && $name && in_array($name, Lexer::$COFFEE_RESERVED))
      {
        throw new SyntaxError("assignment to a reserved word: ".$obj->compile($options).' = '.$val->compile($options));
      }

      $tmp = yy('Assign', $obj, $val, NULL, array('param' => $this->param, 'subpattern' => TRUE));
      $assigns[] = $tmp->compile($options, LEVEL_LIST);
    }

    if ( ! ($top || $this->subpattern))
    {
      $assigns[] = $vvar;
    }

    $code = implode(', ', $assigns);

    return $options['level'] < LEVEL_LIST ? $code : "({$code})";
  }

  function compile_splice($options)
  {
    $tmp = array_pop($this->variable->properties);

    $from = $tmp->range->from;
    $to = $tmp->range->to;
    $exclusive = $tmp->range->exclusive;

    $name = $this->variable->compile($options);

    list($from_decl, $from_ref) = $from ? $from->cache($options, LEVEL_OP) : array('0', '0');

    if ($to)
    {
      if (($from && $from->is_simple_number()) && $to->is_simple_number())
      {
        $to = intval($to->compile($options)) - intval($from_ref);

        if ( ! $exclusive)
        {
          $to++;
        }
      }
      else
      {
        $to = $to->compile($options, LEVEL_ACCESS).' - '. $from_ref;

        if ( ! $exclusive)
        {
          $to .= ' + 1';
        }
      }
    }
    else
    {
      $to = '9e9';
    }

    list($val_def, $val_ref) = $this->value->cache($options, LEVEL_LIST);

    $code = "[].splice.apply({$name}, [{$from_decl}, {$to}].concat({$val_def})), {$val_ref}";
    return $options['level'] > LEVEL_TOP ? "({$code})" : $code;
  }

  function is_statement($options = NULL)
  {
    return isset($options['level']) && $options['level'] === LEVEL_TOP && $this->context && strpos($this->context, '?') > -1;
  }

  function unfold_soak($options = NULL)
  {
    return unfold_soak($options, $this, 'variable');
  }
}

?>
