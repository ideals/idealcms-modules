<?php

namespace Cabinet\Structure\Registration\Site;

use Ideal\Core\Admin\Router;
use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Field;

class Plugin {

    public function onPostDispatch(Router $router)
    {
        $pos = strpos($_SERVER['REQUEST_URI'], 'regPart2');
        if($pos !== false){

        }
    }
}