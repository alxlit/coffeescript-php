<?php

namespace CoffeeScript;

class yy_Range extends yy_Base
{
  public $children = array('from', 'to');

  public $from_num = 0;
  public $to_num = 0;

  private static function check($num)
  {
    // '0' evaluates to FALSE in PHP, but TRUE in JavaScript. Explicit conditions here.
    return ! in_array($num, array(0, NULL, FALSE, ''), TRUE);
  }

  function constructor($from, $to, $tag)
  {
    $this->from = $from;
    $this->to = $to;
    $this->exclusive = $tag === 'exclusive';
    $this->equals = $this->exclusive ? '' : '=';

    return $this;
  }

  function compile_array($options)
  {
    if (self::check($this->from_num) && self::check($this->to_num) && abs($this->from_num - $this->to_num) <= 20)
    {
      $range = range($this->from_num, $this->to_num);

      if ($this->exclusive)
      {
        array_pop($range);
      }

      return '['.implode(', ', $range).']';
    }

    $idt = $this->tab.TAB;
    $i = $options['scope']->free_variable('i');
    $result = $options['scope']->free_variable('results');
    $pre = "\n{$idt}{$result} = [];";

    if (self::check($this->from_num) && self::check($this->to_num))
    {
      $options['index'] = $i;
      $body = $this->compile_node($options);
    }
    else
    {
      $vars = "{$i} = {$this->from_c}".($this->to_c !== $this->to_var ? ", {$this->to_c}" : '');
      $cond = "{$this->from_var} <= {$this->to_var}";
      $body = "var {$vars}; {$cond} ? {$i} <{$this->equals} {$this->to_var} : {$i} >{$this->equals} {$this->to_var}; {$cond} ? {$i}++ : {$i}--";
    }

    $post = "{ {$result}.push({$i}); }\n{$idt}return {$result};\n{$options['indent']}";

    $has_args = function($node)
    {
      return $node->contains(function($n)
      {
        return $n instanceof yy_Literal && $n->value === 'arguments' && ! $n->as_key();
      });

      return FALSE;
    };

    $args = '';

    if ($has_args($this->from) || $has_args($this->to))
    {
      $args = ', arguments';
    }

    return "(function() {{$pre}\n{$idt}for ({$body}){$post}}).apply(this{$args})";
  }

  function compile_node($options)
  {
    if ( ! (isset($this->from_var) && $this->from_var))
    {
      $this->compile_variables($options);
    }

    if ( ! (isset($options['index']) && $options['index']))
    {
      return $this->compile_array($options);
    }

    $known = self::check($this->from_num) && self::check($this->to_num);
    $idx = del($options, 'index');
    $idx_name = del($options, 'name');
    $named_index = $idx_name && $idx_name !== $idx;

    $var_part = "{$idx} = {$this->from_c}";

    if ($this->to_c !== $this->to_var)
    {
      $var_part .= ", {$this->to_c}";
    }

    if (isset($this->step) && $this->step !== $this->step_var)
    {
      $var_part .= ", {$this->step}";
    }

    list($lt, $gt) = array("{$idx} <{$this->equals}", "{$idx} >{$this->equals}");

    if (isset($this->step_num) && self::check($this->step_num))
    {
      $cond_part = intval($this->step_num) > 0 ? "{$lt} {$this->to_var}" : "{$gt} {$this->to_var}";
    }
    else if ($known)
    {
      list($from, $to) = array(intval($this->from_num), intval($this->to_num));
      $cond_part = $from <= $to ? "{$lt} {$to}" : "{$gt} {$to}";
    }
    else
    {
      $cond = "{$this->from_var} <= {$this->to_var}";
      $cond_part = "{$cond} ? {$lt} {$this->to_var} : {$gt} {$this->to_var}";
    }

    if (isset($this->step_var) && $this->step_var)
    {
      $step_part = "{$idx} += {$this->step_var}";
    }
    else if ($known)
    {
      if ($named_index)
      {
        $step_part = $from <= $to ? "++{$idx}" : "--{$idx}";
      }
      else
      {
        $step_part = $from <= $to ? "{$idx}++" : "{$idx}--";
      }
    }
    else
    {
      if ($named_index)
      {
        $step_part = "{$cond} ? ++{$idx} : --{$idx}";
      }
      else
      {
        $step_part = "{$cond} ? {$idx}++ : {$idx}--";
      }
    }

    if ($named_index)
    {
      $var_part = "{$idx_name} = {$var_part}";
      $step_part = "{$idx_name} = {$step_part}";
    }

    return "{$var_part}; {$cond_part}; {$step_part}";
  }

  function compile_simple($options)
  {
    list($from, $to) = array($this->from_num, $this->to_num);

    $idx = del($options, 'index');
    $step = del($options, 'step');

    if ($step)
    {
      $stepvar = $options['scope']->free_variable('step');
    }

    $var_part = "{$idx} = {$from}";

    if ($step)
    {
      $var_part .= ", {$stepvar} = ".$step->compile($options);
    }

    $cond_part = $from <= $to ? "{$idx} <{$this->equals} {$to}" : "{$idx} >{$this->equals} {$to}";

    if ($step)
    {
      $step_part = "{$idx} += {$stepvar}";
    }
    else
    {
      $step_part = $from <= $to ? "{$idx}++" : "{$idx}--";
    }

    return "{$var_part}; {$cond_part}; {$step_part}";
  }

  function compile_variables($options)
  {
    $options = array_merge($options, array('top' => TRUE));

    list($this->from_c, $this->from_var) = $this->from->cache($options, LEVEL_LIST);
    list($this->to_c, $this->to_var) = $this->to->cache($options, LEVEL_LIST);

    if ($step = del($options, 'step'))
    {
      list($this->step, $this->step_var) = $step->cache($options, LEVEL_LIST);
    }

    list($this->from_num, $this->to_num) = array(preg_match(SIMPLENUM, $this->from_var), preg_match(SIMPLENUM, $this->to_var));

    if (isset($this->step_var) && $this->step_var)
    {
      $this->step_num = preg_match(SIMPLENUM, $this->step_var);
    }
  }
}

?>
