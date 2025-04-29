<?php
namespace Bitrixone\Alt2Img;
use Bitrixone\Alt2Img\Settings\DefaultSettings;

class Test {
    public static function showSomething() {
        echo 'Привет, человек!';
        DefaultSettings::adminsRights();
    }
}