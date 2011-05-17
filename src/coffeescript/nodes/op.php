<?php

namespace CoffeeScript;

class yyOp extends yyBase
{
  /*
  constructor: (op, first, second, flip ) ->
    return new In first, second if op is 'in'
    if op is 'do'
      call = new Call first, first.params or []
      call.do = yes
      return call
    if op is 'new'
      return first.newInstance() if first instanceof Call and not first.do
      first = new Parens first   if first instanceof Code and first.bound or first.do
    @operator = CONVERSIONS[op] or op
    @first    = first
    @second   = second
    @flip     = !!flip
    return this

  # The map of conversions from CoffeeScript to JavaScript symbols.
  CONVERSIONS =
    '==': '==='
    '!=': '!=='
    'of': 'in'

  # The map of invertible operators.
  INVERSIONS =
    '!==': '==='
    '===': '!=='

  children: ['first', 'second']

  isSimpleNumber: NO

  isUnary: ->
    not @second
    
  isComplex: ->
    not (@isUnary() and (@operator in ['+', '-'])) or @first.isComplex()

  # Am I capable of
  # [Python-style comparison chaining](http://docs.python.org/reference/expressions.html#notin)?
  isChainable: ->
    @operator in ['<', '>', '>=', '<=', '===', '!==']

  invert: ->
    if @isChainable() and @first.isChainable()
      allInvertable = yes
      curr = this
      while curr and curr.operator
        allInvertable and= (curr.operator of INVERSIONS)
        curr = curr.first
      return new Parens(this).invert() unless allInvertable
      curr = this
      while curr and curr.operator
        curr.invert = !curr.invert
        curr.operator = INVERSIONS[curr.operator]
        curr = curr.first
      this
    else if op = INVERSIONS[@operator]
      @operator = op
      if @first.unwrap() instanceof Op
        @first.invert()
      this
    else if @second
      new Parens(this).invert()
    else if @operator is '!' and (fst = @first.unwrap()) instanceof Op and
                                  fst.operator in ['!', 'in', 'instanceof']
      fst
    else
      new Op '!', this

  unfoldSoak: (o) ->
    @operator in ['++', '--', 'delete'] and unfoldSoak o, this, 'first'

  compileNode: (o) ->
    return @compileUnary     o if @isUnary()
    return @compileChain     o if @isChainable() and @first.isChainable()
    return @compileExistence o if @operator is '?'
    @first.front = @front
    code = @first.compile(o, LEVEL_OP) + ' ' + @operator + ' ' +
           @second.compile(o, LEVEL_OP)
    if o.level <= LEVEL_OP then code else "(#{code})"

  # Mimic Python's chained comparisons when multiple comparison operators are
  # used sequentially. For example:
  #
  #     bin/coffee -e 'console.log 50 < 65 > 10'
  #     true
  compileChain: (o) ->
    [@first.second, shared] = @first.second.cache o
    fst = @first.compile o, LEVEL_OP
    code = "#{fst} #{if @invert then '&&' else '||'} #{ shared.compile o } #{@operator} #{ @second.compile o, LEVEL_OP }"
    "(#{code})"

  compileExistence: (o) ->
    if @first.isComplex()
      ref = new Literal o.scope.freeVariable 'ref'
      fst = new Parens new Assign ref, @first
    else
      fst = @first
      ref = fst
    new If(new Existence(fst), ref, type: 'if').addElse(@second).compile o

  # Compile a unary **Op**.
  compileUnary: (o) ->
    parts = [op = @operator]
    parts.push ' ' if op in ['new', 'typeof', 'delete'] or
                      op in ['+', '-'] and @first instanceof Op and @first.operator is op
    @first = new Parens @first if op is 'new' and @first.isStatement o
    parts.push @first.compile o, LEVEL_OP
    parts.reverse() if @flip
    parts.join ''

  toString: (idt) ->
  super idt, @constructor.name + ' ' + @operator
  */
}

?>
