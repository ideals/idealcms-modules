<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Shop\Structure\Service\Load1c;

use Ideal;
use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Field\Url;
use Shop\Structure\Service\Load1c;
use CatalogPlus\Structure\Category;

class ToolsAbstract
{
    // Таблицы указываются без префекса

    /** @var string Таблица категорий */
    protected $tableCat = 'catalogplus_structure_category';

    /** @var string Таблица товаров */
    protected $tableGood = 'catalogplus_structure_good';

    /** @var string Таблица c информацией о товарном предложении */
    protected $tableOffer = 'catalogplus_structure_offer';

    /** @var string Таблица для связи категории и товара */
    protected $tableMediumGood = 'catalogplus_medium_categorylist';

    /** @var string prev_structure товаров */
    protected $prevGood = '';

    /** @var string структуры товаров */
    protected $structureGood = '';

    /** @var string структуры категорий */
    protected $structureCategory = 'CatalogPlus_Category';

    /** @var string prev_structure категорий товаров */
    protected $prevCat  = '';

    /** @var array Поля обозначающие связь между полями выгрузки 1С и полями в БД */
    protected $fields = array();

    /** @var array Категории товаров */
    protected $category = array();

    protected $treeCat = array();

    public function __construct()
    {
        $config = Config::getInstance();
        $prefix = $config->db['prefix'];
        $this->tableCat = $prefix . $this->tableCat;
        $this->tableGood = $prefix . $this->tableGood;
        $this->tableOffer = $prefix . $this->tableOffer;
        $this->tableMediumGood = $prefix . $this->tableMediumGood;
    }

    /**
     * @param $importFile
     * @param $offersFile
     * @param $priceId
     * оферсы
     * склады
     * характеристики
     */

    public function showProperties($importFile, $offersFile, $priceId)
    {
        $base = new Load1c\Model(DOCUMENT_ROOT . $importFile, DOCUMENT_ROOT . $offersFile, $priceId);

        // Отображение свойств товара, заданных в каталоге
        print '<pre>';
        print_r($base->getProperties());
        print '</pre>';
    }


    public function showCategories($importFile, $offersFile, $priceId)
    {
        $base = new Load1c\Model(DOCUMENT_ROOT . $importFile, DOCUMENT_ROOT . $offersFile, $priceId);

        // Отображение структуры категорий товара, с указанием сколько товаров в каждой категории
        print '<pre>';
        $base->checkGoodsInGroups();
        print '</pre>';
    }


