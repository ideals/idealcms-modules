<?php

namespace CatalogPlus\Structure\Category\Site;

use CatalogPlus\Structure\Good\Site\Model;
use Ideal\Core\Db;
use Ideal\Core\Config;
use Ideal\Core\Util;
use Ideal\Field;
use Ideal\Structure\User;

class ModelAbstract extends \Ideal\Structure\Part\Site\ModelAbstract
{
    protected $categories;

    protected $current;

    protected $tagParam;

    protected $tagParamName;

    protected Model $goods;

    /**
     * {@inheritdoc}
     */
    public function __construct($prevStructure)
    {
        parent::__construct($prevStructure);
        $this->goods = new Model('');
    }

    /**
     * Получить общее количество элементов в списке
     * @return array Полученный список элементов
     */
    public function getListCount()
    {
        $db = Db::getInstance();
        $config = Config::getInstance();
        if ($this->pageData['structure'] == 'CatalogPlus_Good') {
            $table = $config->db['prefix'] . 'catalogplus_structure_good';
            $prevStructure = explode('-', $this->pageData['prev_structure']);
            $prevStructure = end($prevStructure);
            $prevStructure = $prevStructure . '-' . $this->pageData['ID'];
            $_sql = sprintf("SELECT COUNT(ID) FROM %s WHERE is_active = 1 AND prev_structure = '%s'", $table, $prevStructure);
        } else {
            $tableLink = $config->db['prefix'] . 'catalogplus_medium_categorylist';
            $tableGood = $config->db['prefix'] . 'catalogplus_structure_good';
            $cid = rtrim($this->pageData['cid'], '0');
            $_sql = sprintf("SELECT ID FROM %s WHERE is_active = 1 AND cid LIKE '%s%%'", $this->_table, $cid);
            $_sql = sprintf('SELECT good_id FROM %s WHERE category_id IN (%s)', $tableLink, $_sql);
            $_sql = sprintf('SELECT COUNT(ID) FROM %s WHERE is_active=1 AND ID IN (%s)', $tableGood, $_sql);
        }

        $list = $db->select($_sql);

        return $list[0]['COUNT(ID)'];
    }


    public function setTagParamName($path)
    {
        $config = Config::getInstance();
        $structure = $config->getStructureByName('Ideal_Part');
        //$dataList = new \Ideal\Structure\Part\Admin\ModelAbstract('0-' . $structure['ID']);
        $end = end($path);
        //$spravochnik = $dataList->getByParentUrl($end['url']);
        $this->tagParamName = $end['url'];
        $this->prevStructure = $structure['ID'] . '-' . $end['ID'];
        //$this->path = array($structure, $spravochnik);
        return $this->tagParamName;
    }


    public function readCategories()
    {
        if ($this->tagParam === null) {
            $this->tagParam = $this->setTagParamName($this->path);
        }

        if ($this->categories === null) {
            $db = Db::getInstance();
            $_sql = sprintf("SELECT * FROM %s WHERE prev_structure='%s' AND is_active=1", $this->_table, $this->prevStructure);
            $_sql .= ' ORDER BY cid';
            $this->categories = $db->select($_sql);
        }

        return $this->categories;
    }


    public function getCategories(string $urlAll)
    {
        $config = Config::getInstance();
        $list = $this->readCategories();
        $first = [
            'name' => 'Все',
            'link' => 'href="' . $urlAll . $config->urlSuffix . '"',
            'class' => '',
        ];

        if ($this->pageData == null) {
            // Не выбрана ни одна категория
            $first['class'] = 'active';
            $tag = '';
        } else {
            $tag = $this->pageData['url'];
        }

        $params = $this->params;
        $cid = new \Ideal\Field\Cid\Model($params['levels'], $params['digits']);
        $tree = $cid->buildTree($list, $this->path);
        $list = $cid->plainTree($tree);

        foreach ($list as $k => $v) {
            $list[$k]['link'] = 'href="' . $v['link'] . '"'; // $this->getUrl($urlAll, $v)
            $list[$k]['class'] = ($v['url'] == $tag) ? 'active' : '';
        }

        if ($urlAll !== '' && $urlAll !== '0' && strpos($_SERVER['REQUEST_URI'], $urlAll) === 0) {
            // Первый элемент добавляем только когда категории запрашиваются со своего URL
            array_unshift($list, $first);
        }

        return $list;
    }


