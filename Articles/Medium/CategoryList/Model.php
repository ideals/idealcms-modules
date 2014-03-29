<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Articles\Medium\CategoryList;

use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Medium;

/**
 * Медиум для получения списка категорий, присвоенных статье
 */
class Model extends Medium\AbstractModel
{
    /**
     * {@inheritdoc}
     */
    public function getList()
    {
        $config = Config::getInstance();
        $db = Db::getInstance();
        $_sql = 'SELECT ID, name FROM ' . $config->db['prefix'] . 'articles_structure_category';
        $arr = $db->queryArray($_sql);

        $list = array();
        foreach ($arr as $item) {
            $list[$item['ID']] = $item['name'];
        }

        return $list;
    }
}
