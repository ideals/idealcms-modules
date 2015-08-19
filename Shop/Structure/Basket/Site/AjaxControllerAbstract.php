<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */
namespace Shop\Structure\Basket\Site;

use Ideal\Core\Db;
use Ideal\Core\Config;
use Ideal\Structure\Service\SiteData\ConfigPhp;

class AjaxControllerAbstract extends \Ideal\Core\AjaxController
{
    /** @var array типовой массив для ответа из AjaxController */
    protected $answer = array(
        'error' => false, // статус об ошибки
        'text' => '' // ответ, может содержать как сообщение об ошибки так и о успешном завершении работы скрипта
    );

    /** @var array корзина с товарами(предложения) и сводкой данных о самой корзине */
    protected $basket;

    /** @var bool тригер на обновление информации товара(предложения) в корзине */
    protected $update = false; // по-умолчанию обновлять не нужно корзину

    /** @var string|int id элемента с которым работаем. Пример правильного (ID-товра_ID-предложения или ID-товара) */
    protected $idGood;

    /** @var int кол-во товара(предложений) которые планируется добавить в корзину, если пусто то +1 */
    protected $quant;

    /** @var string Значение скидки (может приутствовать знак "%") */
    protected $disco = '';

    /** @var string Общая разница между скидочной и нормальной ценой */
    protected $discoValue = '';

    /** @var int время жизни корзины в секундах, если время обновления(создания) корзины больше, то она обновляется */
    protected $timeLive = 7200;

    // Таблицы с товарами и предложениями в конструкторе
    // TODO определять автоматически модуль из которых надо брать, сейчас прописано CatalogPlus
    protected $tableGood;
    protected $tableOffer;

    /**
     * Генерация данных и установка значений по умолчанию
     */
    public function __construct()
    {
        if (isset($_REQUEST['add-to-cart'])) {
            $this->idGood = $_REQUEST['add-to-cart'];
        }
        if (isset($_REQUEST['quantity'])) {
            $this->quant = $_REQUEST['quantity'];
        }
        if (isset($_COOKIE['basket'])) {
            $this->basket = json_decode($_COOKIE['basket'], true);
            // Определяем как давно была обновлена(создан) корзина
            if ((time() - $this->basket['versi']) > $this->timeLive) {
                $this->update = true;
            }
        } else {
            // Установка значений по умолчанию для корзины
            $this->basket = array(
                'goods' => array(),
                'price' => 0,
                'count' => 0,
                'versi' => time(),
                'disco' => '',
                'discoValue' => 0,
                'total' => 0
            );
        }

        // Указываем таблицы где хранятся данные
        $config = Config::getInstance();
        $prefix = $config->db['prefix'];
        $this->tableGood = $prefix . 'catalogplus_structure_good';
        $this->tableOffer = $prefix . 'catalogplus_structure_offer';

    }

    /**
     * Завершение работы срипта корзины и вывод результата на экран в формате json строки
     * После полная остановка работы
     */
    public function __destruct()
    {
        // Если было установлено правило на пересбор корзины
        if ($this->update) {
            if (isset($this->basket['tabsInfo'])) {
                $tabsInfo = $this->basket['tabsInfo'];
            } else {
                $tabsInfo = array();
            }
            $goods = $this->basket['goods'];
            $this->basket = array(
                'goods' => array(), // товары которые находятся в корзине
                'price' => 0, // общая цена без учета скидки
                'count' => 0, // кол-во наименнований в корзине
                'disco' => 0, // значенеи скидки
                'discoValue' => 0, // общая разница между скидочной и нормальной ценой
                'total' => 0  // общая цена с учетом скидки
            );
            if (!empty($tabsInfo)) {
                $this->basket['tabsInfo'] = $tabsInfo;
            }
            $this->basket['disco'] = $this->disco;
            foreach ($goods as $k => $v) {
                $this->idGood = $k;
                $this->quant = $v['count'];
                $this->addGoodAction(true);
                unset($goods[$k]);
            }
            $this->basket['versi'] = time();
            if (stripos($this->disco, '%') !== false) {
                $this->discoValue = intval(str_replace('%', '', $this->disco));
                $tempTotal = intval($this->basket['total']);
                $this->basket['discoValue'] =  round($tempTotal / 100 * $this->discoValue, 2);
            } else {
                $this->basket['discoValue'] = $this->disco;
            }
            $this->basket['total'] -= $this->basket['discoValue'];
            setcookie("basket", json_encode($this->basket, JSON_FORCE_OBJECT));
        }
        $this->answer['basket'] = $this->basket;
        print json_encode($this->answer);
        exit();
    }

