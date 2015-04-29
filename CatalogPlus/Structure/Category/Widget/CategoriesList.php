<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace CatalogPlus\Structure\Category\Widget;

use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Core\Widget;
use Ideal\Field;

/**
 * Виджет для получение иерархии категорий товара заданной вложенности
 *
 * Пример использования:
 *
 *     $cats = new CategoriesList($model);
 *     $cats->setLvl(4);
 *     $cats->setPrefix('/shop/categories');
 *     $vars['categories'] = $cats->getData();
 */
class CategoriesList extends Widget
{
    /** @var int Уровень вложенности, до которого выбираются категории */
    protected $lvl = 4;

    /** @var array Массив, позволяющий избежать получения из БД категорий, если они были получены вне виджета */
    protected $menuList = array();

    /**
     * Получение списка категорий продукции
     *
     * @return array Список категорий товаров
     */
    public function getData()
    {
        $menuList = $this->getList();

        $path = $this->model->getPath();
        $object = array_pop($path);
        $prev = array_pop($path);
        $digits = (isset($this->model->params['digits'])) ? $this->model->params['digits'] : 3;
        $smallCidActive = '';
        if ($prev['structure'] == 'CatalogPlus_Category') {
            $smallCidActive = substr($object['cid'], 0, $digits * $object['lvl']);
        }

        $lvl = 1;
        $config = Config::getInstance();
        $menuUrl = array('0' => array('url' => $config->structures[0]['url']));
        $url = new Field\Url\Model();

        $menu = array();
        foreach ($menuList as $k => $v) {
            $menu[$k] = $v;
            if ($v['lvl'] > $lvl) {
                if ($v['url'] != '/') {
                    $menuUrl[] = $menuList[$k - 1];
                }
                $url->setParentUrl($menuUrl);
            } elseif ($v['lvl'] < $lvl) {
                $menuUrl = array_slice($menuUrl, 0, ($v['lvl'] - $lvl));
                $url->setParentUrl($menuUrl);
            }
            $lvl = $v['lvl'];

            // Определяем активен ли данный пункт меню
            $menu[$k]['isActivePage'] = 0;
            $currentCid = substr($v['cid'], 0, $v['lvl'] * $digits);
            if (isset($object['lvl']) && $object['lvl'] >= $lvl
                && substr($smallCidActive, 0, strlen($currentCid)) == $currentCid
            ) {
                $menu[$k]['isActivePage'] = 1;
            }
            if (isset($v['is_skip']) && $v['is_skip'] == 0) {
                if (isset($v['url_full']) && $v['url_full'] != '') {
                    $menu[$k]['link'] = $v['url_full'];
                } else {
                    $menu[$k]['link'] = $this->prefix . $url->getUrl($v) . $this->query;
                }
            }
        }
        $categoryList = $this->getSubCategories($menu);
        return $categoryList;
    }

    /**
     * Рекурсивный метод для построения иерархии вложенных категорий
     *
     * @param array $menu Массив, в котором строится иерархия
     * @return array Массив с построенной иерархией дочерних элементов
     */
    protected function getSubCategories(&$menu)
    {
        // Записываем в массив первый элемент
        $categoryList = array(
            array_shift($menu)
        );

        $prev = $categoryList[0]['lvl'];

        while (count($menu) != 0) {
            $m = reset($menu);
            if ($m['lvl'] == $prev) {
                $categoryList[] = array_shift($menu);
                $prev = $m['lvl'];
            } elseif ($m['lvl'] > $prev) {
                end($categoryList);
                $key = key($categoryList);
                $categoryList[$key]['subCategoryList'] = $this->getSubCategories($menu);
            } else {
                return $categoryList;
            }
        }
        return $categoryList;

    }

    /**
     * Получение модели страницы с данными
     *
     * @return \Ideal\Core\Site\Model Модель страницы с данными
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Выполняет запрос к БД для получения списка категорий
     *
     * Метод сделан максимально просто, чтобы было легче модифицировать получение
     * категорий в наследниках виджета.
     *
     * @return array Список категорий из БД
     */
    public function getList()
    {
        if (!empty($this->menuList)) {
            return $this->menuList;
        }

        $db = Db::getInstance();
        $config = Config::getInstance();

        // Считываем список категорий продукции
        $table = $config->db['prefix'] . 'catalogplus_structure_category';
        $_sql = "SELECT *
                 FROM {$table}
                 WHERE is_active=1 AND is_not_menu=0 AND lvl<{$this->lvl}
                 ORDER BY cid";
        $menuList = $db->select($_sql);

        return $menuList;
    }

    /**
     * Установка уровня вложенности для выборки категорий
     *
     * @param int $lvl Уровень вложенности, до которого выбираются категории
     */
    public function setLvl($lvl)
    {
        $this->lvl = $lvl;
    }

    /**
     * Метод позволяет задать список категорий товара, если он уже был определён вне виджета
     *
     * @param array $menuList Массив с плоским списком категорий товара
     */
    public function setMenuList($menuList)
    {
        $this->menuList = $menuList;
    }
}
