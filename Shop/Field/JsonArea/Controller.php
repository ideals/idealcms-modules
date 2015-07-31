<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Shop\Field\JsonArea;

use Ideal\Field\AbstractController;

/**
 * Отображение редактирования поля в админке в виде textarea
 *
 * Пример объявления в конфигурационном файле структуры:
 *     'annotation' => array(
 *         'label' => 'Аннотация',
 *         'sql'   => 'text',
 *         'type'  => 'Ideal_Area'
 *     ),
 */
class Controller extends AbstractController
{

    /** {@inheritdoc} */
    protected static $instance;

    /**
     * {@inheritdoc}
     */
    public function getInputText()
    {
        $value = implode("\n", json_decode(htmlspecialchars_decode($this->getValue())));
        return
            '<textarea class="form-control" name="' . $this->htmlName
            . '" id="' . $this->htmlName
            . '">' . $value . '</textarea>';
    }

    public function pickupNewValue()
    {
        $value = parent::pickupNewValue();
        $value = str_replace("\r", '', $value);
        $value = htmlspecialchars(json_encode(explode("\n", $value)));
        return $value;
    }
}
