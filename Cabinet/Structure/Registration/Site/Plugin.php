<?php

namespace Cabinet\Structure\Registration\Site;

use Ideal\Core\Site\Router;
use Ideal\Structure;
use Ideal\Core\Config;

class Plugin
{

    public function onPreDispatch(Router $router)
    {
        $config = Config::getInstance();

        if ($_GET['url'] == 'cabinet') {
            $id = $config->getStructureByName('Cabinet_Registration');
            $id = $id['ID'];

            $router->setPath(array(array('structure' => 'Cabinet_Registration', 'url' => 'cabinet', 'ID' => $id)));
            $router->setControllerName('\\Cabinet\\Structure\\Registration\\Site\\Controller');
        }

        if ($_GET['url'] == 'logout') {
            session_start();
            unset($_SESSION['login']['user']);
            unset($_SESSION['login']['input']);
            header('Refresh: 0; url=/');;

        }

        if ($_GET['url'] == 'spec-info') {

            $id = $config->getStructureByName('Cabinet_Registration');
            $id = $id['ID'];
            $router->setPath(array(array('structure' => 'Cabinet_Registration', 'url' => 'cabinet', 'ID' => $id)));
            $router->setControllerName('\\Cabinet\\Structure\\Registration\\Site\\Controller');
        }
    }
}
