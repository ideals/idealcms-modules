<?php
namespace CatalogPlus\Structure\Category\Widget;

use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Field;
use Ideal\Core\Widget;

class CategoriesList extends Widget
{
    protected $lvl = 4;

    /** @var  \Ideal\Core\Site\Model */
    protected $model;


    public function setLvl($lvl)
    {
        $this->lvl = $lvl;
    }

    /**
     * Получение списка категорий продукции
     * @param bool $menuList
     * @return array
     */
    public function getData($menuList = false)
    {
        if ($menuList === false) {
            $menuList = $this->getList();
        }
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
     * @return mixed
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
}
