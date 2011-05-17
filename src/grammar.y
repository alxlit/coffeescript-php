%start_symbol   root
%declare_class  { class Parser }

%right          YY_POST_IF.
%right          YY_IF YY_ELSE YY_FOR YY_DO YY_WHILE YY_UNTIL YY_LOOP YY_SUPER YY_CLASS.
%right          YY_FORIN YY_FOROF YY_BY YY_WHEN.
%right          YY_EQUALS YY_COLON YY_COMPOUND_ASSIGN YY_RETURN YY_THROW YY_EXTENDS.
%nonassoc       YY_INDENT YY_OUTDENT.
%left           YY_LOGIC.
%left           YY_COMPARE.
%left           YY_RELATION.
%left           YY_SHIFT.
%left           YY_PLUS YY_MINUS.
%right          YY_UNARY.
%left           YY_EXISTENTIAL.
%nonassoc       YY_INCREMENT YY_DECREMENT.
%left           YY_CALL_START YY_CALL_END.
%left           YY_ACCESSOR YY_EXISTENTIAL_ACCESSOR YY_PROTOTYPE.

root(A) ::=                         . { A = new yyBlock; }
root(A) ::= body(B)                 . { A = B; }
root(A) ::= block(B) YY_TERMINATOR  . { A = B; }

body(A) ::= line(B)                       . { A = yyBlock::wrap(B); }
body(A) ::= body(B) YY_TERMINATOR line(C) . { A = B->push(C); }
body(A) ::= body(B) YY_TERMINATOR         . { A = B; }

line(A) ::= expression(B) . { A = B; }
line(A) ::= statement(B)  . { A = B; }

statement(A) ::= return(B)        . { A = B; }
statement(A) ::= throw(B)         . { A = B; }
statement(A) ::= comment(B)       . { A = B; }
statement(A) ::= YY_STATEMENT(B)  . { A = new yyLiteral(B); }

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

block(A) ::= YY_INDENT YY_OUTDENT         . { A = new yyBlock; }
block(A) ::= YY_INDENT body(B) YY_OUTDENT . { A = B; }

identifier(A) ::= YY_IDENTIFIER(B)  . { A = new yyLiteral(B); }

alphanumeric(A) ::= YY_NUMBER(B)  . { A = new yyLiteral(B); }
alphanumeric(A) ::= YY_STRING(B)  . { A = new yyLiteral(B); }

literal(A) ::= alphanumeric(B)  . { A = B; } 
literal(A) ::= YY_JS(B)         . { A = new yyLiteral(B); }
literal(A) ::= YY_REGEX(B)      . { A = new yyLiteral(B); }
literal(A) ::= YY_BOOL(B)       . { $val = new yyLiteral(B); $val->is_undefined(A === 'undefined'); A = $val; }

assign(A) ::= assignable(B) YY_EQUALS expression(C)             . { A = new yyAssign(B, C); }
assign(A) ::= assignable(B) YY_INDENT expression(C) YY_OUTDENT  . { A = new yyAssign(B, C); }

assignObj(A) ::= objAssignable(B)                                             . { A = new yyValue(B); }
assignObj(A) ::= objAssignable(B) YY_COLON expression(C)                      . { A = new yyAssign(new yyValue(B), C, 'object'); }
assignObj(A) ::= objAssignable(B) YY_COLON YY_INDENT expression(C) YY_OUTDENT . { A = new yyAssign(new yyValue(B), C, 'object'); }
assignObj(A) ::= comment(B)                                                   . { A = B; }

objAssignable(A) ::= identifier(B)    . { A = B; }
objAssignable(A) ::= alphanumeric(B)  . { A = B; }
objAssignable(A) ::= thisProperty(B)  . { A = B; }

return(A) ::= YY_RETURN expression(B) . { A = new yyReturn(B); }
return(A) ::= YY_RETURN               . { A = new yyReturn; }

comment(A) ::= YY_HERECOMMENT(B)  . { A = new yyComment(B); }

code(A) ::= YY_PARAM_START paramList(B) YY_PARAM_END funcGlyph(C) block(D)  . { A = new yyCode(B, D, C); }
code(A) ::= funcGlyph(B) block(C)                                           . { A = new yyCode(array(), C, B); }

