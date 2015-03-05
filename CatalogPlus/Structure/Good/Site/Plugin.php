<?php
namespace CatalogPlus\Structure\Good\Site;

use Ideal\Core\Site;
use Ideal\Field;
use CatalogPlus;

class Plugin
{
    public function onPreDispatch($router)
    {
        if (!isset($_SERVER['REQUEST_URI'])) {
            return $router;
        }

        $url = explode('/', $_SERVER['REQUEST_URI']);
        if ($url[0] != 'tovar') {
            return $router;
        }


        $url = basename($_SERVER['REQUEST_URI']);

        $good = new CatalogPlus\Structure\Good\Site\Model('6');
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
