<?php
namespace Bitrixone\Alt2Img;

class TestElse {
    public static function showSomeThingElse () {
        echo 'Сам гавно!';
        $obj = new Test();
        $obj->showSomething();
        Test::showSomething();
    }
}