    /**
     * @param array<string, mixed> $element
     */
    public function getUrl(string $prefix, array $element)
    {
        if (isset($this->params['is_query_param']) && $this->params['is_query_param']) {
            $config = Config::getInstance();
            $url = $prefix . $config->urlSuffix . '?tag=' . $element['url'];
        } else {
            $urlModel = new Field\Url\Model();
            $url = $urlModel->getUrlWithPrefix($element, $prefix . '/' . $this->tagParam);
        }

        return $url;
    }


    /**
     * {@inheritdoc}
     */
    public function getStructureElements()
    {
        $this->params['elements_site'] = 9999;
        $parentUrl = $this->getParentUrl();
        return $this->getCategories($parentUrl);
    }

    /**
     * Получить список всех категорий
     *
     * @return array
     */
    public function getListCategory()
    {
        $db = Db::getInstance();
        $config = Config::getInstance();
        if ($this->pageData === false) {
            return [];
        }

        if ($this->pageData['structure'] == 'CatalogPlus_Good') {
            $prevStructure = explode('-', $this->pageData['prev_structure']);
            $prevStructure = end($prevStructure);
            $prevStructure = $this->pageData['ID'] . '-' . $prevStructure;
        } else {
            $prevStructure = $this->prevStructure;
        }

        // Узнаем url главной страницы категорий
        $catUrl = '';
        foreach ($this->path as $v) {
            if (isset($v['is_skip']) && ($v['is_skip'] === '1')) {
                continue;
            }

            if (!isset($v['url']) || (strlen($v['url']) < 1)) {
                continue;
            }

            $catUrl .= '/' . $v['url'];
            if ($v['structure'] === 'CatalogPlus_Category') {
                break;
            }
        }

        // Для авторизированных в админку пользователей отображать скрытые категории
        $user = new User\Model();
        $checkActive = ($user->checkLogin()) ? '' : ' AND is_active=1';

        // Список всех доступных категорий
        $sql = sprintf("SELECT * FROM %s WHERE prev_structure = '%s' %s ORDER BY cid", $this->_table, $prevStructure, $checkActive);
        $list = $db->select($sql);

        // Создание массива категорий
        // Есть два уровня на первом уровне ключ сид категории внутри(второй уровень) находится два эелемента
        // первый сама категория, а второй вложнные категории где все повторяется( есть два уровня...)
        $menu = [];
        $tmpUrl = [];
        foreach ($list as $v) {
            $aCurrent = &$menu;

            // Постороение правильных url для вложенных категорий
            if ($v['lvl'] == '1') {
                $tmpUrl = [];
                $tmpUrl[1] = $v['is_skip'] !== '1' ? $catUrl . '/' . $v['url'] : $catUrl;
            } else {
                if (!isset($tmpUrl[(int) $v['lvl'] - 1])) {
                    Util::addError('Нету родителя на 1 уровень выше! Категория:' . $v['name']);
                    exit(1);
                }

                if ($v['is_skip'] !== '1') {
                    $tmpUrl[(int) $v['lvl']] = $tmpUrl[(int) $v['lvl'] - 1] . '/' . $v['url'];
                } else {
                    $tmpUrl[(int) $v['lvl']] = $tmpUrl[(int) $v['lvl'] - 1];
                }
            }

            // Создание иерархии категорий
            $cidKey = str_split($v['cid'], $this->params['digits']);
            foreach ($cidKey as $key => $cid) {
                // Ищем положение текущего элемента(категории)
                if ($cid === '000') {
                    break;
                }

                if (!isset($aCurrent[$cid])) {
                    $aCurrent[$cid] = ['cat' => [], 'subcat' => []];
                }

                if (!isset($cidKey[$key + 1]) || $cidKey[$key + 1] == '000') {
                    $aCurrent = &$aCurrent[$cid];
                } else {
                    $aCurrent = &$aCurrent[$cid]['subcat'];
                }
            }

            $aCurrent['cat'] = $v;
            $aCurrent['cat']['link'] = 'href="' . $tmpUrl[$v['lvl']] . $config->urlSuffix . '"';
        }

        return $menu;

    }

    /**
     * Установка свойств объекта по данным из объекта $model
     *
     * Вызывается при копировании данных из одной модели в другую
     * @param array $model Массив переменных объекта
     * @return $this Либо ссылка на самого себя, либо новый объект модели
     */
    public function setVars($model)
    {
        //$this->setGoods($model);
        $model = parent::setVars($model);
        return $model;
    }

    /**
     * Определение главная ли эта страница товара или внутренняя
     * @return bool Возвращает true если это не главная страница товара
     */
    public function isNotIndex(): bool
    {
        $path = $this->getPath();
        $end = end($path);
        return $end['structure'] == $this->pageData['structure'];
    }
}