    /**
     * @param $importFile
     * @param $offersFile
     * @param $priceId
     * @param bool $loadImg
     * @param bool $conect1c
     */
    public function loadBase($importFile, $offersFile, $priceId, $loadImg = false, $conect1c = false)
    {
        $db = Db::getInstance();

        $base = new Load1c\Model(DOCUMENT_ROOT . $importFile, DOCUMENT_ROOT . $offersFile, $priceId);

        $fields = $this->fields;

        // УСТАНОВКА КАТЕГОРИЙ ТОВАРА ИЗ БД

        // Сбрасываем счетчик товаров для групп
        $sql = "UPDATE {$this->tableCat} SET num = 0, count_sale = 0, is_not_menu = 0 WHERE id_1c != 'not-1c'";
        $db->query($sql);

        // Считываем категории из нашей БД
        $sql = <<<SQL
        SELECT ID, name, cid, lvl, id_1c, is_active, title, count_sale
          FROM {$this->tableCat}
          WHERE prev_structure='{$this->prevCat}' AND id_1c !='not-1c'
SQL;
        $groups = $db->select($sql);

        // Устанавливаем категории из БД
        $base->setOldGroups($groups);
        unset($groups);

        $txt = ''; // сообщение со статусом работы
        $text = ''; // текст письма информирующего о выгрузке

        // ОБРАБОТКА КАТЕГОРИЙ ТОВАРА

        // Получаем изменённые категории
        $changedGroups = $base->getLoadGroups();

        if (!$conect1c) {
            echo '<h2>Категории</h2>';
            echo 'Добавлено: ' . count($changedGroups['add']) . '<br />'; //Пока не требуется
            echo 'Обновлено: ' . count($changedGroups['update']) . '<br />';
            echo 'Удалено: ' . count($changedGroups['delete']) . '<br />';
        } else {
            $text .= "Категории\nДобавлено: " . count($changedGroups['add']) . "\nОбновлено: "
                . count($changedGroups['update']) . "\nУдалено: " . count($changedGroups['delete']) . "\n";
        }

        $txt = $this->updateCategories($db, $this->tableCat, $txt, $changedGroups);
        unset($changedGroups);

        // ОБРАБОТКА ТОВАРА
        // Считываем товар из нашей БД
        $sql = "SELECT ID, name, id_1c, is_active FROM {$this->tableGood} WHERE prev_structure='{$this->prevGood}'";
        $goods = $db->select($sql);

        $changedGoods = $base->getGoods($fields, $goods);

        if (!$conect1c) {
            echo '<h2>Товары</h2>';
            echo 'Товаров в базе: ' . count($goods) . '<br />';
            echo 'Добавлено: ' . count($changedGoods['add']) . '<br />';
            echo 'Обновлено: ' . count($changedGoods['update']) . '<br />';
            echo 'Кол-во в архиве: ' . count($changedGoods['delete']) . '<br />';
        } else {
            $text .= "\nТовары\nБыло: " . count($goods) . "\nДобавлено: " . count($changedGoods['add'])
                . "\nОбновлено: " . count($changedGoods['update']) . "\nКол-во в архиве: "
                . count($changedGoods['delete']) . "\n";
        }
        $db->query("UPDATE {$this->tableGood} SET sell = NULL");

        $txt = $this->updateGoods($db, $this->tableGood, $txt, $changedGoods, $loadImg, $conect1c);
        $txt = $this->updateOffers($db, $txt, $changedGoods);
        unset($changedGoods);

        // ПРИВЯЗКА ТОВАРА К КАТЕГОРИЯМ

        $this->updateGoodsToGroups($db, $base->getGoodsToGroups(), $base->status);

        // Запись скидочных товаров для категорий
        $txt .= 'Товаров со скидкой: '
            . $this->updateCountGoodsCat(
                $db,
                $this->tableCat,
                $base->getSaleCount(),
                $base->getGroupArr(),
                $base->getGoodsOnCat()
            ) . "\n";

        // Скрываем группы которые созданы в 1с, но сейчас не имеют товаров совсем
        $sql = "UPDATE {$this->tableCat} SET is_not_menu = 1 WHERE id_1c != 'not-1c' AND num = 0 AND count_sale = 0";
        $db->query($sql);

        /*if (count($this->newColor) > 0) {
            $txt .= "\nПоявились новые цвета: " . implode('; ', $this->newColor);
        }

        if (count($this->newSize) > 0) {
            $txt .= "\nПоявились новые размеры: " . implode('; ', $this->newSize);
        }

        if (count($this->nonBrand) > 0) {
            $txt .= "\nУ товаров с артикулом нет бренда: " . implode('; ', $this->nonBrand);
        }
        if (count($this->nonColor) > 0) {
            $txt .= "\nУ товаров с артикулом нет цвета: " . implode('; ', $this->nonColor);
        }*/
        if (!$conect1c) {
            echo str_replace("\n", '<br />', $txt);
        } else {
            $config = Config::getInstance();
            $text .= $txt . "\nВыгрузка еще не завершена. Может идти еще выгрузка картинок";
            $mail = new Sender();
            $mail->setSubj('Выгрузка на' . $config->db['domain']);
            $mail->setPlainBody($text);
            $mail->sent('top@neox.ru, morozov@neox.ru, help1@neox.ru, seo3@neox.ru');
            echo iconv("UTF-8", "windows-1251", $text);
        }
    }

