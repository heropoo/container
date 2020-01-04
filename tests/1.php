<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Moon\Container\Container;

$container = new Container();
$container->single('a', function (Container $container){
    return $container->make(A::class, true);
});

$a = $container->make('a');
var_dump($a);
//var_dump($container);

class A
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