<?php
namespace CatalogPlus\Field\Category;

class Controller extends \Ideal\Field\AbstractController
{
    protected $medium; // объект доступа к редактируемым данным

    public function setModel($model, $fieldName, $groupName = 'general')
    {
        parent::setModel($model, $fieldName, $groupName);

        // Загоняем в $this->list список значений select
        $className = $this->field['medium'];
        $this->medium = new $className($this->model, $fieldName);
    }


    public function getInputText()
    {
        $list = $this->medium->getList();
        $variants = $this->medium->getVariants();
        $html = '<select multiple="multiple" class="form-control" name="' . $this->htmlName . '[]" id="' . $this->htmlName . '">';
        foreach ($list as $k => $v) {
            $selected = '';
            if (in_array($k, $variants)) {
                $selected = ' selected="selected"';
            }
            $html .= '<option value="' . $k . '"' . $selected . '>' . $v . '</option>';
        }
        $html .= '</select>';
        return $html;
    }


    public function parseInputValue($isCreate)
    {
        // Если товар связан с категорией через промежуточную таблицу

        $this->newValue = null;
        $newValue = $this->pickupNewValue();

        $item = array(
            'fieldName' => $this->htmlName,
            'value' => null,
            'message' => '',
            'sqlAdd' => $this->medium->getSqlAdd($newValue)
        );

        return $item;
    }

}