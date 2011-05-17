<?php

namespace CoffeeScript;

class yyExtends extends yyBase
{
  public $children = array('child', 'parent');

  function __construct($child, $parent)
  {
    $this->child = $child;
    $this->parent = $parent;
  }

  function compile($options)
  {
    utility('hasProp');

    $tmp = new yyCall(new yyValue(new yyLiteral(utility('extends'))), array($this->child, $this->parent));

    return $tmp->compile($options);
  }
}

?>
