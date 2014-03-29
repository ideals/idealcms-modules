<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Catalog\Field\Price;

/**
 * Class Controller
 */
class Controller extends \Ideal\Field\AbstractController
{
    protected static $instance;

    /**
     * {@inheritdoc}
     */
    public function getInputText()
    {
        $value = htmlspecialchars($this->getValue());
        return '<input type="text" class="form-control" name="' . $this->htmlName
            . '" id="' . $this->htmlName
            .'" value="' . $value .'">';
    }

    /**
     * {@inheritdoc}
     */
    public function getValue()
    {
        $value = intval(parent::getValue()) / 100;
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getValueForList($values, $fieldName)
    {
        return number_format($values[$fieldName] / 100, 2, ',', ' ');
    }


}