funcGlyph(A) ::= YY_FUNC        . { A = 'func'; }
funcGlyph(A) ::= YY_BOUND_FUNC  . { A = 'boundfunc'; }

optComma(A) ::=             . { A = ''; }
optComma(A) ::= YY_COMMA(B) . { A = B; }

paramList(A) ::=                                . { A = array(); }
paramList(A) ::= param(B)                       . { A = array(B); }
paramList(A) ::= paramList(B) YY_COMMA param(C) . { A = array_merge(B, C); }

param(A) ::= paramVar(B)                          . { A = new yyParam(B); }
param(A) ::= paramVar(B) YY_ELLIPSIS              . { A = new yyParam(B, NULL, TRUE); }
param(A) ::= paramVar(B) YY_EQUALS expression(C)  . { A = new yyParam(B, C); }

paramVar(A) ::= identifier(B)   . { A = B; }
paramVar(A) ::= thisProperty(B) . { A = B; }
paramVar(A) ::= array(B)        . { A = B; }
paramVar(A) ::= object(B)       . { A = B; }

splat(A) ::= expression(B) YY_RANGE_EXCLUSIVE . { A = new yySplat(B); }

simpleAssignable(A) ::= identifier(B)             . { A = new yyValue(B); }
simpleAssignable(A) ::= value(B) accessor(C)      . { A = B->push(C); }
simpleAssignable(A) ::= invocation(B) accessor(C) . { A = new yyValue(B, array(C)); }
simpleAssignable(A) ::= thisProperty(B)           . { A = B; }

assignable(A) ::= simpleAssignable(B) . { A = B; }
assignable(A) ::= array(B)            . { A = new yyValue(B); }
assignable(A) ::= object(B)           . { A = new yyValue(B); }

value(A) ::= assignable(B)    . { A = B; }
value(A) ::= literal(B)       . { A = new yyValue(B); }
value(A) ::= parenthetical(B) . { A = new yyValue(B); }
value(A) ::= range(B)         . { A = new yyValue(B); }
value(A) ::= this(B)          . { A = B; }

accessor(A) ::= YY_ACCESSOR identifier(B)             . { A = new yyAccess(B); }
accessor(A) ::= YY_EXISTENTIAL_ACCESSOR identifier(B) . { A = new yyAccess(B, 'soak'); }
accessor(A) ::= YY_PROTOTYPE identifier(B)            . { A = new yyAccess(B, 'proto'); }
accessor(A) ::= YY_PROTOTYPE                          . { A = new yyAccess(new yyLiteral('prototype')); }
accessor(A) ::= index(B)                              . { A = B; }
accessor(A) ::= slice(B)                              . { A = new yySlice(B); }

index(A) ::= YY_INDEX_START expression(B) YY_INDEX_END  . { A = new yySlice(B); }
index(A) ::= YY_INDEX_SOAK index(B)                     . { A = extend(B, array('soak' => TRUE)); }
index(A) ::= YY_INDEX_PROTO index(B)                    . { A = extend(B, array('proto' => TRUE)); }

object(A) ::= YY_OBJECT_START assignList(B) optComma(C) YY_OBJECT_END   . { A = new yyObject(C, B->generated()); }

assignList(A) ::=                                                                     . { A = array(); }
assignList(A) ::= assignObj(B)                                                        . { A = array(B); }
assignList(A) ::= assignList(B) YY_COMMA assignObj(C)                                 . { A = array_merge(B, C); }
assignList(A) ::= assignList(B) optComma YY_TERMINATOR assignObj(C)                   . { A = array_merge(B, C); }
assignList(A) ::= assignList(B) optComma YY_INDENT assignList(C) optComma YY_OUTDENT  . { A = array_merge(B, C); }