    protected function updateCategories(Db $db, $table, $txt, $changedGroups)
    {
        foreach ($changedGroups['update'] as $v) {
            //if ($v['lvl'] == 1) $modelType->getIdType($v['name']);
            if ($v['Наименование'] != $v['name']) {
                $txt .= 'Переименована категория &laquo;' . $v['name'] . '&raquo; в &laquo;'
                    . $v['Наименование'] . "\n";
            }
            if (isset($v['old_cid_lvl'])) {
                $txt .= 'Категория &laquo;' . $v['name'] . '&raquo; перемещена. Было '
                    . $v['old_cid_lvl'] . ' стало cid=' . $v['cid'] . ', lvl=' . $v['lvl'] . "\n";
            }
            $update = array(
                'name' => $v['Наименование'],
                'cid' => $v['cid'],
                'lvl' => $v['lvl'],
                'is_active' => 1,
                'is_not_menu' => 0
            );
            $db->update($table)->set($update)->where('ID=:id', array('id' => $v['ID']))->exec();
        }

        $add = array(
            'date_create' => time(),
            'date_mod' => time(),
            'prev_structure' => $this->prevCat,
            'structure' => $this->structureCategory,
            'is_active' => 1
        );

        foreach ($changedGroups['add'] as $v) {
            $v['id_1c'] = $v['Ид'];
            $this->listCat[$v['id_1c']] = 0;
            unset($v['Ид']);
            $v['name'] = $v['Наименование'];
            $v['name'] = trim($v['name']);
            unset($v['Наименование']);

            $v['url'] = Url\Model::translitUrl($v['name']);

            $v = array_merge($v, $add);

            $db->insert($table, $v);
        }

        foreach ($changedGroups['delete'] as $v) {
            $this->listCat[$v['id_1c']] = 0;
            $par = array('is_active' => 1, 'is_not_menu' => 1);
            $db->update($table)->set($par)->where('ID=:id', array('id' => $v['ID']))->exec();
            $txt .= 'Удалена категория: ' . $v['name'] . "\n";

        }

        return $txt;
    }


