<?php
namespace CatalogPlus\Structure\Good\Site;

use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Core\Site;
use Ideal\Field;

class Plugin
{
    public function onPreDispatch($router)
    {
        if (!isset($_GET['url'])) return $router;

        $url = explode('/', $_GET['url']);
        if ($url[0] != 'tovar') return $router;


        $url = basename($_GET['url']);

        $good = new \CatalogPlus\Structure\Good\Site\Model('6');
        $result = $good->detectPageByUrl($url, array());
        if ($result == 404) {
            // Товара с таким URL не нашли
            $router->is404 = true;
            return $router;
        }

        $good->detectPath();

        $router->setModel($good);

        return $router;
    }
}
