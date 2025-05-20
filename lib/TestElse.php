<?php
namespace Bitrixone\Alt2Img;

class TestElse {
    public static function showSomeThingElse () {
        echo 'Сам ты такой, нехороший человечек!<br>';
        echo 'А шо такое?';
        $obj = new Test();
        $obj->showSomething();
        Test::showSomething();
    }
}