    protected function updateGoods(Db $db, $table, $txt, &$changedGoods, $loadImg = false, $conect1c = false)
    {
        $modelCategory = new Category\Admin\Model('');
        $modelCategory->loadCategory();
        //$typeBrand = new Brand\Admin\Model('1-25'); // TODO нужно вынести prev_structure
        //$typeBrand->loadType();
        $config = Config::getInstance();
        /*$dataId = $config->getStructureByName('Ideal_DataList');
        $dataId = $dataId['ID'];
        $sql = "SELECT * FROM i_ideal_structure_datalist WHERE structure = 'Synonyms_Data'";
        $result = $db->select($sql);
        foreach ($result as $k => $v) {
            if ($v['parent_url'] == 'size') {
                $this->prevSize = $dataId . '-' . $v['ID'];
            } elseif ($v['parent_url'] == 'color') {
                $this->prevColor = $dataId . '-' . $v['ID'];
            }
        }
        $data = new Data\Admin\Model('');
        $data->loadType(false);*/
        foreach ($changedGoods['update'] as $v) {
            $v['is_active'] = 1;
            $properties = unserialize($v['properties']);
            /*$v['color_id'] = $data->getIdType($properties['Цвет'], $this->prevColor);
            if (!isset($properties['Бренд'])) {
                $this->nonBrand[$v['articul']] = $v['articul'];
                continue;
            }
            $v['brand_id'] = $typeBrand->getIdType($properties['Бренд']);*/
            if ($loadImg) {
                $this->loadImg($v);
            }
            if ($v['ID'] == 0) {
                echo 'Невозможно обновить, т.к. нулевой ID.<br />';
                print_r($v);
                exit;
            }
            if ($v['idGroup']) {
                $idCat = $modelCategory->getIdCategory($v['idGroup']);
                $v['category_id'] = $idCat['ID'];
            }
            unset($v['idGroup']);
            unset($properties);
            $db->update($table)->set($v)->where('ID=:id', array('id' => $v['ID']))->exec();
        }
        $add = array(
            'date_create' => time(),
            'date_mod' => time(),
            'prev_structure' => $this->prevGood,
            'structure' => 'CatalogPlus_Offer',
            'is_active' => 1
        );
        foreach ($changedGoods['add'] as $v) {
            $v['name'] = trim($v['name']);
            $properties = unserialize($v['properties']);
            /*$v['color_id'] = $data->getIdType($properties['Цвет'], $this->prevColor);
            if (!isset($properties['Бренд'])) {
                $this->nonBrand[$v['articul']] = $v['articul'];
                continue;
            }
            if (isset($properties['Цвет'])) {
                $url = $properties['Бренд'] . ' ' . $v['articul'] . ' ' . $properties['Цвет'];
            } else {
                $url = $properties['Бренд'] . ' ' . $v['articul'];
                $this->nonColor[$v['articul']] = $v['articul'];
            }
            $url = str_replace('!"#$%&\'()*+,./@:;<=>[\\]^`{|}~', '', $url);
            $v['brand_id'] = $typeBrand->getIdType($properties['Бренд']);
            $v['url'] = Url\Model::translitUrl($url);*/
            $v['url'] = $this->getUrl($v);
            $v = array_merge($v, $add);
            if ($loadImg) {
                $this->loadImg($v);
            }
            //$v['idBrand'] = $modelBrand->getIdType($v['nameGroup']);
            if ($v['idGroup']) {
                $idCat = $modelCategory->getIdCategory($v['idGroup']);
                $v['category_id'] = $idCat['ID'];
            }
            unset($v['idGroup']);
            unset($properties);
            $db->insert($table, $v);
        }
        foreach ($changedGoods['delete'] as $v) {
            $par = array('is_active' => 0);
            if (!isset($v['ID']) || ($v['ID'] == 0)) {
                // Если товара нет в БД, и у него нет цены — не добавляем его в базу
                continue;
            } else {
                $db->update($table)->set($par)->where('ID=:id', array('id' => $v['ID']))->exec();
            }
            //$txt .= 'Удален товар: ' . $v['name'] . "\n";
        }
        return $txt;
    }

    /**
     * @param Db $db
     * @param $txt
     * @param $changedGoods
     */
    protected function updateOffers(Db $db, $txt, &$changedGoods)
    {
        $fields = array(
            'ID' => array('sql' => 'int(8) unsigned not null auto_increment primary key', 'label' => ''),
            'prev_structure' => array('sql' => 'varchar(15)', 'label' => ''),
            'offer_id' => array('sql' => 'varchar(37)', 'label' => ''),
            'good_id' => array('sql' => 'varchar(37)', 'label' => ''),
            'price' => array('sql' => 'int(11)', 'label' => ''),
            'name' => array('sql' => 'varchar(255)', 'label' => ''),
            'count' => array('sql' => 'int(11)', 'label' => ''),
        );
        $_sql = "DROP TABLE IF EXISTS {$this->tableOffer}";
        $db->query($_sql);
        $db->create($this->tableOffer, $fields);
        $db->query("ALTER TABLE {$this->tableOffer} ADD INDEX ( good_id )");
        foreach ($changedGoods['offers'] as $k => $v) {
            unset($v['ЦенаЗаЕдиницу']);
            $row = array();
            $tmp = isset($changedGoods['add'][$k]) ? 'add' : 'update';
            $tmp = unserialize($changedGoods[$tmp][$k]['properties']);
            foreach ($v as $offers => $val) {
                $row['offer_id'] = $offers;
                $row['good_id'] = $k;
                $row['prev_structure'] = '';
                $row['price'] = (int)$val['ЦенаЗаЕдиницу'];
                $row['name'] = $val['Наименование'];
                $row['count'] = (int)$val['Количество'];
                $db->insert($this->tableOffer, $row);
            }
            unset($v['ЦенаЗаЕдиницу']);
            unset($row);
        }
    }

