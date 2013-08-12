<?php
namespace Shop\Structure\CategoryMulti\Site;

use Ideal\Core\Db;

class Model extends \Ideal\Structure\Part\Site\ModelAbstract
{
    public function getWhere($where)
    {
        $db = Db::getInstance();
        $categoryId = $this->object['ID'];
        $_sql = "SELECT good_id FROM i_shop_category_good WHERE category_id='{$categoryId}'";
        $goodIdsArr = $db->queryArray($_sql);
		if (count($goodIdsArr) == 0) {
			return array();
		}
        $goodIs = array();
        foreach ($goodIdsArr as $good) {
            $goodIs[] = "'" . $good['good_id'] . "'";
        }
        $goodIs = implode(',', $goodIs);

        $where .= "AND is_active=1 AND ID IN ({$goodIs})";

        $this->params['field_sort'] = 'name';

        return $where;
    }
}