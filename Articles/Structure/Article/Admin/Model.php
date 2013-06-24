<?php
namespace Articles\Structure\Article\Admin;

use Ideal\Core\Db;
use Ideal\Core\Request;

class Model extends \Ideal\Structure\Roster\Admin\ModelAbstract
{
    public function getToolbar()
    {
        $db = Db::getInstance();
        $_table = 'i_articles_structure_category';
        $structurePath = '11-1';
        $_sql = "SELECT * FROM {$_table} WHERE structure_path='{$structurePath}' AND is_active=1 ORDER BY cid";
        $this->categories = $db->queryArray($_sql);

        $request = new Request();
        $currentCategory = $request->category;

        $select = '<select name="category">';
        foreach ($this->categories as $category) {
            $selected = '';
            if ($category['ID'] == $currentCategory) {
                $selected = 'selected="selected"';
            }
            $select .= '<option ' . $selected . ' value="' . $category['ID'] . '">' . $category['name'] . '</option>';
        }
        $select .= '</select>';

        return $select;
    }

}