    /**
     * Получение url товара
     * @param array $good Bнформация о товаре
     * @return string Url товара
     */
    protected function getUrl($good)
    {
        $url = str_replace('!"#$%&\'()*+,./@:;<=>[\\]^`{|}~', '', $good['name']);
        return Url\Model::translitUrl($url);
    }

    /**
     * Создание ссылки на картинки товара
     * @param $v array данные о товаре
     */
    protected function loadImg(&$v)
    {
        /*
        // Удаление картинок если картинки перевыгружют
        $config = Config::getInstance();
        $allowResize = explode("\n", $config->allowResize);
        */
        if (isset($v['img']) && $v['img'] != null) {
            /*
            // Удаление картинок если картинки перевыгружют
            foreach ($allowResize as $k => $v) {
                if (file_exists(DOCUMENT_ROOT . '/images/1c/' . $v . '/' .$v['img'])) {
                    unlink(DOCUMENT_ROOT . '/images/1c/' . $v . '/' .$v['img']);
                }
            }*/
            $image = basename($v['img']);
            $dir = basename(str_replace('/' . $image, '', $v['img']));
            $image = $dir . '/' . $image;
            $v['img'] = $image;
            unset($image);
        }
        if (isset($v['imgs']) && $v['imgs'] != null) {
            $imgs = explode('|:|', $v['imgs']);
            foreach ($imgs as $k => $value) {
                /*
                // Удаление картинок если картинки перевыгружют
                foreach ($allowResize as $k => $v) {
                    if (file_exists(DOCUMENT_ROOT . '/images/1c/' . $v . '/' . $value)) {
                        unlink(DOCUMENT_ROOT . '/images/1c/' . $v . '/' . $value);
                    }
                }*/
                $image = basename($imgs[$k]);
                $dir = basename(str_replace('/' . $image, '', $imgs[$k]));
                $image = $dir . '/' . $image;
                $imgs[$k] = $image;
            }
            $v['imgs'] = implode('|:|', $imgs);
            unset($imgs);
        }
    }

    protected function updateGoodsToGroups(Db $db, $goodsToGroups, $status)
    {
        $fields = array(
            'category_id' => array('sql' => 'int(11)', 'label' => ''),
            'good_id' => array('sql' => 'int(11)', 'label' => '')
        );

        if ($status == 'full') {
            $_sql = "DROP TABLE IF EXISTS {$this->tableMediumGood}";
            $db->query($_sql);
            $db->create($this->tableMediumGood, $fields);
            $db->query("ALTER TABLE {$this->tableMediumGood} ADD INDEX ( category_id )");
            $db->query("ALTER TABLE {$this->tableMediumGood} ADD INDEX ( good_id )");
            foreach ($goodsToGroups as $groupId => $goodIds) {
                foreach ($goodIds as $goodId) {
                    if ($groupId == '') {
                        continue;
                    }
                    $_sql = "SELECT ID FROM {$this->tableCat} AS t1 WHERE t1.id_1c='{$groupId}' LIMIT 1";
                    $id = $db->select($_sql);
                    $id = $id[0]['ID'];
                    $_sql = "SELECT ID FROM {$this->tableGood} AS t1 WHERE t1.id_1c='{$goodId}' LIMIT 1";
                    $id2 = $db->select($_sql);
                    $id2 = $id2[0]['ID'];
                    $row = array(
                        'category_id' => $id,
                        'good_id' => $id2
                    );
                    if ($row['category_id'] == '') {
                        continue;
                    }
                    $db->insert($this->tableMediumGood, $row);
                }
            }
        } else {
            $goods = array();
            // Перестраиваем массив привязок с группа->товар на товар->группы
            foreach ($goodsToGroups as $groupId => $goodIds) {
                foreach ($goodIds as $goodId) {
                    $goods[$goodId] = $groupId;
                }
            }

            // Проходимся по массиву товаров: удаляем все привязки товара из БД, добавляем из XML
            foreach ($goods as $goodId => $groupsId) {
                $_sql = "DELETE FROM {$this->tableMediumGood} WHERE good_id = '{$goodId}'";
                $db->query($_sql);
                foreach ($groupsId as $groupId) {
                    $row = array(
                        'category_id' => $groupId,
                        'good_id' => $goodId
                    );
                    $db->insert($this->tableMediumGood, $row);
                }
            }
        }
    }

