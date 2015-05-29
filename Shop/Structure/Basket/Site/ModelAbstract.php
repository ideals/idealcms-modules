<?php
namespace Shop\Structure\Basket\Site;

use Ideal\Core\Db;
use Ideal\Core\Util;
use Ideal\Core\Config;
use Ideal\Core\Request;
use Ideal\Structure\User;

class ModelAbstract extends \Ideal\Structure\News\Site\ModelAbstract
{
    /**
     * TODO определять таблицу автоматически
     * Таблица где хранятся товары
     * @var string
     */
    protected $table = 'i_catalogplus_structure_good';

    protected $tableGood;
    protected $tableOffer;

    protected $linkArr = array();

    public function __construct($prevStructure)
    {
        parent::__construct($prevStructure);
        // Указываем таблицы где хранятся данные
        $config = Config::getInstance();
        $prefix = $config->db['prefix'];
        $this->tableGood = $prefix . 'catalogplus_structure_good';
        $this->tableOffer = $prefix . 'catalogplus_structure_offer';
    }

    public function getGoods()
    {
        if (!isset($_COOKIE['basket'])) {
            return false;
        }
        $basket = json_decode($_COOKIE['basket'], true);
        if (count($basket['goods']) === 0) {
            return false;
        }
        $basket['total'] = 0;
        $basket['count'] = 0;
        foreach ($basket['goods'] as $k => $v) {
            $id = explode('_', $k);
            if (count($id) > 1) {
                $tmp = $this->getGoodInfo($id[0], $id[1]);
            } else {
                $tmp = $this->getGoodInfo($id[0]);
            }
            if ($tmp === false) {
                unset($basket['goods'][$k]);
                continue;
            }
            if (isset($tmp['count'])) {
                if (isset($v['count']) && ((int)$v['count'] > (int)$tmp['count'])) {
                    $v['warning'][] = 'Заказано больше чем есть на складе. Уточняйте у менеджера.';
                }
                unset ($tmp['count']);
            }
            $basket['goods'][$k] = array_merge($v, $tmp);
            $basket['goods'][$k]['total_price'] = $v['count'] * $tmp['sale_price'];
            $basket['total'] += $basket['goods'][$k]['total_price'];
            $basket['count'] += 1;
        }
        return $basket;
    }

    private function getGoodInfo($id, $offer = false)
    {
        $db = Db::getInstance();
        $config = Config::getInstance();
        if ($offer === false) {
            // Запрос в базу на получение информации о конкретном товаре
            $sql = "SELECT e.*, (CEIL(((100-e.sell)/100)*e.price)) AS sale_price
                    FROM {$this->tableGood} AS e
                    WHERE ID = {$id} AND e.is_active = 1
                    LIMIT 1";
        } else {
            // Запрос в базу на получение информации о конкретном предложении для товара
            $sql = "SELECT o.*, (CEIL(((100-g.sell)/100)*o.price)) AS sale_price, g.url, g.prev_structure AS prev
                    FROM {$this->tableOffer} AS o
                    INNER JOIN {$this->tableGood} as g ON (g.ID = {$id})
                    WHERE o.ID = {$offer} AND o.is_active = 1 AND g.ID= {$id}
                    LIMIT 1";
        }
        $info = $db->select($sql);
        if (count($info) === 0) {
            return false;
        }
        $info = $info[0];
        if ($offer !== false) {
            $field = $config->getStructureByName('CatalogPlus_Offer');
            $field = $field['fields'];
            foreach ($field as $k => $v) {
                if ($v['type'] != 'Ideal_Select') {
                    unset($field[$k]);
                }
            }
            $tmp = array_keys($field);
            foreach ($tmp as $v) {
                if ($info[$v] != '0') {
                    $info['offer'] = array(
                        'name' => $field[$v]['label'],
                        'value' => $field[$v]['values'][$info[$v]]
                    );
                }
            }
        }
        if (isset($info['prev'])) {
            $info['link'] = 'href="' . $this->getUrlByPrevStructure($info['prev'], $info['url']) . '"';
        } else {
            $info['link'] = 'href="' . $this->getUrlByPrevStructure($info['prev_structure'], $info['url']) . '"';
        }
        return $info;
    }


    public function getStructureElements()
    {
        return array();
    }

    public function detectPageByUrl($path, $url)
    {
        $db = Db::getInstance();

        // Для авторизированных в админку пользователей отображать скрытые страницы
        $user = new User\Model();
        $checkActive = ($user->checkLogin()) ? '' : ' AND is_active=1';

        $sql = "SELECT * FROM {$this->_table} WHERE url='{$url[0]}' {$checkActive} ORDER BY pos";

        $tabs = $db->select($sql); // запрос на получение всех табов, с этим урлом

        // Таб не нашли. Отображаем корзину
        if (!isset($tabs[0]['ID'])) {
            $this->path = $path;
            $this->is404 = true; // TODO обработка не существующего таба
            return $this;
        }

        if (count($tabs) > 1) {
            $c = count($tabs);
            Util::addError("В базе несколько ({$c}) табов для корзины с одинаковым url: " . implode('/', $url));
            $tabs = array($tabs[0]); // выводим таб который стоит раньше
        }
        $path[count($path) - 1]['tab'] = $tabs[0];

        $this->path = $path;

        return $this;
    }