    /**
     * Добавление товара в корзину
     * @param bool $local тригер на проверка запуска из другого метода класса
     * @return bool|null
     */
    public function addGoodAction($local = false)
    {
        $id = $good = $this->idGood;
        $quant = (int)$this->quant;
        if (!($quant > 0)) {
            if (!$local) {
                exit();
            } else {
                return false;
            }
        }
        $good = explode('_', $good); // получение id товара(обязан быть) и предложение(если есть)
        if (count($good) > 1) {
            $good = $this->getGoodInfo($good[0], $good[1]);
        } else {
            $good = $this->getGoodInfo($good[0]);
        }
        if (count($good) === 0) {
            // Не удалось получить информацию(цену) о товаре(предложении)
            // Значит товар(предложение) отсутвует или распродан/о
            if (!$local) {
                exit();
            } else {
                return false;
            }
        }
        if (isset($this->basket['goods'][$id])) {
            $this->basket['goods'][$id]['count'] += $quant;
        } else {
            $this->basket['goods'][$id] = array(
                'price' => $good['price'],
                'count' => $quant,
                'sale_price' => $good['sale_price'],
                'name' => $good['name']
            );
            $this->basket['count'] += 1;
        }
        $this->basket['price'] += ($quant * $good['price']);
        $this->basket['total'] += ($quant * $good['sale_price']);
        $this->basket['disco'] += ($quant * $good['discount']);
        if (!$local) {
            exit();
        } else {
            return true;
        }
    }

    /**
     * Удаление товара в корзине
     * @param bool $local тригер на проверка запуска из другого метода класса
     * @return bool
     */
    public function delGoodAction($local = false)
    {
        unset($this->basket['goods'][$this->idGood]);
        $this->update = true;
        if (!$local) {
            exit();
        } else {
            return true;
        }
    }

    /**
     * Изменение кол-ва товара в корзине
     * @param bool $local тригер на проверка запуска из другого метода класса
     * @return bool
     */
    public function quantGoodAction($local = false)
    {
        $this->basket['goods'][$this->idGood]['count'] = (int)$this->quant;
        $this->disco = $this->basket['disco'];
        $this->update = true;
        if (!$local) {
            exit();
        } else {
            return true;
        }
    }

    /**
     * Функция на запрос состояние корзины
     */
    public function getBasketAction()
    {
        exit();
    }

    /**
     * Функция на запрос состояние корзины
     */
    public function clearBasketAction()
    {
        setcookie("basket", '', time() - 3600);
        exit();
    }

    /**
     * Функция на запрос примененния промо кода
     */
    public function discountApplyAction()
    {
        $file = new ConfigPhp();
        $filePath = stream_resolve_include_path("Shop/Structure/Service/ShopSettings/shop_settings.php");
        $file->loadFile($filePath);
        $params = $file->getParams();
        // Ищем в промо кодах введённый
        $promoCodesInfo = json_decode(htmlspecialchars_decode($params['default']['arr']['promoCodes']['value']));
        $discountInfo = array();
        foreach ($promoCodesInfo as $promoCodeInfo) {
            if (stripos($promoCodeInfo, $_REQUEST['promoCode']) !== false) {
                $discountInfo = $promoCodeInfo;
                break;
            }
        }
        if (!empty($discountInfo)) {
            $discountInfo = explode('|', $discountInfo);
            $promoFromDate = strtotime(str_replace('.', '-', $discountInfo[2]));
            $promoToDate = strtotime(str_replace('.', '-', $discountInfo[3]));
            if ($promoFromDate <= time() && $promoToDate >= time()) {
                $this->disco = $discountInfo[1];
                $this->update = true;
            } else {
                $this->answer['error'] = true;
                $this->answer['text'] = 'Данный промо код не удовлетворяет периоду проведения акции';
            }
        } else {
            $this->answer['error'] = true;
            $this->answer['text'] = 'Промо код не найден';
        }
        exit();
    }

    /**
     * TODO Удаление корзины полностью
     */
    public function delBasket()
    {

    }

    /**
     * Получение информации о товара(предложении) его цена и цена с учетом скидки
     *
     * @param $id
     * @param bool $offer
     * @return mixed
     */
    private function getGoodInfo($id, $offer = false)
    {
        $db = Db::getInstance();
        if ($offer === false) {
            // Запрос в базу на получение информации о конкретном товаре
            $sql = "SELECT e.price, (CEIL(((100-e.sell)/100)*e.price)) AS sale_price, e.name
                    FROM {$this->tableGood} AS e
                    WHERE ID = {$id} AND e.is_active = 1
                    LIMIT 1";
        } else {
            // Запрос в базу на получение информации о конкретном предложении для товара
            $sql = "SELECT o.price, (CEIL(((100-g.sell)/100)*o.price)) AS sale_price, o.name
                    FROM {$this->tableOffer} AS o
                    INNER JOIN {$this->tableGood} as g ON (g.ID = {$id})
                    WHERE o.ID = {$offer} AND o.is_active = 1
                    LIMIT 1";
        }
        $allPrice = $db->select($sql);
        if (count($allPrice) === 0) {
            // Нету товара(предложения) возвращаем пустой массив
            return array();
        }
        $allPrice[0]['price'] /= 100; // в базе цена умножена на 100
        $allPrice[0]['sale_price'] /= 100; // для хранения дробной части в целочисленной
        // разница в цене и скидочной цене
        $allPrice[0]['discount'] = $allPrice[0]['price'] - $allPrice[0]['sale_price'];
        return $allPrice[0];
    }

}