    /**
     * TODO создание категории если ее нет, с учётом аддонов
     * @param $id
     * @return mixed
     */
    protected function createCategory($id)
    {
        $parentId = $this->treeCat[$id]['parent_id'];
        $cid = new \Ideal\Field\Cid\Model(6, 3);
        $lvl = 1;
        if ($parentId !== false) {
        } else {
            //$newCid = $cid->setBlock($nextParentCid, $newLvl, $newCid, true);

            $db = Db::getInstance();

            $insert = array(
                //'id_1c',
                'prev_structure' => $this->prevCat,
                'cid' => $cid,
                'lvl' => $lvl,
                'structure' => 'Shop_Category',
                'template' => 'Ideal_Page',
                //'name' => $child,
                //'url' => Url\Model::translitUrl($child),
                'num' => 1,
                'date_create' => time(),
                'date_mod' => time()
            );
        }
        return $id;
    }

    /**
     * Записываем кол-во товаров со скидкой для каждой группы
     * @param Db $db
     * @param $table string таблица категорий
     * @param $sale array массив где ключ id_1c категории, а значение кол-во товара со скидкой
     * @param $groups
     * @param $countCat int кол-во товара на группу
     * @return int Количество товаров со скидкой
     */
    private function updateCountGoodsCat(Db $db, $table, $sale, $groups, $countCat)
    {
        $arr = array();
        $count = 0;
        ksort($groups);
        foreach ($groups as $key => $val) {
            preg_match_all('/([0-9]{0,3})/', $key, $tmp);
            $tmp = $tmp[0];
            $obiwan = & $arr[array_shift($tmp)];
            foreach ($tmp as $_v) {
                if (($_v === '000') || ($_v === '')) {
                    break;
                }
                $obiwan = &$obiwan['sub'][array_shift($tmp)];
            }
            $obiwan['id_1c'] = $val['id_1c'];
            $obiwan['count_sale'] = (int)$val['count_sale'];
            $obiwan['count_good'] = isset($countCat[$val['id_1c']]) ? $countCat[$val['id_1c']] : 0;
            if (isset($sale[$val['id_1c']]) && $sale[$val['id_1c']] != $val['count_sale']) {
                $obiwan['count_sale'] = $sale[$val['id_1c']];
            }
        }

        foreach ($arr as $k => $v) {
            $tmp = $this->recurUpdateSale($db, $table, $arr[$k]);
            $count += $tmp['s'];
        }
        return $count;
    }

    /**
     * Установка кол-во товара в категории
     * @param Db $db
     * @param $table
     * @param $elem
     * @return array
     */
    private function recurUpdateSale(Db $db, $table, &$elem)
    {
        $countS = 0;
        $countG = 0;
        if (isset($elem['sub'])) {
            foreach ($elem['sub'] as $k => $v) {
                $tmp = $this->recurUpdateSale($db, $table, $elem['sub'][$k]);
                $countS += $tmp['s'];
                $countG += $tmp['g'];
            }
        } else {
            $countS = $elem['count_sale'];
            $countG = $elem['count_good'];
        }
        $tmp = $elem['id_1c'];
        $_sql = "UPDATE {$table} SET count_sale={$countS}, num={$countG}";
        $_sql .= " WHERE id_1c='{$tmp}'";
        $db->query($_sql);
        return array('s' => $countS, 'g' => $countG);
    }
}
