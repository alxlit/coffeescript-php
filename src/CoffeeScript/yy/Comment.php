<?php

namespace CoffeeScript;

class yy_Comment extends yy_Base
{
  function constructor()
  {
    list($comment) = args(func_get_args(), 1);

    $this->comment = $comment;

    return $this;
  }

  function compile_node($options, $level = NULL)
  {
    $code = '/*'.multident($this->comment, $this->tab)."\n{$this->tab}*/\n";

    if ($level === LEVEL_TOP || $options['level'] === LEVEL_TOP)
    {
      $code = $options['indent'].$code;
    }

    return $code;
  }

  function is_statement($options = NULL)
  {
    return TRUE;
  }

  function make_return($res = NULL)
  {
    return $this;
  }
}

?>
