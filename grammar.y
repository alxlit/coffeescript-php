%start_symbol   root
%declare_class  { class Parser }

%syntax_error { 
  throw new SyntaxError(
    'unexpected '.$this->tokenName($yymajor).' in '.self::$FILE.':'
  . (self::$LINE + 1).'.'
  );
}

%right      YY_POST_IF.
%right      YY_IF YY_ELSE YY_FOR YY_WHILE YY_UNTIL YY_LOOP YY_SUPER YY_CLASS.
%right      YY_FORIN YY_FOROF YY_BY YY_WHEN.
%right      YY_EQUALS YY_COLON YY_COMPOUND_ASSIGN YY_RETURN YY_THROW YY_EXTENDS.
%nonassoc   YY_INDENT YY_OUTDENT.
%left       YY_LOGIC.
%left       YY_COMPARE.
%left       YY_RELATION.
%left       YY_SHIFT.
%left       YY_MATH.
%left       YY_PLUS YY_MINUS.
%right      YY_UNARY.
%left       YY_EXISTENTIAL.
%nonassoc   YY_INCREMENT YY_DECREMENT.
%left       YY_CALL_START YY_CALL_END.
%left       YY_ACCESSOR YY_EXISTENTIAL_ACCESSOR YY_PROTOTYPE.

root(A) ::=                         . { A = yy('Block'); }
root(A) ::= body(B)                 . { A = B; }
root(A) ::= block(B) YY_TERMINATOR  . { A = B; }

body(A) ::= line(B)                       . { A = yy_Block::wrap(array(B)); }
body(A) ::= body(B) YY_TERMINATOR line(C) . { A = B->push(C); }
body(A) ::= body(B) YY_TERMINATOR         . { A = B; }

line(A) ::= expression(B) . { A = B; }
line(A) ::= statement(B)  . { A = B; }

statement(A) ::= return(B)        . { A = B; }
statement(A) ::= comment(B)       . { A = B; }
statement(A) ::= YY_STATEMENT(B)  . { A = yy('Literal', B); }

expression(A) ::= value(B)        . { A = B; }
expression(A) ::= invocation(B)   . { A = B; }
expression(A) ::= code(B)         . { A = B; }
expression(A) ::= operation(B)    . { A = B; }
expression(A) ::= assign(B)       . { A = B; }
expression(A) ::= if(B)           . { A = B; }
expression(A) ::= try(B)          . { A = B; }
expression(A) ::= while(B)        . { A = B; }
expression(A) ::= for(B)          . { A = B; }
expression(A) ::= switch(B)       . { A = B; }
expression(A) ::= class(B)        . { A = B; }
expression(A) ::= throw(B)        . { A = B; }

block(A) ::= YY_INDENT YY_OUTDENT         . { A = yy('Block'); }
block(A) ::= YY_INDENT body(B) YY_OUTDENT . { A = B; }

identifier(A) ::= YY_IDENTIFIER(B)  . { A = yy('Literal', B); }

alphanumeric(A) ::= YY_NUMBER(B)  . { A = yy('Literal', B); }
alphanumeric(A) ::= YY_STRING(B)  . { A = yy('Literal', B); }

literal(A) ::= alphanumeric(B)  . { A = B; }
literal(A) ::= YY_JS(B)         . { A = yy('Literal', B); }
literal(A) ::= YY_REGEX(B)      . { A = yy('Literal', B); }
literal(A) ::= YY_DEBUGGER(B)   . { A = yy('Literal', B); }

literal(A) ::= YY_BOOL(B) . {
  $val = yy('Literal', B);
  $val->is_undefined = B === 'undefined';
  A = $val;
}

assign(A) ::= assignable(B) YY_EQUALS expression(C)                       . { A = yy('Assign', B, C); }
assign(A) ::= assignable(B) YY_EQUALS YY_TERMINATOR expression(C)         . { A = yy('Assign', B, C); }
assign(A) ::= assignable(B) YY_EQUALS YY_INDENT expression(C) YY_OUTDENT  . { A = yy('Assign', B, C); }

assignObj(A) ::= objAssignable(B)                                             . { A = yy('Value', B); }
assignObj(A) ::= objAssignable(B) YY_COLON expression(C)                      . { A = yy('Assign', yy('Value', B), C, 'object'); }
assignObj(A) ::= objAssignable(B) YY_COLON YY_INDENT expression(C) YY_OUTDENT . { A = yy('Assign', yy('Value', B), C, 'object'); }
assignObj(A) ::= comment(B)                                                   . { A = B; }

