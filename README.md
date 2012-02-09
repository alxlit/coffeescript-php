
# CoffeeScript PHP

A port of the [CoffeeScript](http://jashkenas.github.com/coffee-script/)
compiler (version 1.1.1) to PHP. It's **really close** to being finished, there
are just a few obscure bugs that need hunting down.

## Status

### Complete

* Grammar for the parser generator (a PHP port of
  [Lemon](http://pear.php.net/package/PHP_ParserGenerator/), since there's no 
  port of Bison to PHP afaik)
* Lexer
* Lexical scope regulator
* Rewriter
* Parser

### Todo

* Bugs
* Make it `E_STRICT`

## FAQ

#### Why not modify the original compiler to emit PHP?

The compiler itself depends on Jison, which is written in JavaScript, so you'd
have to do something about that... More generally speaking, it'd be much more
work to try and sort out all the differences between JavaScript and PHP (object
model, core classes/libraries, etc), I imagine. There's also much less incentive
to do all that work server side, where the choice to not use one language is,
if not easy, at least available.

#### Why is ParserGenerator included locally?

The PEAR package is unmaintained and seems to be broken. In addition, some 
minor (undocumented) changes have been made to the parser template (Lempar.php)
and the actual generator.

## Requirements

PHP 5.3+ (uses namespaces, anonymous functions).

## Usage

At the moment the API is pretty basic. It'll probably be expanded a bit in the 
future.

```php
<?php

$coffee = file_get_contents('path/to/source.coffee');

try
{
  $js = CoffeeScript\compile($coffee);
}
catch (Exception e) {}

?>
```

## Development

To rebuild the parser run `php make.php`. Running tests is easy, just drop the
entire folder into localhost and go to coffeescript-php/test/.