class(A) ::= YY_CLASS                                                   . { A = new yyClass; }
class(A) ::= YY_CLASS block(B)                                          . { A = new yyClass(NULL, NULL, B); }
class(A) ::= YY_CLASS YY_EXTENDS value(B)                               . { A = new yyClass(NULL, B); }
class(A) ::= YY_CLASS YY_EXTENDS value(B) block(C)                      . { A = new yyClass(NULL, B, C); } 
class(A) ::= YY_CLASS simpleAssignable(B)                               . { A = new yyClass(B); }
class(A) ::= YY_CLASS simpleAssignable(B) block(C)                      . { A = new yyClass(B, NULL, C); }
class(A) ::= YY_CLASS simpleAssignable(B) YY_EXTENDS value(C)           . { A = new yyClass(B, C); }
class(A) ::= YY_CLASS simpleAssignable(B) YY_EXTENDS value(C) block(D)  . { A = new yyClass(B, C, D); }

invocation(A) ::= value(B) optFuncExist(C) arguments(D)       . { A = new yyCall(B, D, C); }
invocation(A) ::= invocation(B) optFuncExist(C) arguments(D)  . { A = new yyCall(B, D, C); }
invocation(A) ::= YY_SUPER                                    . { A = new yyCall('super', array(new yySplat(new yyLiteral('arguments')))); }
invocation(A) ::= YY_SUPER arguments(B)                       . { A = new yyCall('super', B); }

optFuncExist(A) ::=               . { A = YY_FALSE; }
optFuncExist(A) ::= YY_FUNC_EXIST . { A = YY_TRUE; }

arguments(A) ::= YY_CALL_START YY_CALL_END                      . { A = array(); }
arguments(A) ::= YY_CALL_START argList(B) optComma YY_CALL_END  . { A = B; }

this(A) ::= YY_THIS       . { A = new yyValue(new yyLiteral('this')); }
this(A) ::= YY_AMPERSAND  . { A = new yyValue(new yyLiteral('this')); }

thisProperty(A) ::= YY_AMPERSAND identifier(B)  . { A = new yyValue(new yyLiteral('this'), array(new yyAccess(B)), 'this'); }

array(A) ::= YY_ARRAY_START YY_ARRAY_END                      . { A = new yyArr(array()); }
array(A) ::= YY_ARRAY_START argList(B) optComma YY_ARRAY_END  . { A = new yyArr(B); }

rangeDots(A) ::= YY_RANGE_INCLUSIVE . { A = 'inclusive'; }
rangeDots(A) ::= YY_RANGE_EXCLUSIVE . { A = 'exclusive'; }

range(A) ::= YY_ARRAY_START expression(B) rangeDots(C) expression(D) YY_ARRAY_END . { A = new yyRange(B, D, C); }

slice(A) ::= YY_INDEX_START expression(B) rangeDots(C) expression(D) YY_INDEX_END . { A = new yyRange(B, D, C); }
slice(A) ::= YY_INDEX_START expression(B) rangeDots(C) YY_INDEX_END               . { A = new yyRange(B, NULL, C); }
slice(A) ::= YY_INDEX_START rangeDots(B) expression(C) YY_INDEX_END               . { A = new yyRange(NULL, C, B); }

argList(A) ::= arg(B)                                                       . { A = array(B); }
argList(A) ::= argList(B) YY_COMMA arg(C)                                   . { A = array_merge(B, array(C)); }
argList(A) ::= argList(B) optComma YY_TERMINATOR arg(C)                     . { A = array_merge(B, array(C)); }
argList(A) ::= YY_INDENT argList(B) optComma YY_OUTDENT                     . { A = B; }
argList(A) ::= argList(B) optComma YY_INDENT argList(C) optComma YY_OUTDENT . { A = array_merge(B, C); }

arg(A) ::= expression(B)  . { A = B; }
arg(A) ::= splat(B) . { A = B; }

simpleArgs(A) ::= expression(B)                         . { A = B; }
simpleArgs(A) ::= simpleArgs(B) YY_COMMA expression(C)  . { A = array(B, C); }

try(A) ::= YY_TRY block(B)                              . { A = new yyTry(B); }
try(A) ::= YY_TRY block(B) catch(C)                     . { A = new yyTry(B, C[0], C[1]);  }
try(A) ::= YY_TRY block(B) YY_FINALLY block(C)          . { A = new yyTry(B, NULL, NULL, C); }
try(A) ::= YY_TRY block(B) catch(C) YY_FINALLY block(D) . { A = new yyTry(B, C[0], C[1], D); }