    /**
     * Получение табов доступных для корзины
     * @return array
     */
    public function getTabs()
    {
        $db = Db::getInstance();
        $config = Config::getInstance();

        // Для авторизированных в админку пользователей отображать скрытые страницы
        $user = new User\Model();
        $checkActive = ($user->checkLogin()) ? '' : ' WHERE is_active=1';

        // Получаем все доступные табы
        $sql = "SELECT * FROM {$this->_table} {$checkActive} ORDER BY {$this->params['field_sort']}";
        $tabs = $db->select($sql);

        // Определяем текущий таб по url
        $curUrl = explode('/', $_SERVER['REQUEST_URI']);
        $curUrl = end($curUrl);

        // Папка где находится админка
        $cmsFolder = DOCUMENT_ROOT . '/' . $config->cmsFolder;

        $active = false; // тригер был ли активный таб среди полученных из быза
        $url = new \Ideal\Field\Url\Model();
        $cartUrl = $url->getUrl($this->pageData); // url корзины с постфиксом
        // Таб корзины(самый первый)
        $cartTab = array(
            'ID' => "0",
            'name' => $this->pageData['name'],
            'link' => "href='{$cartUrl}'",
            'url' => $this->pageData['url'],
            'is_show' => true
        );

        // Обрезаем постфикс с url корзины для правильного построения ссылок далее
        $count = strlen($config->urlSuffix);
        if ($count > 0) {
            $count = -1 * $count;
        } else {
            $count = strlen($cartUrl);
        }
        $cartUrl = substr($cartUrl, 0, $count); // url корзины без постфикса
        array_unshift($tabs, $cartTab);
        $newTabs = array();
        foreach ($tabs as $k => $v) {
            if (($v['url'] == $curUrl) && ($active == false)) {
                $v['link'] = '';
                $v['is_active'] = true;
                $active = true;
            } else {
                $v['is_active'] = false;
                // Ссылка на таб
                $v['link'] = 'href="' . $cartUrl . '/' . $v['url'] . $config->urlSuffix . '"';
            }

            if (strpos($v['template'], '_') == false) {
                // Строим путь до шаблона таба
                $tmp = str_replace($cmsFolder, '', stream_resolve_include_path($v['template']));
                $v['pathTab'] = $tmp;
                $v['is_show'] = true;
                $newTabs[] = $v;
            } else {
                $num = count($newTabs);
                if ($num > 0) {
                    list($v['modName'], $v['structure']) = explode('_', $v['template']);
                    $newTabs[$num - 1]['module'] = $v;
                }
            }

        }

        if (($active && !((bool)$newTabs[0]['is_active']))) {
            $newTabs[0]['link'] = 'href="' . $cartUrl . $config->urlSuffix . '"';
        }

        return $newTabs;

    }

    /**
     * Получение url для страницы по prev_structure
     * Построение начинает с предедущей структуры, то есть если страница находится в одной структуре, но
     * ее уровень не первый, то предется передовать полный путь до первого уровня в переменной @param $url
     *
     * @param string $prev prev_structure
     * @param string $url url данной страницы
     * @return string возвращает строку url с постфиксом
     * @throws \Exception
     */
    private function getUrlByPrevStructure($prev, $url)
    {
        $config = Config::getInstance();
        $db = Db::getInstance();
        $url = trim($url, ' \\');
        if (isset($this->linkArr[$prev])) {
            // Если для данной структуры мы уже строили путь
            return '/' . $this->linkArr[$prev] . '/' . $url . $config->urlSuffix;
        }
        $prevStructure = explode('-', $prev);
        $link = array(); // массив с url до корня(без него)
        $i = 0;
        do {
            $k = 0; // ключ к последнему элементу массива
            $prevConf = $config->getStructureById($prevStructure[0]); // конфиг структуры откуда берем данные
            $prevTable = $config->getTableByName($prevConf['structure']); // таблица структуры откуда берем ссылки
            $sql = "SELECT * FROM {$prevTable} WHERE ID = {$prevStructure[1]}";
            $tmp = $db->select($sql);
            if (!isset($tmp[0]['is_skip']) || ($tmp[0]['is_skip'] == '0')) {
                /* Если нужно проверять еще и на активность страницы
                 * if (!(isset($tmp[0]['is_active']) ^ ($tmp[0]['is_active'] == '1'))) {
                    $link[] = $tmp[0]['url'];
                }*/
                $link[] = $tmp[0]['url']; // добовляем ссылку в список
            }
            // Если у нас страница не первого уровня строим url до первого уровня
            if (isset($tmp[0]['lvl']) && ($tmp[0]['lvl'] > 1)) {
                $cid = new \Ideal\Field\Cid\Model($prevConf['params']['levels'], $prevConf['params']['digits']);
                $cids = $cid->getParents($tmp[0]['cid']); // Получаем все предедущие(родительские) cid
                $cids = '\'' . implode('\',\'', $cids) . '\'';
                $sql = "SELECT * FROM {$prevTable} WHERE cid IN ({$cids}) ORDER BY cid";
                $tmp = $db->select($sql);
                foreach ($tmp as $k => $v) {
                    if (!isset($v['is_skip']) || ($v['is_skip'] == '0')) {
                        /* Если нужно проверять еще и на активность страницы
                         * if (!(isset($v['is_active']) ^ ($v['is_active'] == '1'))) {
                            $link[] = $v['url'];
                        }*/
                        $link[] = $v['url']; // добовляем ссылку в список
                    }
                }
            }
            // Теперь ищем оставщиеся url из других структ, если есть
            $prevStructure = explode('-', $tmp[$k]['prev_structure']);
            // Защита от бесконечного циклп, а поскольку вряд ли возможна вложеность 10 уровня, на ней и останавливаемся
            $i++;
            if ($i > 10) {
                break;
            }
        } while ($prevStructure[0] != '0');
        // Записываем url для данной структуры($prev)
        $this->linkArr[$prev] = implode('/', $link);
        // Возвращаем сформированныей url
        return '/' . $this->linkArr[$prev] . '/' . $url . $config->urlSuffix;
    }

}
