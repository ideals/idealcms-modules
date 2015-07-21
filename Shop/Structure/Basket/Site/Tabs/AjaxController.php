<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */
namespace Shop\Structure\Basket\Site\Tabs;

use FormPhp\Forms;
use Ideal\Core\Request;

class AjaxController extends \Ideal\Core\AjaxController
{
    // Обрабатывает запросы для формы из шаблона поумолчанию "Подтверждение заказа"
    public function confirmationAction()
    {
        $request = new Request();
        $form = new Forms('confirmationForm');
        $form->setAjaxUrl('/');
        $form->setSuccessMessage(false);
        $form->add('order_comments', 'text');
        if ($form->isPostRequest()) {
            if ($form->isValid()) {
                // Если валидация пройдена успешно, то записываем значение в куки
                setcookie("order_comments", $form->getValue('order_comments'));
            }
        } else {
            // Обработка запросов для получения функциональных частей формы
            switch ($request->target) {
                // Генерируем js
                case 'js':
                    $script = <<<JS
                    if (typeof window.checkform != 'undefined') {
                        window.checkform.push('#confirmationForm');
                        } else {
                        window.checkform = ['#confirmationForm'];
                        }
JS;
                    $form->setJs($script);
                    $request->mode = 'js';
                    break;
                // Генерируем css
                case 'css':
                    $request->mode = 'css';
                    break;
                // Генерируем стартовую часть формы
                case 'start':
                    echo $form->start();
                    exit();
                    break;
            }
            $form->render();
        }
        exit();
    }
}
