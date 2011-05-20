<?php

namespace CoffeeScript;

class yyParam extends yyBase
{
  public $children = array('name', 'value');

  function __construct($name, $value = NULL, $splat = NULL)
  {
    $this->name = $name;
    $this->value = $value;
    $this->splat = $splat;
  }

  function as_reference($options)
  {
    if ($this->reference)
    {
      return $this->reference;
    }

    $node = $this->name;

    if ($node->this)
    {
      $node = $node->properties[0]->name;

      if ($node->value->reserved)
      {
        $node = new yyLiteral('_'.$node->value);
      }
    }
    else if ($node->is_complex())
    {
      $node = new yyLiteral($options['scope']->free_variable('arg'));
    }

    $node = new yyValue($node);

    if ($this->splat)
    {
      $node = new yySplat($node);
    }

    return ($this->reference = $node);
  }

  function compile($options)
  {
    return $this->name->compile($options, LEVEL_LIST);
  }

  function is_complex()
  {
    return $this->name->is_complex();
  }
}

?>
