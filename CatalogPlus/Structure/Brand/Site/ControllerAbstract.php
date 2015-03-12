<?php
namespace CatalogPlus\Structure\Brand\Site;

use Ideal\Structure\Part;

class ControllerAbstract extends Part\Site\Controller
{
    public function detailAction()
    {
        $this->templateInit('CatalogPlus\\Structure\\Brand\\Site\\detail.twig');
    }
}
