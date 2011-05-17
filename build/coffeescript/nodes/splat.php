<?php

namespace CoffeeScript;

class yySplat extends yyBase
{
  public $children = array('name');

  function __construct($name)
  {
    if (isset($name->compile))
    {
      $this->name = $name;
    }
    else
    {
      $this->name = new yyLiteral($name);
    }
  }

  function assigns($name)
  {
    return $this->name->assigns($name);
  }

  function compile($options)
  {
    if (isset($this->index))
    {
      return $this->compile_param($options);
    }
    else
    {
      return $this->name->compile($options);
    }
  }

  static function compile_splatted_array($options, $list, $apply)
  {
    $index = -1;

    while (($node = $list[++$index]) && !($node instanceof yySplat))
    {
      continue;
    }

    if ($index >= count($list))
    {
      return '';
    }

    if (count($list) === 1)
    {
      $code = $list[0]->compile($options, LEVEL_LIST);

      if ($apply)
      {
        return $code;
      }
      
      return utility('slice').".call({$code})";
    }

    $args = array_slice($list, $index);

    foreach ($args as $i => $node)
    {
      $code = $node->compile($options, LEVEL_LIST);
      $args[$i] = (node instanceof yySplat) ? utility('slice').".call({$code})" : "[{$code}]";
    }

    if ($index === 0)
    {
      return $args[0].'.concat('.implode(', ', array_slice($args, 1)).')';
    }

    $base = array();

    foreach (array_slice($list, 0, $index) as $node)
    {
      $base[] = $node->compile($options, LEVEL_LIST);
    }

    return '['.implode(', ', $base).'].concat('.implode(', ', $args).')';
  }

  function is_assignable()
  {
    return TRUE;
  }
}

?>