objAssignable(A) ::= identifier(B)    . { A = B; }
objAssignable(A) ::= alphanumeric(B)  . { A = B; }
objAssignable(A) ::= thisProperty(B)  . { A = B; }

return(A) ::= YY_RETURN expression(B) . { A = yy('Return', B); }
return(A) ::= YY_RETURN               . { A = yy('Return'); }

comment(A) ::= YY_HERECOMMENT(B)  . { A = yy('Comment', B); }

code(A) ::= YY_PARAM_START paramList(B) YY_PARAM_END funcGlyph(C) block(D)  . { A = yy('Code', B, D, C); }
code(A) ::= funcGlyph(B) block(C)                                           . { A = yy('Code', array(), C, B); }

funcGlyph(A) ::= YY_FUNC        . { A = 'func'; }
funcGlyph(A) ::= YY_BOUND_FUNC  . { A = 'boundfunc'; }

optComma(A) ::=             . { A = ''; }
optComma(A) ::= YY_COMMA(B) . { A = B; }

paramList(A) ::=                                . { A = array(); }
paramList(A) ::= param(B)                       . { A = array(B); }
paramList(A) ::= paramList(B) YY_COMMA param(C) . { A = array_merge(B, array(C)); }

param(A) ::= paramVar(B)                          . { A = yy('Param', B); }
param(A) ::= paramVar(B) YY_RANGE_EXCLUSIVE       . { A = yy('Param', B, NULL, TRUE); }
param(A) ::= paramVar(B) YY_EQUALS expression(C)  . { A = yy('Param', B, C); }

paramVar(A) ::= identifier(B)   . { A = B; }
paramVar(A) ::= thisProperty(B) . { A = B; }
paramVar(A) ::= array(B)        . { A = B; }
paramVar(A) ::= object(B)       . { A = B; }

splat(A) ::= expression(B) YY_RANGE_EXCLUSIVE . { A = yy('Splat', B); }

simpleAssignable(A) ::= identifier(B)             . { A = yy('Value', B); }
simpleAssignable(A) ::= value(B) accessor(C)      . { A = B->add(C); }
simpleAssignable(A) ::= invocation(B) accessor(C) . { A = yy('Value', B, is_object(C) ? array(C) : (array) C); }
simpleAssignable(A) ::= thisProperty(B)           . { A = B; }

assignable(A) ::= simpleAssignable(B) . { A = B; }
assignable(A) ::= array(B)            . { A = yy('Value', B); }
assignable(A) ::= object(B)           . { A = yy('Value', B); }

value(A) ::= assignable(B)    . { A = B; }
value(A) ::= literal(B)       . { A = yy('Value', B); }
value(A) ::= parenthetical(B) . { A = yy('Value', B); }
value(A) ::= range(B)         . { A = yy('Value', B); }
value(A) ::= this(B)          . { A = B; }

accessor(A) ::= YY_ACCESSOR identifier(B)             . { A = yy('Access', B); }
accessor(A) ::= YY_EXISTENTIAL_ACCESSOR identifier(B) . { A = yy('Access', B, 'soak'); }
accessor(A) ::= YY_PROTOTYPE identifier(B)            . { A = array( yy('Access', yy('Literal', 'prototype')), yy('Access', B) ); }
accessor(A) ::= YY_PROTOTYPE                          . { A = yy('Access', yy('Literal', 'prototype')); }
accessor(A) ::= index(B)                              . { A = B; }

index(A) ::= YY_INDEX_START indexValue(B) YY_INDEX_END  . { A = B; }
index(A) ::= YY_INDEX_SOAK index(B)                     . { A = extend(B, array('soak' => TRUE)); }

indexValue(A) ::= expression(B)   . { A = yy('Index', B); }
indexValue(A) ::= slice(B)        . { A = yy('Slice', B); }

object(A) ::= YY_OBJECT_START assignList(B) optComma YY_OBJECT_END . {
  $object_start = $this->yystack[$this->yyidx - 3]->minor;
  $generated = isset($object_start->generated) ? $object_start->generated : FALSE;

  A = yy('Obj', B, $generated);
}

