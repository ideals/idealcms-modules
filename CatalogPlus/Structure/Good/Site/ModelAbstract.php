<?php
namespace CatalogPlus\Structure\Good\Site;

use Ideal\Core\Db;
use Ideal\Core\Config;
use Ideal\Core\Util;
use Ideal\Field;
use CatalogPlus;

class ModelAbstract extends \Ideal\Core\Site\Model
{
    /**
     * @var $categoryModel \CatalogPlus\Structure\Category\Site\Model
     */
    protected $categoryModel;
    protected $currentCategory;
    /** @var bool Отображать в категории товары из подкатегорий */
    protected $showNestedElements = true;

    /** @var mixed null - если фильтр не установлен, Объект фильтра если фильтр был применён */
    protected $filter = null;

    public function setFilter($filter)
    {
        $this->filter = $filter;
        $this->filter->setCategoryModel($this->categoryModel);
        $this->filter->setShowNestedElements($this->showNestedElements);
    }

    public function detectPageByUrl($path, $url)
    {
        // Определяем, нет ли в URL категории
        $this->categoryModel = new CatalogPlus\Structure\Category\Site\Model($this->prevStructure);
        $model = $this->categoryModel->detectPageByUrl($path, $url);
        if (!$model->is404) {
            // Прошло успешно определение страницы категории, значит товар определять не надо
            return $model;
        }

        if (count($url) > 1) {
            // У товара не может быть URL с несколькими уровнями вложенности
            $this->is404 = true;
            $this->path = $path;
            return $this;
        }

        // Ищем товар по URL в базе
        $db = Db::getInstance();

        $url = $db->escape_string(array_shift($url));
        $_sql = "SELECT * FROM {$this->_table} WHERE BINARY url='{$url}' LIMIT 1";
        $list = $db->select($_sql);

        // Товар не нашли, возвращаем 404
        if (!isset($list[0]['ID'])) {
            $this->path = $path;
            $this->is404 = true;
            return $this;
        }

        // Товар найден, проводим необходимую инициализацию свойств

        $this->path = array_merge($path, $list);
        $this->pageData = end($list);

        return $this;
    }

    public function getCategories()
    {
        $parentUrl = $this->getParentUrl();
        return $this->categoryModel->getCategories($parentUrl);
    }

    public function getStructureElements()
    {
        $this->categoryModel = new CatalogPlus\Structure\Category\Site\Model($this->prevStructure);
        $this->categoryModel->setPath($this->path);
        $this->params['elements_site'] = 9999;
        $articles = $this->getList(1);
        $categories = $this->getCategories();
        return array_merge($categories, $articles);
    }

    public function getData()
    {
        $data = $this->pageData['data'];
        $configAddon = new CatalogPlus\Addon\Data\Model('');
        if ($data['ID']) {
            unset($data['ID']);
        }
        if ($data['prev_structure']) {
            unset($data['prev_structure']);
        }
        foreach ($data as $k => $v) {
            unset($data[$k]);
            if ((is_string($v) && strlen($v) < 1) || (is_null($v))) {
                continue;
            }
            $data[$k]['name'] = $configAddon->fields[$k]['label'];
            $data[$k]['value'] = $v;
        }
        return $data;
    }

    /**
     * @param int $page Номер отображаемой страницы
     * @return array Полученный список элементов
     */
    public function getList($page = null)
    {
        $list = parent::getList($page);
        // Path для выстраивания правильного пути к товарам
        $pathGood = array();
        $config = Config::getInstance();

        // Строим path для товаров
        if (isset($this->categoryModel)) {
            $cidCat = $this->categoryModel->getPath();
            foreach ($cidCat as $k => $v) {
                if (!isset($v['structure'])) {
                    continue;
                }
                if ($v['structure'] == 'CatalogPlus_Category') {
                    // Добавляем элемент где хранятся товары в админке
                    $db = Db::getInstance();
                    $table = $config->db['prefix'] . 'ideal_structure_part';
                    $shortCid = end($pathGood);
                    $shortCid = isset($shortCid['cid']) ? rtrim($shortCid['cid'], '0') : '';
                    $sql = "SELECT * FROM {$table} WHERE Structure='CatalogPlus_Good' AND cid LIKE '{$shortCid}%'";
                    $goodElem = $db->select($sql);
                    $pathGood[] = $goodElem[0];
                    break;
                }
                $pathGood[] = $v;
            }
        }

        // Построение правильных URL
        $url = new Field\Url\Model();
        $url->setParentUrl($pathGood);
        if (is_array($list) and count($list) != 0) {
            foreach ($list as $k => $v) {
                $list[$k]['link'] = $url->getUrl($v);
            }
        }

        return $list;
    }

    public function getShortCidCat()
    {
        $cid = false;
        if (isset($this->categoryModel)) {
            $category = $this->categoryModel->getPageData();
            if (isset($category['ID'])) {
                $params = $this->categoryModel->params;
                $cidModel = new Field\Cid\Model($params['levels'], $params['digits']);
                $cid = $cidModel->getCidByLevel($category['cid'], $category['lvl'], false);
            }
        }
        return $cid;
    }

