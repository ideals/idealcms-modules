<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2013 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace MiniForum\Structure\Post\Widget;

use MiniForum\Structure\Post\Site;

/**
 * Виджет отображения комментариев под каждой страницей на сайте
 */
class Comments extends \Ideal\Core\Widget
{
    /** @var  \Ideal\Core\Site\Model Модель, содержащая данные отображаемой странице */
    protected $model;
    /** @var bool Флаг нужно отбражать на этой странице форум или не нужно */
    protected $isShow = true;
    /** @var string Идентификатор страницы состоящий из prev_structure и ID */
    protected $pageStructure = '';

    /**
     * Получение списка комментариев первого уровня для запрошенной страницы
     *
     * ВНИМАНИЕ, если в возвращаемом массиве флаг isShow == false, то форум отображать не нужно
     *
     * @return array Массив с флагом отображения форума и списком сообщений для отображения на странице
     */
    public function getData()
    {
        if (!$this->isShow) {
            // Если комментарии отображать не нужно, ничего не делаем
            return array('isShow' => false);
        }

        $forum = new \MiniForum\Structure\Post\Site\Model('');

        // Получаем список сообщений первого уровня для страницы $pageStructure
        $posts = $forum->getComments($this->pageStructure);

        $result = array(
            'isShow' => true,
            'pageStructure' => $this->pageStructure,
            'posts' => $posts,
        );

        return $result;
    }

    /**
     * Установка списка исключений и проверка, не соответствует ли какой-либо из
     * параметров $model->pageData элементу массива $exceptions
     *
     * Если хотя бы один из параметров массива $exceptions есть в $model->pageData,
     * то на этой странице комментарии отображаться не будут
     *
     * @param array $exceptions Массив с параметрами исключаемых страниц
     */
    public function setParams($exceptions)
    {
        $page = $this->model->getPageData();

        // Если были найдены сходные элементы в обоих массивах, отключаем вывод виджета форума
        if (count(array_intersect_assoc($page, $exceptions)) > 0) {
            $this->isShow = false;
            return;
        }
    }

    public function setPath($pageStructure)
    {
        $this->pageStructure = $pageStructure;
    }
}