assignList(A) ::=                                                                     . { A = array(); }
assignList(A) ::= assignObj(B)                                                        . { A = array(B); }
assignList(A) ::= assignList(B) YY_COMMA assignObj(C)                                 . { B[] = C; A = B; }
assignList(A) ::= assignList(B) optComma YY_TERMINATOR assignObj(C)                   . { B[] = C; A = B; }
assignList(A) ::= assignList(B) optComma YY_INDENT assignList(C) optComma YY_OUTDENT  . { A = array_merge(B, C); }

class(A) ::= YY_CLASS                                                   . { A = yy('Class'); }
class(A) ::= YY_CLASS block(B)                                          . { A = yy('Class', NULL, NULL, B); }
class(A) ::= YY_CLASS YY_EXTENDS expression(B)                          . { A = yy('Class', NULL, B); }
class(A) ::= YY_CLASS YY_EXTENDS expression(B) block(C)                 . { A = yy('Class', NULL, B, C); } 
class(A) ::= YY_CLASS simpleAssignable(B)                               . { A = yy('Class', B); }
class(A) ::= YY_CLASS simpleAssignable(B) block(C)                      . { A = yy('Class', B, NULL, C); }
class(A) ::= YY_CLASS simpleAssignable(B) YY_EXTENDS expression(C)      . { A = yy('Class', B, C); }
class(A) ::= YY_CLASS simpleAssignable(B) YY_EXTENDS expression(C) block(D)  . { A = yy('Class', B, C, D); }

invocation(A) ::= value(B) optFuncExist(C) arguments(D)       . { A = yy('Call', B, D, C); }
invocation(A) ::= invocation(B) optFuncExist(C) arguments(D)  . { A = yy('Call', B, D, C); }
invocation(A) ::= YY_SUPER                                    . { A = yy('Call', 'super', array(yy('Splat', yy('Literal', 'arguments')))); }
invocation(A) ::= YY_SUPER arguments(B)                       . { A = yy('Call', 'super', B); }

optFuncExist(A) ::=               . { A = FALSE; }
optFuncExist(A) ::= YY_FUNC_EXIST . { A = TRUE; }

arguments(A) ::= YY_CALL_START YY_CALL_END                      . { A = array(); }
arguments(A) ::= YY_CALL_START argList(B) optComma YY_CALL_END  . { A = B; }

this(A) ::= YY_THIS     . { A = yy('Value', yy('Literal', 'this')); }
this(A) ::= YY_AT_SIGN  . { A = yy('Value', yy('Literal', 'this')); }

thisProperty(A) ::= YY_AT_SIGN identifier(B)  . { A = yy('Value', yy('Literal', 'this'), array(yy('Access', B)), 'this'); }

array(A) ::= YY_ARRAY_START YY_ARRAY_END                      . { A = yy('Arr', array()); }
array(A) ::= YY_ARRAY_START argList(B) optComma YY_ARRAY_END  . { A = yy('Arr', B); }

rangeDots(A) ::= YY_RANGE_INCLUSIVE . { A = 'inclusive'; }
rangeDots(A) ::= YY_RANGE_EXCLUSIVE . { A = 'exclusive'; }

range(A) ::= YY_ARRAY_START expression(B) rangeDots(C) expression(D) YY_ARRAY_END . { A = yy('Range', B, D, C); }

slice(A) ::= expression(B) rangeDots(C) expression(D)   . { A = yy('Range', B, D, C); }
slice(A) ::= expression(B) rangeDots(C)                 . { A = yy('Range', B, NULL, C); }
slice(A) ::= rangeDots(B) expression(C)                 . { A = yy('Range', NULL, C, B); }
slice(A) ::= rangeDots(B)                               . { A = yy('Range', NULL, NULL, B); }

argList(A) ::= arg(B)                                                       . { A = array(B); }
argList(A) ::= argList(B) YY_COMMA arg(C)                                   . { A = array_merge(B, array(C)); }
argList(A) ::= argList(B) optComma YY_TERMINATOR arg(C)                     . { A = array_merge(B, array(C)); }
argList(A) ::= YY_INDENT argList(B) optComma YY_OUTDENT                     . { A = B; }
argList(A) ::= argList(B) optComma YY_INDENT argList(C) optComma YY_OUTDENT . { A = array_merge(B, C); }

arg(A) ::= expression(B)  . { A = B; }
arg(A) ::= splat(B)       . { A = B; }

simpleArgs(A) ::= expression(B)                         . { A = B; }
simpleArgs(A) ::= simpleArgs(B) YY_COMMA expression(C)  . { A = array(B, C); }