    public function detectPath()
    {
        if (isset($this->categoryModel)) {
            // Установлена категория, значит определять товар не нужно, определяем предков структуры товара
            $path = parent::detectPath();
            // todo определить вложенные категории
            $catPath = $this->categoryModel->getLocalPath();
            $this->path = array_merge($path, $catPath);
            return $this->path;
        }

        // Определение пути из товара
        $config = Config::getInstance();

        $good = $this->pageData;
        list($parentStructure, $parentId) = explode('-', $good['prev_structure']);
        if ($parentStructure == 0) {
            // Частный случай - определение пути не из конкретного товара, а из уровня выше
            return parent::detectPath();
        }
        $structure = $config->getStructureById($parentStructure);

        // Находим предка — структуру статей
        $parentClassName = Util::getClassName($structure['structure'], 'Structure') . '\\Site\\Model';
        /** @var $parentModel \Ideal\Core\Site\Model */
        $parentModel = new $parentClassName('CatalogPlus_Good');
        $parentModel->setPageDataById($parentId);

        $path = $parentModel->detectPath();

        $this->path = $path;
        $this->path[] = $good;

        return $this->path;
    }

    protected function getLocalPath()
    {
        return array();
    }

    public function getHeader()
    {
        $header = '';
        if (isset($this->pageData['content'])) {
            // Если есть шаблон с контентом, пытаемся из него извлечь заголовок H1
            list($header, $text) = $this->extractHeader($this->pageData['content']);
            $this->pageData['content'] = $text;
        }

        if ($header == '') {
            // Если заголовка H1 в тексте нет, берём его из названия name
            $header = $this->pageData['name'];
        }
        return $header;
    }

    public function setCategoryModel($model)
    {
        $this->categoryModel = $model;
    }

    /**
     * Установка свойств объекта по данным из массива $model
     *
     * Вызывается при копировании данных из одной модели в другую
     * @param Model $model Массив переменных объекта
     * @param bool $bypass Признак того что этот метод нужно пропустить и перейти к родительскому
     * @return $this Либо ссылка на самого себя, либо новый объект модели
     */
    public function setVars($model, $bypass = false)
    {
        if ($bypass) {
            return parent::setVars($model);
        }

        $category = new CatalogPlus\Structure\Category\Site\Model('');
        $path = $model->getPath();
        $end = array_pop($path);
        $end['structure'] = 'CatalogPlus_Category';
        $path[] = $end;
        $model->setPath($path);
        $category->setVars($model);
        //$category->setGoods($this);
        return $category;
    }

    public function getBreadCrumbs()
    {
        if (!empty($this->pageData) && isset($this->pageData['ID'])) {
            $db = Db::getInstance();
            $config = Config::getInstance();
            $pathCat = array();
            $this->categoryModel = new CatalogPlus\Structure\Category\Site\Model($this->prevStructure);
            $tableMid = $config->db['prefix'] . 'catalogplus_medium_categorylist';
            $tableCat = $config->db['prefix'] . 'catalogplus_structure_category';
            $tablePart = $config->db['prefix'] . 'ideal_structure_part';
            // Ищем категорию к которой привязан товар
            $sql = "SELECT cat.cid FROM {$tableMid} as mid
                  INNER JOIN {$tableCat} as cat ON (cat.ID = mid.category_id)
                  WHERE mid.good_id = {$this->pageData['ID']} AND cat.is_active = 1 LIMIT 1";
            $cat = $db->select($sql);
            // Выстраиваем путь к категории
            if (count($cat) > 0) {
                $cat = $cat[0]['cid'];
                $cids = array();
                for ($i = 1; $i < $this->categoryModel->params['levels']; $i++) {
                    $tmp = substr($cat, 0, $i * $this->categoryModel->params['digits']);
                    $tmp = str_pad(
                        $tmp,
                        $this->categoryModel->params['digits'] * $this->categoryModel->params['levels'],
                        '0'
                    );
                    $cids[$tmp] = $tmp;
                }
                $tmp = implode('\', \'', $cids);
                $sql = "SELECT * FROM {$tableCat} WHERE cid IN ('{$tmp}') ORDER BY cid";
                $pathCat = $db->select($sql);
            }

            // Состовляем путь до страницы где хранится модули CatalogPlus
            $tmp = array();
            foreach ($this->path as $v) {
                if (!isset($v['structure'])) {
                    continue;
                }
                if ($v['structure'] == 'CatalogPlus_Good') {
                    break;
                }
                $tmp[] = $v;
            }
            // Ищем страницу где хранятся категории на сайте
            $sql = "SELECT * FROM {$tablePart} WHERE structure='CatalogPlus_Category'";
            $cat = $db->select($sql);
            if (count($cat) > 0) {
                array_unshift($pathCat, $cat[0]);
            }
            $pathCat = array_merge($tmp, $pathCat);
            $pathCat[] = end($this->path);
            $this->path = $pathCat;
        }
        return parent::getBreadCrumbs();
    }

