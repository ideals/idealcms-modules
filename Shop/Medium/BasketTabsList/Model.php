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
        $this->checkTabs();

        foreach ($this->list as $k => $v) {
            $fileName = explode('/', $k);
            $fileName = end($fileName);
            if (strpos($fileName, '_') == 0) {
                continue;
            }
            unset($this->list[$k]);
            if (strpos($fileName, '.twig')) {
                $fileName = substr($fileName, 0, -5);
            }
            $structure = $config->getStructureByName($fileName);
            if ($structure != false) {
                $this->list[$k] = $structure['name'] . ' (модуль)';
            }

        }

        // Сортируем массив
        natsort($this->list);

        return $this->list;
    }

    protected function checkTabs()
    {
        $pattern = $this->dir . '{,.c}/' . $this->mask;

        $func = function (&$value) {
            // Проверяем наличие файла
            if ($rFile = fopen($value, 'r')) {
                $k = substr($value, strpos($value, 'Shop/'));
                $str = fgets($rFile); // считываем первую строку файла
                // Проверяем если название название шаблона-таба в шаблоне
                if (preg_match('/\{\#(.{3,})\#\}/iu', $str, $str)) {
                    $v = trim($str[1], '{}# ');
                } else {
                    // Если описания не оказалось в шаблоне используем названия файла шаблона-таба
                    $v = substr($value, strripos($value, '/') + 1, -5);
                }
                $this->list[$k] = $v; // указываем в какой папке Mods находиться данный шаблон-таб
                fclose($rFile);
            }
        };

        $glob = glob($pattern, GLOB_BRACE);

        return array_walk($glob, $func);
    }
}
