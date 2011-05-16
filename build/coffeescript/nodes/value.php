<?php

namespace CoffeeScript;

class yyValue extends yyBase
{
  public $children = array('base', 'properties');

  function __construct($base, $props = NULL, $tag = NULL)
  {
    if ( ! $props && $base instanceof yyValue)
    {
      return $base;
    }

    $this->base = $base;
    $this->properties = is_null($props) ? array() : $props;
    $this->{$tag} = ! is_null($tag);
  }

  function assigns($name)
  {
    return ! count($this->properties) && $this->base->assigns($name);
  }

  function cache_reference($options)
  {
    $name = last($this->properties);

    if (count($this->properties) < 2 && ! $this->base->is_complex() && ($name && $name->is_complex()))
    {
      return array($this, $this);
    }

    $base = new yyValue($this->base, array_slice($this->properties, 0, -1));

    if ($base->is_complex())
    {
      $bref = new yyLiteral($options['scope']->free_variable('base'));
      $base = new yyValue(new yyParens(new yyAssign($bref, $base)));
    }

    if ( ! $name)
    {
      return array($base, $bref);
    }

    if ($name->is_complex())
    {
      $nref = new yyLiteral($options['scope']->free_variable('name'));
      $name = new yyIndex(new yyAssign($nref, $name->index));
      $nref = new yyIndex($nref);
    }

    $base[] = $name;

    return array($base, new yyValue(isset($bref) ? $bref : $base->base, array(isset($nref) ? $nref : $name)));
  }

  function compile_node($options)
  {
    $this->base->front = $this->front;
    $props = $this->properties;

    $code = $this->base->compile($options, count($props) ? LEVEL_ACCESS : NULL);

    if ($props[0] instanceof yyAccess && $this->is_simple_number())
    {
      $code = "($code)";
    }

    foreach ($props as $prop)
    {
      $code .= $prop->compile($options);
    }

    return $code;
  }

  function push($prop)
  {
    $this->properties[] = $prop;

    return $this;
  }

  function has_properties()
  {
    return count($this->properties) > 0;
  }

  function is_array()
  {
    return ! count($this->properties) && $this->base instanceof yyArr;
  }

  function is_assignable()
  {
    return $this->has_properties() || $this->base->is_assignable();
  }

  function is_atomic()
  {
    foreach (array_merge($this->properties, (array) $this->base) as $node)
    {
      if ($node->soak || $node instanceof yyCall)
      {
        return FALSE;
      }
    }

    return TRUE;
  }

  function is_complex()
  {
    return $this->has_properties() || $this->base->is_complex();
  }

  function is_object($only_generated)
  {
    if (count($this->properties))
    {
      return FALSE;
    }

    return ($this->base instanceof yyObj) && ( ! $only_generated || $this->base->generated);
  }

  function is_simple_number()
  {
    return ($this->base instanceof yyLiteral) && preg_match(SIMPLENUM, $this->base->value);
  }

  function is_splice()
  {
    return last($this->properties) instanceof yySlice;
  }

  function is_statement($options)
  {
    return ! count($this->properties) && $this->base->is_statement($options);
  }

  function jumps($options)
  {
    return ! $this->properties->length && $this->base->jumps($options);
  }

  function make_return()
  {
    if (count($this->properties))
    {
      return parent::make_return();
    }
    else
    {
      return $this->base->make_return();
    }
  }

  function unfold_soak()
  {
    if (isset($this->unfolded_soak))
    {
      return $this->unfolded_soak;
    }

    $result = NULL;

    if (($ifn = $this->base->unfold_soak($options)))
    {
      $ifn->body->properties[] = $this->properties;
      return $ifn;
    }

    foreach ($this->properties as $i => $prop)
    {
      if ($prop->soak)
      {
        $prop->soak = FALSE;

        $fst = new yyValue($this->base, array_slice($this->properties, 0, $i));
        $snd = new yyValue($this->base, array_slice($this->properties, $i));

        if ($fst->is_complex())
        {
          $ref = new yyLiteral($options['scope']->free_variable('ref'));
          $fst = new yyParens(new yyAssign($ref, $fst));
          $snd->base = $ref;
        }

        return yyIf(new yyExistence($fst), $snd, array('soak' => TRUE));
      }
    }

    return ($this->unfolded_soak = $result);
  }

  function unwrap()
  {
    return count($this->properties) ? $this : $this->base;
  }

}

?>
