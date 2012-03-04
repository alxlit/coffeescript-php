
# CoffeeScript PHP

A port of the [CoffeeScript](http://jashkenas.github.com/coffee-script/)
compiler to PHP.

## Status

CoffeeScript version **1.1.1** has been fully ported over. Compiled code nearly
matches the reference 100%, except for the occasional extra newline.

## FAQ

#### Why not modify the original compiler to emit PHP?

The compiler itself depends on Jison, which is written in JavaScript, so you'd
have to do something about that... More generally speaking, it'd be much more
work to try and sort out all the differences between JavaScript and PHP (object
model, core classes/libraries, etc), I imagine. There's also much less incentive
to do all that work server side, where the choice to not use one language is,
if not easy, at least available.

#### What parser generator are you using?

Since there's no PHP port of Bison (which the reference compiler uses), we're
using a port of Lemon called [ParserGenerator](http://pear.php.net/package/PHP_ParserGenerator).

It's included locally since the PEAR package is unmaintained and seems to be
broken. In addition, some minor changes have been made to the parser template 
(Lempar.php) and the actual generator.

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

