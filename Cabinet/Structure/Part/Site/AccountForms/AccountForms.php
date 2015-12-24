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

    /** @var string Адрес страницы, на которой располагается форма */
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
     * Генерирует html формы восстановления пароля
     *
     * @return string
     */
    public function getRestoreForm()
    {
        $form = $this->getRestoreFormObject();
        $formView = $this->templateInit('Cabinet/Structure/Part/Site/AccountForms/restoreForm.twig');
        $formView->start = $form->start();
        $formHtml = $formView->render();
        return $formHtml;
    }

    /**
     * Генерирует объект формы восстановления пароля
     *
     * @return \FormPhp\Forms
     * @throws \Exception
     */
    public function getRestoreFormObject()
    {
        $form = new FormPhp\Forms('restoreForm');
        $form->setAjaxUrl('/?mode=ajax&controller=\\\\Cabinet\\\\Structure\\\\Part\\\\Site&action=restore');
        $form->add('login', 'text');
        $form->setValidator('login', 'required');
        $form->setValidator('login', 'email');
        return $form;
    }

    /**
     * Генерирует html формы регистрации
     *
     * @return string
     */
    public function getRegistrationForm()
    {
        $form = $this->getRegistrationFormObject();
        $formView = $this->templateInit('Cabinet/Structure/Part/Site/AccountForms/registrationForm.twig');
        $formView->start = $form->start();
        $formView->link = $this->link;
        $formHtml = $formView->render();
        return $formHtml;
    }

    /**
     * Генерирует объект формы регистрации
     *
     * @return \FormPhp\Forms
     * @throws \Exception
     */
    public function getRegistrationFormObject()
    {
        $form = new FormPhp\Forms('registrationForm');
        $form->setAjaxUrl('/?mode=ajax&controller=\\\\Cabinet\\\\Structure\\\\Part\\\\Site&action=registration');
        $form->setClearForm(false);
        $form->add('lastname', 'text');
        $form->add('name', 'text');
        $form->add('phone', 'text');
        $form->add('addr', 'text');
        $form->add('email', 'text');
        $form->add('int', 'text');
        $form->add('link', 'text');
        $form->setValidator('lastname', 'required');
        $form->setValidator('name', 'required');
        $form->setValidator('phone', 'required');
        $form->setValidator('phone', 'phone');
        $form->setValidator('addr', 'required');
        $form->setValidator('email', 'required');
        $form->setValidator('email', 'email');
        $form->setValidator('int', 'required');
        $form->setValidator('int', 'captcha');
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