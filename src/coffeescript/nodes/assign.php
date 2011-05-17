<?php

namespace CoffeeScript;

class yyAssign extends yyBase
{
  public $children = array('variable', 'value');

  const METHOD_DEF = '/^(?:(\S+)\.prototype\.|\S+?)?\b([$A-Za-z_][$\w\x7f-\uffff]*)$/';

  function __construct($variable, $value, $context = NULL, $options = NULL)
  {
    $this->variable = $variable;
    $this->value = $value;
    $this->context = $context;
    $this->param = $options ? $options['param'] : NULL;
  }

  function assigns($name)
  {
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
    list($left, $rite) = $this->variable->cache_reference($options);
    $tmp = new yyOp(array_slice($this->context, 0, -1), $left, new yyAssign($rite, $this->value, '='));
    return $tmp->compile($options);
  }

  function compile_node($options)
  {
    if (($is_value = ($this->variable instanceof yyValue)))
    {
      if ($this->variable->is_array() || $this->variable->is_object())
      {
        return $this->compile_pattern_match($options);
      }

      if ($this->variable->is_splice())
      {
        return $this->compile_splice($options);
      }

      if (in_array($this->context, array('||=', '&&=', '?=')))
      {
        return $this->compile_conditional($options);
      }
    }

    $name = $this->variable->compile($options, LEVEL_LIST);

    if ( ! ($this->context || $this->variable->is_assignable()))
    {
      throw new SyntaxError('"'.$this->variable->compile($options).'" cannot be assigned.');
    }

    if ( ! ($this->context || $is_value && ($this->variable->namespaced || $this->variable->has_properties())))
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

    if ($this->value instanceof yyCode && preg_match(self::METHOD_DEF, $name, $match))
    {
      $this->value->name = $match[2];

      if ($match[1])
      {
        $this->value->klass = $match[1];
      }
    }

    $val = $this->value->compile($options, LEVEL_LIST);

    if ($this->context === 'object')
    {
      return "{$name}: {$val}";
    }

    $val = $name.($this->context ? $this->context : '=').' '.$val;

    return $options['level'] <= LEVEL_LIST ? $val : "({$val})";
  }

  function compile_pattern_match($options)
  {
    $top = $options['level'] === LEVEL_TOP;
    $value = $this->value;
    $objects = $this->variable->objects;
    
    if ( ! ($olen = count($objects)))
    {
      $code = $value->compile($options);
      return $options['level'] >= LEVEL_OP ? "($code)" : $code;
    }

    $is_object = $this->variable->is_object();

    if ($top && $olen === 1 && ! (($obj = $objects[0]) instanceof yySplat))
    {
      if ($obj instanceof yyAssign)
      {
        $idx = $obj->variable->base;
        $obj = $obj->value;
      }
      else
      {
        if ($obj->base instanceof yyParens)
        {
          $tmp = new yyValue($obj->unwrap_all());
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
            $idx = new yyLiteral(0);
          }
        }

        $acc = preg_match(IDENTIFIER, $idx->unwrap()->value);
        $value = new yyValue($value);

        if ($acc)
        {
          $value->properties[] = new yyAccess($idx);
        }
        else
        {
          $value->properties[] = new yyIndex($idx);
        }

        $tmp = new yyAssign($obj, $value);

        return $tmp->compile($options);
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
          if ($obj instanceof yyAssign)
          {
            $idx = $obj->variable->base;
            $obj = $obj->value;
          }
          else
          {
            if ($obj->base instanceof yyParens)
            {
              $tmp = new yyValue($obj->unwrap_all());
              list($obj, $idx) = $tmp->cache_reference($options);
            }
            else
            {
              $idx = $obj->this ? $obj->properties[0]->name : $obj;
            }
          }
        }

        if ( ! $splat && ($obj instanceof yySplat))
        {
          $val = "{$olen} <={$vvar}.length ? ".utility('slice').".call({$vvar}, {$i})";

          if (($rest = $olen - $i - 1))
          {
            $ivar = $options['scope']->free_variable('i');
            $val .= ", {$ivar} = {$vvar}.length - {$rest}) : ({$ivar} = {$i}, [])";
          }
          else
          {
            $val .= ') : []';
          }

          $val = new yyLiteral($val);
          $splat = "{$ivar}++";
        }
        else
        {
          if ($obj instanceof yySplat)
          {
            $obj = $obj->name->compile($options);
            throw SyntaxError("multiple splats are disallowed in an assignment: {$obj} ...");
          }

          if (is_int($idx) || is_float($idx))
          {
            $idx = new yyLiteral($splat ? $splat : $idx);
            $acc = FALSE;
          }
          else
          {
            $acc = $is_object ? preg_match(IDENTIFIER, $idx->unwrap()->value) : 0;
          }

          $val = new yyValue(new yyLiteral($vvar), array($acc ? new yyAccess($idx) : new yyIndex($idx)));
        }


      }

      $tmp = new yyAssign($obj, $val, NULL, array('param' => $this->param));
      $assigns[] = $tmp->compile($options, LEVEL_TOP);
    }

    if ( ! $top)
    {
      $assigns[] = $vvar;
    }

    $code = implode(', ', $assigns);

    return $options['level'] < LEVEL_LIST ? $code : "({$code})";
  }

  function compile_splice($options)
  {
    $tmp = array_pop($this->varaiable->properties);

    $from = $tmp->from;
    $to = $tmp->to;
    $exclusive = $tmp->exclusive;

    $name = $this->variable->compile($options);

    list($from_decl, $from_ref) = $from ? $from->cache($options, LEVEL_OP) : array('0', '0');

    if ($to)
    {
      if (($from && $from->is_simple_number()) && $to->is_simple_number)
      {
        $to = (int) $to->compile($options) - (int) $from_ref;

        if ( ! $exclusive)
        {
          $to++;
        }
      }
      else
      {
        $to = $to->compile($options).' - '. $from_ref;

        if ( ! $exclusive)
        {
          $to .= ' + 1';
        }
      }
    }
    else
    {
      $to = "9e9";
    }

    list($val_def, $val_ref) = $this->value->cache($options, LEVEL_LIST);

    $code = "[].splice.apply({$name}, [{$from_decl}, {$to}].concat({$val_def})), {$val_ref}";
    return $options['level'] > LEVEL_TOP ? "({$code})" : $code;
  }

  function unfold_soak($options)
  {
    return unfold_soak($options, $this, 'variable');
  }
}

?>
