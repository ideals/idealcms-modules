<?php

namespace Cabinet\Structure\User\Site;

use Ideal\Core\Site\Router;
use Ideal\Structure;
use Ideal\Core\Config;

class Plugin
{

    public function onPreDispatch(Router $router)
    {
        $config = Config::getInstance();

        if ($_GET['url'] == 'cabinet') {
            $id = $config->getStructureByName('Cabinet_User');
            $id = $id['ID'];

            $router->setPath(array(array('structure' => 'Cabinet_User', 'url' => 'cabinet', 'ID' => $id)));
            $router->setControllerName('\\Cabinet\\Structure\\User\\Site\\Controller');
        }

        if ($_GET['url'] == 'logout') {
            session_start();
            unset($_SESSION['login']['user']);
            unset($_SESSION['login']['input']);
            header('Refresh: 0; url=/');

        }
    }
}
