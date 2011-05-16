
# CoffeeScript-PHP

A port of the [CoffeeScript](http://jashkenas.github.com/coffee-script/) 
*compiler* to PHP. It's incomplete, porting is really tedious so feel free to
contribute.

### Complete

* Grammar (src/grammar.y) for the parser generator (using Lemon, since there's
  no port of Bison to PHP).
* Lexer (build/coffeescript/lexer.php).
* Some of the nodes (15/31) (build/coffeescript/nodes/).

The nodes are the focus at the moment. Right now I'm just blindly porting them 
to PHP, once that's done they'll undoubtedly need tweaking.

### Todo

* Scope manager.
* Rewriter.
* Test cases. I figure these will just compare to references created by the 
  original compiler, nothing fancy.

## FAQ

### Why not modify the original compiler to emit PHP?

Much more work to do... There are too many differences between JavaScript and 
PHP (object model, underlying libraries, etc) that would need to be seriously 
thought about. There's also less incentive/reason to do stuff like that server-side.

## Requirements

PHP 5.3+ (uses namespaces, anonymous functions).

## Usage

In your projects,

```php
$js = CoffeeScript\compile('path/to/source.coffee');
```

