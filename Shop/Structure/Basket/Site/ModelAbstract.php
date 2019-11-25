<?php
namespace Shop\Structure\Basket\Site;

use Ideal\Core\Db;
use Ideal\Core\Util;
use Ideal\Core\Config;
use Ideal\Core\Request;
use Ideal\Structure\Service\SiteData\ConfigPhp;
use Ideal\Structure\User;

class ModelAbstract extends \Ideal\Core\Site\Model
{
    /** @var \CatalogPlus\Structure\Good\Site\Model */
    protected $goodModel;

    /** @var array Краткая корзина с товарами и сводкой данных о самой корзине */
    protected $basketCookie;

    /** @var array Корзина с товарами и сводкой данных о самой корзине */
    protected $basket;

    /** @var bool тригер на обновление информации товара(предложения) в корзине */
    protected $update = false; // по-умолчанию обновлять не нужно корзину

    /** @var int время жизни корзины в секундах, если время обновления(создания) корзины больше, то она обновляется */
    protected $timeLive = 7200;

    // Таблица со списком страниц относящихся к процессу оформления заказа
    protected $table;

    public function __construct($prevStructure)
    {
        parent::__construct($prevStructure);

        $config = Config::getInstance();
        if ($config->getStructureByName('Catalog_Good')) {
            $this->goodModel = new \Catalog\Structure\Good\Site\Model($prevStructure);
        } elseif ($config->getStructureByName('CatalogPlus_Good')) {
            $this->goodModel = new \CatalogPlus\Structure\Good\Site\Model($prevStructure);
        } else {
            \Ideal\Core\Util::addError('Не подключен модуль с товарами');
        }

        if (isset($_COOKIE['basket'])) {
            $this->basketCookie = json_decode($_COOKIE['basket'], true);
            // Определяем как давно была обновлена / cоздана корзина
            if (!isset($this->basketCookie['time'])) {
                $this->basketCookie['timei'] = time();
            }
            if ((time() - $this->basketCookie['time']) > $this->timeLive) {
                $this->update = true;
            }
        } else {
            // Установка значений по умолчанию для корзины
            $this->basketCookie = $this->getClearBasket();
        }

        // Указываем таблицы где хранятся данные
        $this->table = $config->db['prefix'] . 'shop_structure_basket';
    }

    /**
     * Получение полного списка товаров со всеми их свойствами
     *
     * @return array
     * @throws \Exception
     */
    public function getBasket()
    {
        if (!empty($this->basket)) {
            // Если корзина уже сформирована, возвращаем её
            return $this->basket;
        }
        // Формируем корзину на основе краткой версии из oookies
        $basket = $this->getClearBasket();
        $goodsDb = $this->goodModel->getGoodsInfo(array_keys($this->basketCookie['goods']));
        foreach ($this->basketCookie['goods'] as $id => $good) {
            if (empty($goodsDb[$id])) {
                // Если такого товара нет в БД (удалили из выгрузки), то убираем его из корзины
                continue;
            }
            $basket['goods'][$id] = array_merge($good, $goodsDb[$id]);
        }
        $basket = $this->recalcTotal($basket);
        $this->basket = $basket;

        $this->update = true;
        $this->getBasketCookie();

        return $basket;
    }

    /**
     * Получение краткой версии корзины, только с информацией из cookies
     *
     * @return array
     * @throws \Exception
     */
    public function getBasketCookie()
    {
        if (!$this->update) {
            return $this->basketCookie;
        }

        $this->update = false;

        // Если требуется обновить корзину, перечитываем её из БД
        $basket = $this->getBasket();
        $goods = $basket['goods'];
        // Выделяем только цены
        $basketCookie = $this->getClearBasket();
        foreach ($this->basketCookie['goods'] as $id => $good) {
            if (empty($goods[$id])) {
                // Если товара в корзине не оказалось, значит его отключили в БД, убираем его из корзины
                continue;
            }
            $good['price'] = $goods[$id]['price'];
            $good['price_old'] = $goods[$id]['price_old'];
            $basketCookie['goods'][$id] = $good;
        }
        $basketCookie = $this->recalcTotal($basketCookie);
        $this->basketCookie = $basketCookie;
        $this->saveBasket();

        return $basketCookie;
    }

    /**
     * Добавление товара или изменение его количества в корзине
     *
     * Если параметр $good['count'] передаётся со знаком '+", то указанное количество товара будет
     * добавлено к существующему, если без знака, то будет установлено переданное количество товара
     *
     * @param $good
     * @throws \Exception
     */
    public function addGood($good)
    {
        $id = $good['id'];
        $mode = strpos($good['count'], '+') === 0 ? 'add' : 'set';
        $good['count'] = (int)$good['count'];

        if ($good['count'] === 0) {
            // Передано нулевое кол-во, значит нужно удалить товар из корзины
            if (isset($this->basketCookie['goods'][$id])) {
                $this->delGood($id);
            }
            return;
        }

        $goodsInfo =  $this->goodModel->getGoodsInfo(array($id));

        if (empty($goodsInfo[$id])) {
            // Не удалось получить информацию(цену) о товаре(предложении)
            // Значит товар(предложение) отсуствует или распродан/о и нужно убрать его из корзины
            if (isset($this->basket['goods'][$id])) {
                $this->delGood($id);
            }
            return;
        }

        if (isset($this->basketCookie['goods'][$id])) {
            if ($mode === 'add') {
                $this->basketCookie['goods'][$id]['count'] += $good['count'];
            } else {
                $this->basketCookie['goods'][$id]['count'] = $good['count'];
            }
        } else {
            $good['price'] = $goodsInfo[$id]['price'];
            $good['price_old'] = $goodsInfo[$id]['price_old'];
            $this->basketCookie['goods'][$id] = $good;
        }
        $this->basketCookie = $this->recalcTotal($this->basketCookie);
        $this->saveBasket();
        // Очищаем корзину с полными описаниями товаров - если она потребуется, то будет пересобрана
        unset($this->basket);
    }

    /**
     * Удаление товара из корзины
     *
     * @param $id
     */
    public function delGood($id)
    {
        if (!empty($this->basket['goods'][$id])) {
            unset($this->basket['goods'][$id]);
            $this->basket = $this->recalcTotal($this->basket);
        }
        unset($this->basketCookie['goods'][$id]);
        $this->basketCookie = $this->recalcTotal($this->basketCookie);
        $this->saveBasket();
    }

    /**
     * Построение пустой корзины, с минимально необходимым набором полей
     *
     * @return array
     */
    protected function getClearBasket()
    {
        return array(
            'goods' => array(), // товары которые находятся в корзине
            'total_old' => 0, // общая сумма цен без скидок
            'total' => 0, // общая цена с учетом скидки
            'time' => time(),
        );
    }

    /**
     * Пересчёт суммыарной стоимости товаров в корзине
     *
     * @param array $basket
     * @return array
     */
    protected function recalcTotal($basket)
    {
        $total = $totalOld = 0;
        if (!empty($basket['goods'])) {
            foreach ($basket['goods'] as $good) {
                $total += $good['price'] * $good['count'];
                $totalOld += $good['price_old'] * $good['count'];
            }
        }
        $basket['total'] = $total;
        $basket['total_old'] = $totalOld;

        return $basket;
    }

    /**
     * Сохранение корзины в Cookies
     */
    protected function saveBasket()
    {
        setcookie(
            'basket',
            json_encode($this->basketCookie, JSON_FORCE_OBJECT),
            strtotime('+1 year'),
            '/'
        );
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