try(A) ::= YY_TRY block(B)                              . { A = yy('Try', B); }
try(A) ::= YY_TRY block(B) catch(C)                     . { A = yy('Try', B, C[0], C[1]);  }
try(A) ::= YY_TRY block(B) YY_FINALLY block(C)          . { A = yy('Try', B, NULL, NULL, C); }
try(A) ::= YY_TRY block(B) catch(C) YY_FINALLY block(D) . { A = yy('Try', B, C[0], C[1], D); }

catch(A) ::= YY_CATCH identifier(B) block(C)  . { A = array(B, C); }

throw(A) ::= YY_THROW expression(B) . { A = yy('Throw', B); }

parenthetical(A) ::= YY_PAREN_START body(B) YY_PAREN_END                      . { A = yy('Parens', B); }
parenthetical(A) ::= YY_PAREN_START YY_INDENT body(B) YY_OUTDENT YY_PAREN_END . { A = yy('Parens', B); }

whileSource(A) ::= YY_WHILE expression(B)                       . { A = yy('While', B); }
whileSource(A) ::= YY_WHILE expression(B) YY_WHEN expression(C) . { A = yy('While', B, array('guard' => C)); }
whileSource(A) ::= YY_UNTIL expression(B)                       . { A = yy('While', B, array('invert' => TRUE)); }
whileSource(A) ::= YY_UNTIL expression(B) YY_WHEN expression(C) . { A = yy('While', B, array('invert' => TRUE, 'guard' => C)); }

while(A) ::= whileSource(B) block(C)      . { A = B->add_body(C); }
while(A) ::= statement(B) whileSource(C)  . { A = C->add_body(yy_Block::wrap(array(B))); }
while(A) ::= expression(B) whileSource(C) . { A = C->add_body(yy_Block::wrap(array(B))); }
while(A) ::= loop(B)                      . { A = B; }

loop(A) ::= YY_LOOP block(B)      . { A = yy('While', yy('Literal', 'true')); A->add_body(B); }
loop(A) ::= YY_LOOP expression(B) . { A = yy('While', yy('Literal', 'true')); A->add_body(yy_Block::wrap(B)); }

for(A) ::= statement(B) forBody(C)  . { A = yy('For', B, C); }
for(A) ::= expression(B) forBody(C) . { A = yy('For', B, C); }
for(A) ::= forBody(B) block(C)      . { A = yy('For', C, B); }

forBody(A) ::= YY_FOR range(B)          . { A = array('source' => yy('Value', B)); }
forBody(A) ::= forStart(B) forSource(C) . { C['own'] = isset(B['own']) ? B['own'] : NULL; C['name'] = B[0]; C['index'] = isset(B[1]) ? B[1] : NULL; A = C; }

forStart(A) ::= YY_FOR forVariables(B)        . { A = B; }
forStart(A) ::= YY_FOR YY_OWN forVariables(B) . { B['own'] = TRUE; A = B; }

forValue(A) ::= identifier(B) . { A = B; }
forValue(A) ::= array(B)      . { A = yy('Value', B); }
forValue(A) ::= object(B)     . { A = yy('Value', B); }

forVariables(A) ::= forValue(B)                       . { A = array(B); }
forVariables(A) ::= forValue(B) YY_COMMA forValue(C)  . { A = array(B, C); }

forSource(A) ::= YY_FORIN expression(B)                                           . { A = array('source' => B); }
forSource(A) ::= YY_FOROF expression(B)                                           . { A = array('source' => B, 'object' => TRUE); }
forSource(A) ::= YY_FORIN expression(B) YY_WHEN expression(C)                     . { A = array('source' => B, 'guard' => C); }
forSource(A) ::= YY_FOROF expression(B) YY_WHEN expression(C)                     . { A = array('source' => B, 'guard' => C, 'object' => TRUE); }
forSource(A) ::= YY_FORIN expression(B) YY_BY expression(C)                       . { A = array('source' => B, 'step' => C); }
forSource(A) ::= YY_FORIN expression(B) YY_WHEN expression(C) YY_BY expression(D) . { A = array('source' => B, 'guard' => C, 'step' => D); }
forSource(A) ::= YY_FORIN expression(B) YY_BY expression(C) YY_WHEN expression(D) . { A = array('source' => B, 'step' => C, 'guard' => D); }

