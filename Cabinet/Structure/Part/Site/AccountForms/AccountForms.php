<?php
namespace Cabinet\Structure\Part\Site\AccountForms;

use FormPhp;
use Ideal\Core\View;
use Ideal\Core\Config;

/**
 * Класс обеспечивающий генерацию объектов и html-представлений форм, связанных с личным кабинетом
 */
class AccountForms
{

    /** @var string Адрес страницы, на которой располагается форма*/
    protected $link = '';

    public function setLink($link)
    {
        $this->link = $link;
    }

    /**
     * Генерирует html формы входа
     *
     * @return string
     */
    public function getLoginForm()
    {
        $form = $this->getloginFormObject();
        $formView = $this->templateInit('Cabinet/Structure/Part/Site/AccountForms/loginForm.twig');
        $formView->start = $form->start();
        $formView->link = $this->link;
        $formHtml = $formView->render();
        return $formHtml;
    }

    /**
     * Генерирует объект формы входа
     *
     * @return \FormPhp\Forms
     * @throws \Exception
     */
    public function getLoginFormObject()
    {
        $form = new FormPhp\Forms('loginForm');
        $form->setAjaxUrl('/?mode=ajax&controller=\\\\Cabinet\\\\Structure\\\\Part\\\\Site&action=login');
        $form->add('login', 'text');
        $form->add('pass', 'text');
        $form->setValidator('login', 'required');
        $form->setValidator('login', 'email');
        $form->setValidator('pass', 'required');
        return $form;
    }

    /**
     * Генерирует html формы личного кабинета
     *
     * @return string
     */
    public function getLkForm($user)
    {
        $form = $this->getLkFormObject();
        $formView = $this->templateInit('Cabinet/Structure/Part/Site/AccountForms/lkForm.twig');
        $formView->start = $form->start();
        $formView->user = $user;
        $formHtml = $formView->render();
        return $formHtml;
    }

    /**
     * Генерирует объект формы личного кабинета
     *
     * @return \FormPhp\Forms
     * @throws \Exception
     */
    public function getLkFormObject()
    {
        $form = new FormPhp\Forms('lkForm');
        $form->setAjaxUrl('/?mode=ajax&controller=\\\\Cabinet\\\\Structure\\\\Part\\\\Site&action=save');
        $form->add('fname', 'text');
        $form->add('phone', 'text');
        $form->add('addr', 'text');
        $form->add('pass', 'text');
        $form->setValidator('phone', 'phone');
        return $form;
    }

    /**
     * Закрытый метод для генерации отдельного экземпляра класса "Ideal\Core\View"
     * Используется для получения представления форм с помошью шаблонизатора Twig.
     *
     * @param string $tplName Имя шаблона
     * @return \Ideal\Core\View
     */
    private function templateInit($tplName = '')
    {
        if (!stream_resolve_include_path($tplName)) {
            echo 'Нет файла шаблона ' . $tplName;
            exit;
        }
        $tplRoot = dirname(stream_resolve_include_path($tplName));
        $tplName = basename($tplName);

        // Определяем корневую папку системы для подключение шаблонов из любой вложенной папки через их путь
        $config = Config::getInstance();
        $cmsFolder = DOCUMENT_ROOT . '/' . $config->cmsFolder;

        $folders = array_merge(array($tplRoot, $cmsFolder));
        $view = new View($folders, $config->cache['templateSite']);
        $view->loadTemplate($tplName);
        return $view;
    }
}