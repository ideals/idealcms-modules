<?php
namespace Cabinet\Structure\Part\Site;

use FormPhp;
use Ideal\Core\View;
use Ideal\Core\Config;
use Ideal\Field;

class ModelAbstract extends \Ideal\Structure\Part\Site\Model
{

    /**
     * Генерирует html формы входа
     *
     * @return string
     */
    public function getLoginForm()
    {
        $form = $this->getloginFormObject();
        $formView = $this->templateInit('Cabinet/Structure/Part/Site/loginForm.twig');
        $formView->start = $form->start();
        $formView->link = $this->getFullUrl();
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
     * Генерация абсолютного пути до страницы логина/регистрации/подтверждения
     *
     * @return string Абсолютный путь до страницы логина/регистрации/подтверждения
     */
    private function getFullUrl()
    {
        $pageData = $this->getPageData();
        $url = new Field\Url\Model();
        $link = $url->getUrl($pageData);
        return $link;
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