catch(A) ::= YY_CATCH identifier(B) block(C)  . { A = array(B, C); }

throw(A) ::= YY_THROW expression(B) . { A = new yyThrow(B); }

parenthetical(A) ::= YY_PAREN_START body(B) YY_PAREN_END                      . { A = new yyParens(B); }
parenthetical(A) ::= YY_PAREN_START YY_INDENT body(B) YY_OUTDENT YY_PAREN_END . { A = new yyParens(B); }

whileSource(A) ::= YY_WHILE expression(B)                       . { A = new yyWhile(B); }
whileSource(A) ::= YY_WHILE expression(B) YY_WHEN expression(C) . { A = new yyWhile(B, array('guard' => C)); }
whileSource(A) ::= YY_UNTIL expression(B)                       . { A = new yyWhile(B, array('invert' => TRUE)); }
whileSource(A) ::= YY_UNTIL expression(B) YY_WHEN expression(C) . { A = new yyWhile(B, array('invert' => TRUE, 'guard' => C)); }

while(A) ::= whileSource(B) block(C)      . { A = B->add_body(C); }
while(A) ::= statement(B) whileSource(C)  . { A = C->add_body(yyBlock::wrap(array(B))); }
while(A) ::= expression(B) whileSource(C) . { A = C->add_body(yyBlock::wrap(array(B))); }
while(A) ::= loop(B)                      . { A = B; }

loop(A) ::= YY_LOOP block(B)      . { A = new yyWhile(new yyLiteral('true')); A->add_body(B); }
loop(A) ::= YY_LOOP expression(B) . { A = new yyWhile(new yyLiteral('true')); A->add_body(yyBlock::wrap(B)); }

for(A) ::= statement(B) forBody(C)  . { A = new yyFor(B, C); }
for(A) ::= expression(B) forBody(C) . { A = new yyFor(B, C); }
for(A) ::= forBody(B) block(C)      . { A = new yyFor(C, B); }

forBody(A) ::= YY_FOR range(B)          . { A = array('source' => new yyValue(B)); }
forBody(A) ::= forStart(B) forSource(C) . { C['own'] = B['own']; C['name'] = B[0]; C['index'] = B[1]; A = C; }

forStart(A) ::= YY_FOR forVariables(B)        . { A = B; }
forStart(A) ::= YY_FOR YY_OWN forVariables(B) . { B['own'] = TRUE; A = B; }

forValue(A) ::= identifier(B) . { A = B; }
forValue(A) ::= array(B)      . { A = new yyValue(B); }
forValue(A) ::= object(B)     . { A = new yyValue(B); }

forVariables(A) ::= forValue(B)                       . { A = array(B, NULL,  'own' => NULL); }
forVariables(A) ::= forValue(B) YY_COMMA forValue(C)  . { A = array(B, C,     'own' => NULL); }

forSource(A) ::= YY_FORIN expression(B)                                           . { A = array('source' => B); }
forSource(A) ::= YY_FOROF expression(B)                                           . { A = array('source' => B, 'object' => TRUE); }
forSource(A) ::= YY_FORIN expression(B) YY_WHEN expression(C)                     . { A = array('source' => B, 'guard' => C); }
forSource(A) ::= YY_FOROF expression(B) YY_WHEN expression(C)                     . { A = array('source' => B, 'guard' => C, 'object' => TRUE); }
forSource(A) ::= YY_FORIN expression(B) YY_BY expression(C)                       . { A = array('source' => B, 'step' => C); }
forSource(A) ::= YY_FORIN expression(B) YY_WHEN expression(C) YY_BY expression(D) . { A = array('source' => B, 'guard' => C, 'step' => D); }
forSource(A) ::= YY_FORIN expression(B) YY_BY expression(C) YY_WHEN expression(D) . { A = array('source' => B, 'step' => C, 'guard' => D); }

