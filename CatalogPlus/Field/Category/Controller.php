<?php

namespace CatalogPlus\Field\Category;

use Ideal\Field\AbstractController;
use Ideal\Medium\AbstractModel;

class Controller extends AbstractController
{
    /** @var AbstractModel */
    protected $medium; // объект доступа к редактируемым данным

    public function setModel($model, $fieldName, $groupName = 'general'): void
    {
        parent::setModel($model, $fieldName, $groupName);

        // Загоняем в $this->list список значений select
        $className = $this->field['medium'];
        $this->medium = new $className($this->model, $fieldName);
    }


    public function getInputText(): string
    {
        $list = $this->medium->getList();
        $variants = $this->medium->getValues();
        $html = '<select multiple="multiple" class="form-control" name="' . $this->htmlName
            . '[]" id="' . $this->htmlName . '">';
        foreach ($list as $k => $v) {
            $selected = '';
            if (in_array($k, $variants)) {
                $selected = ' selected="selected"';
            }

            $html .= '<option value="' . $k . '"' . $selected . '>' . $v . '</option>';
        }

        return $html . '</select>';
    }


    /**
     * @return array<string, string|null>
     */
    public function parseInputValue($isCreate): array
    {
        // Если товар связан с категорией через промежуточную таблицу

        $this->newValue = null;
        $newValue = $this->pickupNewValue();

        return [
            'fieldName' => $this->htmlName,
            'value' => null,
            'message' => '',
            'sqlAdd' => $this->medium->getSqlAdd($newValue),
        ];
    }
}
