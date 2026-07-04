<?php

namespace Shop\Structure\Service\Load1CV208\Db\Tag;

use Shop\Structure\Service\Load1CV208\Db\AbstractDb;
use Ideal\Core\Db;

class DbTag extends AbstractDb
{
    /** @var string Структуры тегов */
    protected $structureTag = 'ideal_structure_tag';

    /** @var string Структуры медиумов тегов */
    protected $structureMedium = 'ideal_medium_taglist';

    /** @var array массив товаров с тегами */
    protected $tags;

    /** @var string значение поля prev_structure для тегов */
    protected $prevTag = '1-18';

    /**
     *  Установка полей класса
     */
    public function __construct()
    {
        parent::__construct();
        $this->table = $this->prefix . $this->structureTag;
    }

    /**
     * Парсинг тегов из БД
     *
     * @return array ключ - путь до страницы тега, значение - все поля структуры тегов
     */
    public function parse(): array
    {
        $db = Db::getInstance();

        // Считываем теги из нашей БД
        $sql = sprintf('SELECT ID, cid, lvl, name, url, is_active FROM %s%s ORDER BY cid ASC', $this->table, $this->tablePostfix);
        $tmp = $db->select($sql);

        // Меняем ключи массива результатов выборки для соответствия даным из XML
        $result = [];
        foreach ($tmp as $element) {
            $key = $element['lvl'] == 1 ? $element['url'] : self::getKeyPath($tmp, $element) . '/' . $element['url'];

            $result[$key] = $element;
        }

        return $result;
    }

    /**
     * Сохранение изменений и добавление новых тегов в БД
     *
     * @param array $tags массив тегов для сохранения
     */
    public function save($tags): void
    {
        foreach ($tags as $k => $tag) {
            if (!isset($tag['prev_structure'])) {
                $tag[$k]['prev_structure'] = $this->prevTag;
            }

            $tag[$k]['structure'] = 'Ideal_Tag';
        }

        parent::save($tags);
    }

    /**
     * Получение списка тегов для распределения товаров из выгрузки
     *
     * @return array массив категорий ключ - url, значение - ID категории в базе
     */
    public function getTags(): array
    {
        $db = Db::getInstance();

        $tags = [];
        $res = $db->select('SELECT ID, url, cid, lvl FROM ' . $this->table . $this->tablePostfix . ' ORDER BY cid');
        foreach ($res as $tag) {
            $tags[$tag['url']] = $tag;
        }

        return $tags;
    }

    /**
     * Подготовка параметров тега для добавления в БД
     *
     * @param array $element Добавляемый тег
     * @return array Модифицированный тег
     */
    protected function getForAdd(array $element): array
    {
        $element['prev_structure'] = $this->prevTag;
        $element['template'] = 'index.twig';
        $element['structure'] = 'Ideal_Tag';
        $element['is_active'] = 1;
        $element['is_not_menu'] = 1;

        return parent::getForAdd($element);
    }

    /**
     * Генерирует ключ для массива выборки из базы
     *
     * @param array $tmp - массив выборки из базы
     * @param array<string, mixed> $element - рассматриваемый элемент выборки
     * @return string - сгенерированный ключ для рассматриваемого элемента
     */
    private function getKeyPath($tmp, array $element)
    {
        $key = '';
        $searchLvl = $element['lvl'] - 1;
        $searchCid = str_split($element['cid'], 3);
        $searchCid = implode('', array_slice($searchCid, 0, $searchLvl));
        $searchCid = str_pad($searchCid, 18, '0');
        foreach ($tmp as $item) {
            if ($item['cid'] == $searchCid) {
                $key = $item['url'];
                if (intval($item['lvl']) !== 1) {
                    $key = self::getKeyPath($tmp, $item) . '/' . $key;
                }

                break;
            }
        }

        return $key;
    }
}