    /**
     * Получение информации о товарах
     *
     * @param array $ids список id товаров и предложение
     * @return array информация о товарах и их предложениях
     * @throws \Exception
     */
    public function getGoodsInfo($ids)
    {
        $config = Config::getInstance();
        $db = Db::getInstance();
        $offers = false; // По умолчанию оферы не подключены
        if ($config->getStructureByName('CatalogPlus_Offer')) {
            // А если подключены то загружаем о них данные
            $offers = $config->getStructureByName('CatalogPlus_Offer');
        }
        $goodId = array();
        $offerId = array();
        foreach ($ids as $k => $v) {
            if (stripos($v, '_') !== false) {
                list($goodId[], $offerId[]) = explode('_', $v);
            } else {
                $goodId[] = intval($v);
            }
        }
        $goodId = array_filter($goodId);
        $offerId = array_filter($offerId);
        $table = $this->_table;
        if ($offers && !$offerId) {
            list($prevStructureId) = explode('-', $this->prevStructure);
            $prevStructure = $config->getStructureById($prevStructureId);
            $table = $config->getTableByName($prevStructure['structure']);
        }
        $sql = "SELECT * FROM {$table} WHERE ID IN (" . implode(',', $goodId) . ")";
        if ($offers && $offerId) {
            $offerTable = $config->getTableByName('CatalogPlus_Offer');
            $field = '';
            // Что бы поля предложений не переписывали поля товара, добовляем преставку offer_
            foreach (array_keys($offers['fields']) as $v) {
                if ($field == '') {
                    $field .= 'o.' . $v . ' AS offer_' . $v;
                    continue;
                }
                $field .= ', o.' . $v . ' AS offer_' . $v;
            }
            // Узнаем ID структуры товаров что бы можно было сделать join для предложений
            $idStructure = $config->getStructureByName('CatalogPlus_Good');
            $idStructure = $idStructure['ID'];
            // Запрос с учетом существования id товара(предложения)
            $sql = "SELECT g.*, {$field} FROM {$this->_table} AS g
                    INNER JOIN {$offerTable} AS o ON (o.prev_structure = CONCAT('{$idStructure}-', g.ID))
                    WHERE g.ID IN (" . implode(',', $goodId) . ") AND o.ID IN (" . implode(',', $offerId) . ")";
        }
        $info = $db->select($sql);
        // Делаем массив с ключом идТовара_идПредложения и дополняем ссылкой на товар
        foreach ($info as $k => $v) {
            $link = '';
            $this->getFullUrl($v, $link);
            $v['link'] = 'href="' . $link . '"';
            unset($info[$k]);
            if ($offers && isset($v['offer_ID'])) {
                $info[$v['ID'] . '_' . $v['offer_ID']] = $v;
            } else {
                $info[$v['ID']] = $v;
            }
        }
        return $info;
    }

    /**
     * Рекурсивная функция, генерирующая ссылку на товар
     *
     * @param array $v Массив с данными о текущем элементе структуры
     * @param string $link Генерируемая ссылка на товар
     * @param array $structure Массив с данными о текущей структуре
     */
    public function getFullUrl($v, &$link, $structure = array())
    {
        if (!isset($v['is_skip']) || !$v['is_skip']) {
            $link = $v['url'] . '/' . $link;
        }
        $config = Config::getInstance();
        $db = Db::getInstance();

        if (!isset($v['cid'])) {
            if (isset($v['prev_structure']) && !empty($v['prev_structure'])) {
                list($idStructure, $idElement) = explode('-', $v['prev_structure']);
                $structure = $config->getStructureById($idStructure);
                $structureTable = $config->getTableByName($structure['structure']);
                $sql = "SELECT * FROM {$structureTable} WHERE id = {$idElement} LIMIT 1";
                $info = $db->select($sql);
                $v = $info[0];
                $this->getFullUrl($v, $link, $structure);
            }
        } else {
            if (empty($structure)) {
                $structure = $config->getStructureByName($this->getStructureName());
            }
            $nextUpLevel = $v['lvl'] - 1;
            if (!$nextUpLevel) {
                if ($v['prev_structure'] == '0-1') {
                    $link = '/' . $link;
                    return;
                } else {
                    list($idStructure, $idElement) = explode('-', $v['prev_structure']);
                    $structure = $config->getStructureById($idStructure);
                    $structureTable = $config->getTableByName($structure['structure']);
                }
                $sql = "SELECT * FROM {$structureTable} WHERE id = {$idElement} LIMIT 1";
                $info = $db->select($sql);
                $v = $info[0];
                $this->getFullUrl($v, $link, $structure);
            } else {
                $cid = new Field\Cid\Model($structure['params']['levels'], $structure['params']['digits']);
                $cid = $cid->getCidByLevel($v['cid'], $nextUpLevel);
                $structureTable = $config->getTableByName($structure['structure']);
                $sql = "SELECT * FROM {$structureTable} WHERE cid = '{$cid}%' LIMIT 1";
                $info = $db->select($sql);
                if (!empty($info)) {
                    $v = $info[0];
                    $this->getFullUrl($v, $link, $structure);
                }
            }
        }
        $link = rtrim($link, '/') . $config->urlSuffix;
    }
}