switch(A) ::= YY_SWITCH expression(B) YY_INDENT whens(C) YY_OUTDENT                   . { A = new yySwitch(B, C); }
switch(A) ::= YY_SWITCH expression(B) YY_INDENT whens(C) YY_ELSE block(D) YY_OUTDENT  . { A = new yySwitch(B, C, D); }
switch(A) ::= YY_SWITCH YY_INDENT whens(B) YY_OUTDENT                                 . { A = new yySwitch(NULL, B); }
switch(A) ::= YY_SWITCH YY_INDENT whens(B) YY_ELSE block(C) YY_OUTDENT                . { A = new yySwitch(NULL, B, C); }

whens(A) ::= when(B)          . { A = B; }
whens(A) ::= whens(B) when(C) . { A = array_merge(B, C); }

when(A) ::= YY_LEADING_WHEN simpleArgs(B) block(C)                . { A = array(array(B, C)); }
when(A) ::= YY_LEADING_WHEN simpleArgs(B) block(C) YY_TERMINATOR  . { A = array(array(B, C)); }

ifBlock(A) ::= YY_IF(B) expression(C) block(D)                    . { A = new yyIf(C, D, array('type' => B)); }
ifBlock(A) ::= ifBlock(B) YY_ELSE YY_IF(C) expression(D) block(E) . { A = B->add_else(new yyIf(D, E, array('type' => C))); }

if(A) ::= ifBlock(B)                                . { A = B; }
if(A) ::= ifBlock(B) YY_ELSE block(C)               . { A = B->addElse(C); }
if(A) ::= statement(B) YY_POST_IF(C) expression(D)  . { A = new yyIf(D, yyBlock::wrap(array(B)), array('type' => C, 'statement' => TRUE)); }
if(A) ::= expression(B) YY_POST_IF(C) expression(D) . { A = new yyIf(D, yyBlock::wrap(array(B)), array('type' => C, 'statement' => TRUE)); }

operation(A) ::= YY_UNARY(B) expression(C)                                                    . { A = new yyOp(B, C); }
operation(A) ::= YY_MINUS(B) expression(C)                                                    . { A = new yyOp(B, C); /* prec: 'UNARY'; */ }
operation(A) ::= YY_PLUS(B) expression(C)                                                     . { A = new yyOp(B, C); /* prec: 'UNARY'; */ }
operation(A) ::= YY_DECREMENT(B) simpleAssignable(C)                                          . { A = new yyOp(B, C); }
operation(A) ::= YY_INCREMENT(B) simpleAssignable(C)                                          . { A = new yyOp(B, C); }
operation(A) ::= simpleAssignable(B) YY_DECREMENT(C)                                          . { A = new yyOp(C, B, NULL, TRUE); }
operation(A) ::= simpleAssignable(B) YY_INCREMENT(C)                                          . { A = new yyOp(C, B, NULL, TRUE); }
operation(A) ::= expression(B) YY_EXISTENTIAL                                                 . { A = new yyExistence(B); }
operation(A) ::= expression(B) YY_PLUS(C) expression(D)                                       . { A = new yyOp(C, B, D); }
operation(A) ::= expression(B) YY_MINUS(C) expression(D)                                      . { A = new yyOp(C, B, D); }
operation(A) ::= expression(B) YY_MATH(C) expression(D)                                       . { A = new yyOp(C, B, D); }
operation(A) ::= expression(B) YY_SHIFT(C) expression(D)                                      . { A = new yyOp(C, B, D); }
operation(A) ::= expression(B) YY_COMPARE(C) expression(D)                                    . { A = new yyOp(C, B, D); }
operation(A) ::= expression(B) YY_LOGIC(C) expression(D)                                      . { A = new yyOp(C, B, D); }
operation(A) ::= expression(B) YY_RELATION(C) expression(D)                                   . { if (C{0} === '!') { A = new yyOp(substr(C, 1), B, D); A->invert(); } else { A = new yyOp(C, B, D); } }
operation(A) ::= simpleAssignable(B) YY_COMPOUND_ASSIGN(C) expression(D)                      . { A = new yyAssign(B, D, C); }
operation(A) ::= simpleAssignable(B) YY_COMPOUND_ASSIGN(C) YY_INDENT expression(D) YY_OUTDENT . { A = new yyAssign(B, D, C); }
operation(A) ::= simpleAssignable(B) YY_EXTENDS expression(C)                                 . { A = new yyExtends(B, C); }



