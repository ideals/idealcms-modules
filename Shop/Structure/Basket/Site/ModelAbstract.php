<?php
namespace Shop\Structure\Basket\Site;

use Ideal\Core\Db;
use Ideal\Core\Util;
use Ideal\Core\Config;
use Ideal\Core\Request;
use Ideal\Structure\User;

class ModelAbstract extends \Ideal\Core\Site\Model
{
    /** @var \CatalogPlus\Structure\Good\Site\Model */
    protected $goodsModel;

    public function __construct($prevStructure)
    {
        parent::__construct($prevStructure);
        $config = Config::getInstance();
        if ($config->getStructureByName('Catalog_Good')) {
            $this->goodsModel = new \Catalog\Structure\Good\Site\Model($prevStructure);
        } elseif ($config->getStructureByName('CatalogPlus_Good')) {
            $this->goodsModel = new \CatalogPlus\Structure\Good\Site\Model($prevStructure);
        } else {
            \Ideal\Core\Util::addError('Не подключен модуль с товарами');
        }
    }

    /**
     * Получение информации с каждого этапа оформления заказа
     *
     * @return array
     */
    public function getTabsInfo()
    {
        $tabsInfo = array();
        if (isset($_COOKIE['tabsInfo'])) {
            $tabsInfo = json_decode($_COOKIE['tabsInfo'], true);
        }
        return $tabsInfo;
    }

    /**
     * Получение полной информации о товарах в корзине
     *
     * @return array
     */
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

        // todo где лучше раскладывать корзину по офферам тут или в товарах? Наверное лучше тут

        $goods = $this->goodsModel->getGoodsInfo(array_keys($basket['goods']));
        //$goods = $this->goodsModel->goodsFromBasket($basket['goods']);
        foreach ($basket['goods'] as $k => $v) {
            if (!isset($goods[$k])) {
                unset($basket['goods'][$k]);
                continue;
            } else {
                $good = $goods[$k];
            }
            if (isset($good['count'])) {
                if (isset($v['count']) && ((int)$v['count'] > (int)$good['count'])) {
                    $v['warning'][] = 'Заказано больше чем есть на складе. Уточняйте у менеджера.';
                }
                unset ($good['count']);
            }
            $basket['goods'][$k] = array_merge($v, $good);
            $basket['goods'][$k]['total_price'] = $v['count'] * $v['sale_price'];
            $basket['total'] += $basket['goods'][$k]['total_price'];
            $basket['count'] += 1;
        }
        // Применяем скидку, если она есть
        if (!empty($basket['disco'])) {
            $basket['total'] -= $basket['discoValue'];
        }
        return $basket;
    }

    /**
     * Получаем первый слайд для начала оформления заказа
     *
     * @return array
     */
    public function getFirstTab()
    {
        $db = Db::getInstance();
        $sql = "SELECT * FROM {$this->_table} WHERE is_active=1 ORDER BY {$this->params['field_sort']} LIMIT 1";
        $tab = $db->select($sql);
        if (count($tab) < 1) {
            return array();
        }
        $tab = $tab[0];
        $path = $this->getPath();
        $url = new \Ideal\Field\Url\Model();
        // Указываем родителя для url что бы можно было получить корректный url
        $url->setParentUrl($path);
        $tab['link'] = 'href="' . $url->getUrl($tab) . '"';
        return $tab;
    }

    /**
     * {@inheritdoc}
     */
    public function detectPageByUrl($path, $url)
    {
        $db = Db::getInstance();

        // Для авторизированных в админку пользователей отображать скрытые страницы
        $user = new User\Model();
        $checkActive = ($user->checkLogin()) ? '' : ' AND is_active=1';

        $sql = "SELECT * FROM {$this->_table} WHERE BINARY url='{$url[0]}' {$checkActive} ORDER BY pos";

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

        $tabs[0]['structure'] = 'Shop_Basket';
        $tabs[0]['url'] = $url[0];

        $this->path = array_merge($path, $tabs);

        $request = new Request();
        $request->action = 'detail';

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

        // Определяем путь к табам
        $path = $this->getPath();
        $active = null;
        if ($path[count($path) - 2]['structure'] == 'Shop_Basket') {
            // Если предпоследний элемент пути - корзина, то нужно срезать последний элемент,
            // чтобы выстроить пути ко всем табам
            $active = array_pop($path);
        }

        // Строим ссылки на табы
        $url = new \Ideal\Field\Url\Model();
        $url->setParentUrl($path);
        $cookieTabsInfo = 0;
        if (isset($_COOKIE['tabsInfo'])) {
            $cookieTabsInfo = json_decode($_COOKIE['tabsInfo']);
        }
        foreach ($tabs as $k => $tab) {
            $tabs[$k]['link'] = 'href="' . $url->getUrl($tab) . '"';
            $tabs[$k]['is_current'] = (!empty($active) && $active['ID'] == $tab['ID']);
            if (!empty($cookieTabsInfo)) {
                $checkedTab = 'tab_' . ($k + 1);
                if (isset($cookieTabsInfo->$checkedTab)) {
                    $tabs[$k]['tabWasFilled'] = 1;
                }
            }
        }

        // Добавляем самый первый таб - ссылка на корзину
        $basket = array_pop($path);
        $url->setParentUrl($path);
        array_unshift($tabs, array(
            'ID' => "0",
            'name' => $basket['name'],
            'link' => "href='{$url->getUrl($basket)}'",
            'url' => $basket['url'],
            'is_show' => true
        ));
        
        return $tabs;
    }

    public function setEmptyBasket()
    {
        $pageData = $this->getPageData();
        $pageData['template'] = 'Shop/Structure/Basket/Site/empty.twig';
        $this->setPageData($pageData);
    }

    public function getCurrentTabId($tabs)
    {
        $pageData = $this->getPageData();
        foreach ($tabs as $key => $value) {
            if (array_search($pageData['ID'], $value, true) !== false) {
                return $key;
            }
        }
        return false;
    }
}
