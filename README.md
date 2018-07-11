# Middleware
```
send() --------------------> to() --------------------> finish()
         ^   ^   ^                          ^   ^   ^
         |   |   |                          |   |   |
         |   |   +------- through(Fn) ------+   |   |
         |   +-----------     ...     ----------+   |
         +--------------- through(F1) --------------+
```

## Install
```
composer require nulastudio\middleware
```

## Usage
```php
$middleware = new nulastudio\Middleware;

$pack = $middleware->send(1, 2, 3)->to(function (...$goods) {
    echo "goods received\n";
    foreach ($goods as &$good) {
        $good++;
    }
    return $goods;
})->through([function ($next, ...$params) {
    echo "before middleware 1\n";
    $return = $next(...$params);
    echo "after middleware 1\n";
    return $return;
}, function ($next, ...$params) {
    echo "before middleware 2\n";
    $return = $next(...$params);
    echo "after middleware 2\n";
    return $return;
}])->through(function ($next, ...$params) {
    echo "before middleware 3\n";
    $return = $next(...$params);
    echo "after middleware 3\n";
    return $return;
})->finish(function ($origin, $goods) {
    var_dump($origin, $goods);
    return $goods;
})->pack();

$return = $pack();

echo "final return\n";

var_dump($return);

/* outputs */
/*
before middleware 1
before middleware 2
before middleware 3
goods received
after middleware 3
after middleware 2
after middleware 1
array(3) {
  [0]=>
  int(1)
  [1]=>
  int(2)
  [2]=>
  int(3)
}
array(3) {
  [0]=>
  int(2)
  [1]=>
  int(3)
  [2]=>
  int(4)
}
final return
array(3) {
  [0]=>
  int(2)
  [1]=>
  int(3)
  [2]=>
  int(4)
}
*/
```

### overridable params
```php
$middleware = new nulastudio\Middleware;

$pack = $middleware->send()->to(function (...$goods) {
    var_dump($goods);
    return $goods;
})->pack();

$return = $pack(1,2,3);

// or

$pack = $middleware->send(4,5,6)->to(function (...$goods) {
    var_dump($goods);
    return $goods;
})->pack();

$return = $pack(1,2,3);

// output:
// 1,2,3

```

## Reference
* Middleware `send` (...$goods)
    - `goods` will be passed to `$destination`.
* Middleware `to` (callable $destination)
    - `destination` `destination` gets two arguments ($next, $params) and must return new values or `$params`. `$next` is the next middleware callback, `$params` is the value returned from previous middleware callback, the first middleware callback will get the value passed to `send`.
* Middleware `through` ($anywhere, bool $reset = false)
    - `anywhere` a callback or set of callbacks.
    - `reset` empty the through callbacks before add a callback.
* Middleware `finish` (callable $todo)
    - `todo` what to do when finished. `todo` gets two arguments ($origin, $goods) and must return new values or `$goods`. `$origin` is the value passed to `send`, `$goods` is the value returned from the callable which is passed to `to`.
* callable `pack` ()
    - pack middleware into a callable function.