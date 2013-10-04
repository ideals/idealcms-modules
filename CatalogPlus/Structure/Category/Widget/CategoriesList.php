<?php
namespace CatalogPlus\Structure\Category\Widget;

use \Ideal\Core\Config;
use \Ideal\Core\Db;
use \Ideal\Field;

class CategoriesList extends \Ideal\Core\Widget
{
    protected $structurePath;
    protected $prefix;
    protected $lvl = 4;
    protected $model;

    public function setStructurePath($structurePath)
    {
        $this->structurePath = $structurePath;
    }


    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    public function setLvl($lvl){
        $this->lvl = $lvl;
    }

    /**
     * Получение списка категорий продукции
     * @return array
     */
    public function getData()
    {
        $db = Db::getInstance();
        $config = Config::getInstance();
        $url = new Field\Url\Model();
        $object = $this->model->getPath();
        $object = end($object);
        if(!isset($object['cid'])) return;

        // Считываем список категорий продукции
        $table = $config->db['prefix'] . 'catalogplus_structure_category';
        $_sql = "SELECT *
                 FROM {$table}
                 WHERE is_active=1 AND is_not_menu=0 AND lvl<{$this->lvl}
                 ORDER BY cid";
        $menuList = $db->queryArray($_sql);

        $lvl = 1;
        $menuUrl = array('0' => array('url' => $config->structures[0]['url']));
        $smallCidActive = rtrim($object['cid'], '0');

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
            if (isset($v['is_skip']) && $v['is_skip'] == 0) {
                if (isset($v['url_full']) && $v['url_full'] != '') {
                    $menu[$k]['link'] = 'href="' . $v['url_full'] . '"';
                } else {
                    $menu[$k]['link'] = 'href="' . $this->prefix . $url->getUrl($v) . '"';
                }
            }

            // Определяем активен ли данный пункт меню
            $menu[$k]['isActivePage'] = 0;
            $currentCid = rtrim($v['cid'], '0');
            if (isset($object['lvl']) && $object['lvl'] >= $lvl
                && substr($smallCidActive, 0, strlen($currentCid)) == $currentCid) {
                $menu[$k]['isActivePage'] = 1;
            }
        }
        $categoryList = $this->getSubCategories($menu);
        return $categoryList;
    }

    function getSubCategories(&$menu)
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
}