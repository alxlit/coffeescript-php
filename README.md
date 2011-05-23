
# CoffeeScript PHP

A port of the [CoffeeScript](http://jashkenas.github.com/coffee-script/) 
**compiler** to PHP. It's incomplete, and porting is really tedious so feel free
to contribute.

### Complete

* Grammar for the parser generator (using a PHP port of 
  [Lemon](http://pear.php.net/package/PHP_ParserGenerator/), since there's no 
  port of Bison to PHP as far as I know).
* Lexer (passes all test cases).

### Todo

* Rewriter (bugfixes).
* Nodes (21/31 have been drafted).
* Scope regulator.

## FAQ

#### Why not modify the original compiler to emit PHP?

The compiler itself depends on Jison, which is written in JavaScript, so you'd
have to do something about that... More generally speaking, it'd be too much 
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

In your projects,

```php
<?php

try
{
  $js = CoffeeScript\compile('path/to/source.coffee');
}
catch (Exception e) {}

?>
```

## Development

To rebuild the parser run `php make.php`. Running tests is easy, just put the 
project directory into /srv/http (or whatever location you're running your
local server from) and navigate to http://localhost/coffeescript-php/test/.

You'll need to have shorttags enabled (sorry).

