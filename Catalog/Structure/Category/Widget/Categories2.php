<?php
namespace Catalog\Structure\Category\Widget;

use Ideal\Core\Db;
use Ideal\Core\Config;

/**
 * Виджет получения двухуровневого списка категорий товара из БД
 *
 */
class Categories2 extends \Ideal\Core\Widget
{
    /**
     * Получение двухуровневого меню категорий в структуризированном виде
     *
     * Ссылки на пункты меню прописываются в свойстве link (без href).
     * У первого уровня в свойстве subMenu находится массив, содержащий элементы
     * второго уровня.
     *
     * @return array Список категорий из БД
     */
    public function getData()
    {
        // Определяем кол-во разрядов на один уровень cid для структуры категорий
        $config = Config::getInstance();
        $category = $config->getStructureByName('Catalog_Category');
        $digits = $category['params']['digits'];

        $menuList = $this->getList();

        // Раскладываем считанное меню во вложенные массивы по cid и lvl
        $num = 0;
        $menu = array();
        $url = new \Ideal\Field\Url\Model();
        foreach ($menuList as $v) {
            if ($v['lvl'] == 1) {
                $num = substr($v['cid'], 0, $digits);
                $parentUrl = (isset($v['is_skip']) && $v['is_skip']) ? '' : $v['url'];
                $v['link'] = $url->getUrlWithPrefix($v, $this->prefix);
                $v['subMenu'] = array();
                $menu[$num] = $v;
            }
            if ($v['lvl'] == 2) {
                $prefix = $this->prefix . '/' . $parentUrl;
                $v['link'] = $url->getUrlWithPrefix($v, $prefix);
                $menu[$num]['subMenu'][] = $v;
            }
        }
        unset($menuList);

        // Определение активных пунктов меню
        $pageData = $this->model->getPageData();
        if (isset($pageData['prev_structure']) && $pageData['prev_structure'] == $this->prevStructure) {
            $activeUrl = substr($pageData['cid'], 0, $digits);
            if (!isset($menu[$activeUrl])) {
                return $menu;
            }
            $menu[$activeUrl]['activeUrl'] = 1;
            $menu[$activeUrl]['classActiveUrl'] = 'activeMenu';
            foreach ($menu[$activeUrl]['subMenu'] as $k => $elem) {
                $elem['cid'] = rtrim($elem['cid'], '0');
                $cid = substr($pageData['cid'], 0, strlen($elem['cid']));
                if ($elem['cid'] == $cid) {
                    $menu[$activeUrl]['subMenu'][$k]['activeUrl'] = 1;
                }
            }
        }

        return $menu;
    }

    /**
     * Выполняет запрос к БД для получения списка категорий
     *
     * Метод сделан максимально просто, чтобы было легче модифицировать получение
     * категорий в наследниках виджета.
     *
     * @return array Список категорий из БД
     */
    protected function getList()
    {
        $db = Db::getInstance();
        $config = Config::getInstance();
        $_table = $config->db['prefix'] . 'catalog_structure_category';
        $_sql = "SELECT * FROM {$_table}
                    WHERE (lvl = 1 OR lvl = 2) AND is_active=1 AND is_not_menu=0
                          AND prev_structure='{$this->prevStructure}' ORDER BY cid";
        $menuList = $db->select($_sql);
        return $menuList;
    }
}
