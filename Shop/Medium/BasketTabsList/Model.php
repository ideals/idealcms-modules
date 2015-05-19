<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Shop\Medium\BasketTabsList;

use Ideal\Core\Config;
use Ideal\Medium;

class Model extends Medium\AbstractModel
{
    /** @var string папка с модами */
    protected $dir;
    /** @var string шаблон для поиска */
    protected $mask;
    /** @var array список доступных шаблонов табов */
    protected $list;

    /**
     * Получение списка всех доступных шаблонов для таба корзины
     * @return array
     */
    public function getList()
    {
        $config = Config::getInstance();

        $this->dir = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/Mods';
        $this->mask = 'Shop/Structure/Basket/Site/Tabs/*.twig';
        $this->list = array();
        // Поиск шаблонов в папке с модами
        $this->checkTabs(' (Mods)');

        // Поиск шаблонов в папке с изменеными модами
        $this->dir = $this->dir . '.c';
        $this->checkTabs(' (Mods.c)');

        // Сортируем массив
        natsort($this->list);

        return $this->list;
    }

    protected function checkTabs($postfixName = '')
    {
        $this->tmp = $postfixName;
        array_walk(glob($this->dir . '/' . $this->mask), function ($value, $key) {
            // Проверяем наличие файла
            if ($rFile = fopen($value, 'r')) {
                $k = substr($value, mb_strlen($this->dir) + 1);
                $str = fgets($rFile); // считываем первую строку файла
                // Проверяем если название название шаблона-таба в шаблоне
                if (preg_match('/\{\#(.{3,})\#\}/iu', $str)) {
                    $v = trim($str, '{}# ');
                } else {
                    // Если описания не оказалось в шаблоне используем названия файла шаблона-таба
                    $v = substr($value, strripos($value, '/') + 1, -5);
                }
                $this->list[$k] = $v . $this->tmp; // указываем в какой папке Mods находиться данный шаблон-таб
                fclose($rFile);
            }
        });
        unset($this->tmp);
    }
}
