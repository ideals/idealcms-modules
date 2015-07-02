<?php
namespace Shop\Structure\Service\Load1c_v2;

use Ideal\Core\Config;
use Ideal\Core\Db;

/**
 * Created by PhpStorm.
 * User: Help4
 * Date: 02.07.2015
 * Time: 16:31
 */

class DbCategory
{
    /** @var string префикс таблиц */
    protected $prefix;

    /** @var string Структуры категорий */
    protected $structureCat = 'catalogplus_structure_category';

    /** @var string Таблица категорий */
    protected $category = 'CatalogPlus_Category';

    public function __construct()
    {
        $config = Config::getInstance();
        $prefix = $config->db['prefix'];
        $this->structureCat = $prefix . $this->structureCat;
        $this->category = $prefix . $this->category;
    }

    public function parse()
    {
        $db = Db::getInstance();

        // Сбрасываем счетчик товаров для групп
        $values = array(
            'num' => 0,
            'count_sale' => 0,
            'is_not_menu' => 0,
        );
        $where = array(
            'key' => 'not-1c',
        );
        $db->update($this->structureCat)
            ->set($values)
            ->where('id_1c <> :key', $where)
            ->exec();

        // Считываем категории из нашей БД
        $where = array(
            'prev_structure' => '1-23',
            'id_1c' => 'not-1c',
        );
        $sql = "SELECT ID, name, cid, lvl, id_1c, is_active, title, count_sale
          FROM `ciaokids`.`{$this->structureCat}`
          WHERE prev_structure =:prev_structure AND id_1c <>:id_1c";

        $result = $db->select($sql, $where);

        return $this->setOldGroups($result);
    }

    public function save($array)
    {
        $this->update($array['update']);
        $this->delete($array['delete']);
        $this->add($array['add']);
    }

    protected function update()
    {

    }

    protected function delete()
    {
        /** проставляем is_active = 0. Удаления как такового нет
         * 2 варианта: категория удалена, категория перемещена
         *
         * Удаление
         *
         * Перемещение
         * 1) Определить предка
         * 2) Определить новый cid предка
         * 3) Определить новый cid элемента на основании 2
         * 4) Подготовить для занесения с новыми cid и поставить is_active = 0
         */
    }

    protected function add()
    {

    }

    protected function setOldGroups($groups)
    {
        $oldGroups = array();
        // В качестве ключей для категорий из БД ставим ключ 1С
        foreach ($groups as $v) {
            $v['is_exist'] = false;
            if ($v['title']) {
                $v['is_exist'] = true;
            }

            if ($v['id_1c'] == '') {
                $v['id_1c'] = $v['ID'];
            }
            $oldGroups[$v['id_1c']] = $v;
        }
        return $oldGroups;
    }
}