switch(A) ::= YY_SWITCH expression(B) YY_INDENT whens(C) YY_OUTDENT                   . { A = yy('Switch', B, C); }
switch(A) ::= YY_SWITCH expression(B) YY_INDENT whens(C) YY_ELSE block(D) YY_OUTDENT  . { A = yy('Switch', B, C, D); }
switch(A) ::= YY_SWITCH YY_INDENT whens(B) YY_OUTDENT                                 . { A = yy('Switch', NULL, B); }
switch(A) ::= YY_SWITCH YY_INDENT whens(B) YY_ELSE block(C) YY_OUTDENT                . { A = yy('Switch', NULL, B, C); }

whens(A) ::= when(B)          . { A = B; }
whens(A) ::= whens(B) when(C) . { A = array_merge(B, C); }

when(A) ::= YY_LEADING_WHEN simpleArgs(B) block(C)                . { A = array(array(B, C)); }
when(A) ::= YY_LEADING_WHEN simpleArgs(B) block(C) YY_TERMINATOR  . { A = array(array(B, C)); }

ifBlock(A) ::= YY_IF(B) expression(C) block(D)                    . { A = yy('If', C, D, array('type' => B)); }
ifBlock(A) ::= ifBlock(B) YY_ELSE YY_IF(C) expression(D) block(E) . { A = B->add_else(yy('If', D, E, array('type' => C))); }

if(A) ::= ifBlock(B)                                . { A = B; }
if(A) ::= ifBlock(B) YY_ELSE block(C)               . { A = B->add_else(C); }
if(A) ::= statement(B) YY_POST_IF(C) expression(D)  . { A = yy('If', D, yy_Block::wrap(array(B)), array('type' => C, 'statement' => TRUE)); }
if(A) ::= expression(B) YY_POST_IF(C) expression(D) . { A = yy('If', D, yy_Block::wrap(array(B)), array('type' => C, 'statement' => TRUE)); }

operation(A) ::= YY_UNARY(B) expression(C)                  . { A = yy('Op', B, C); }
operation(A) ::= YY_MINUS(B) expression(C)                  . { A = yy('Op', B, C); /* prec: 'UNARY'; */ }
operation(A) ::= YY_PLUS(B) expression(C)                   . { A = yy('Op', B, C); /* prec: 'UNARY'; */ }

operation(A) ::= YY_DECREMENT(B) simpleAssignable(C)        . { A = yy('Op', B, C); }
operation(A) ::= YY_INCREMENT(B) simpleAssignable(C)        . { A = yy('Op', B, C); }
operation(A) ::= simpleAssignable(B) YY_DECREMENT(C)        . { A = yy('Op', C, B, NULL, TRUE); }
operation(A) ::= simpleAssignable(B) YY_INCREMENT(C)        . { A = yy('Op', C, B, NULL, TRUE); }

operation(A) ::= expression(B) YY_EXISTENTIAL               . { A = yy('Existence', B); }

operation(A) ::= expression(B) YY_PLUS(C) expression(D)     . { A = yy('Op', C, B, D); }
operation(A) ::= expression(B) YY_MINUS(C) expression(D)    . { A = yy('Op', C, B, D); }
operation(A) ::= expression(B) YY_MATH(C) expression(D)     . { A = yy('Op', C, B, D); }
operation(A) ::= expression(B) YY_SHIFT(C) expression(D)    . { A = yy('Op', C, B, D); }
operation(A) ::= expression(B) YY_COMPARE(C) expression(D)  . { A = yy('Op', C, B, D); }
operation(A) ::= expression(B) YY_LOGIC(C) expression(D)    . { A = yy('Op', C, B, D); }

operation(A) ::= expression(B) YY_RELATION(C) expression(D) . {
  if (C{0} === '!') {
    A = yy('Op', substr(C, 1), B, D);
    A = A->invert();
  }
  else {
    A = yy('Op', C, B, D);
  }
}

operation(A) ::= simpleAssignable(B) YY_COMPOUND_ASSIGN(C) expression(D)                      . { A = yy('Assign', B, D, C); }
operation(A) ::= simpleAssignable(B) YY_COMPOUND_ASSIGN(C) YY_INDENT expression(D) YY_OUTDENT . { A = yy('Assign', B, D, C); }
operation(A) ::= simpleAssignable(B) YY_EXTENDS expression(C)                                 . { A = yy('Extends', B, C); }



