<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Moon\Container\Container;

$container = new Container();
$container->single(C::class, function (Container $container) {
    $c =  new C;
    $c->ccc = 200;
    return $c;
});
$container->alias('a', 'Aaa');

$a = $container->make('a');
var_dump($a);

//var_dump($container);

class Aaa
{
    public function __construct(B $b, C $c, $d = 1)
    {
        $this->b = $b;
        $this->c = $c;
        $this->d = $d;
    }
}

class B
{
    public function __construct(C $c)
    {
        $this->c = $c;
    }
}

class C
{

}