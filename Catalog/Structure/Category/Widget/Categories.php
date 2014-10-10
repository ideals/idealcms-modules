<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */
namespace Catalog\Structure\Category\Widget;

use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Field\Url;

class Categories extends \Ideal\Core\Widget
{
    /**
     * Получение списка категорий на первом уровне
     * @return array
     */
    public function getData()
    {
        $db = Db::getInstance();
        $config = Config::getInstance();
        $_table = $config->db['prefix'] . 'catalog_structure_category';
        $_sql = "SELECT * FROM {$_table}
                    WHERE lvl=1 AND is_active=1 AND is_not_menu=0 AND prev_structure='{$this->prevStructure}'
                    ORDER BY cid";
        $menuList = $db->select($_sql);

        $menu = array();
        // Построение правильных url
        $url = new Url\Model();
        foreach ($menuList as $v) {
            $v['link'] = $url->getUrlWithPrefix($v, $this->prefix);
            $menu[$v['cid']] = $v;
        }
        unset($menuList);

        $path = $this->model->getPath();
        foreach ($path as $v) {
            if (!isset($v['cid'])) {
                continue;
            }
            if (!isset($menu[$v['cid']])) {
                continue;
            }
            if (isset($v['prev_structure']) && ($v['prev_structure'] == $this->prevStructure)
                && ($v['ID'] == $menu[$v['cid']]['ID'])
            ) {
                $menu[$v['cid']]['isActivePage'] = 1;
                break;
            }
        }
        return $menu;
    }
}
