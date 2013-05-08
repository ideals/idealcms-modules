<?php
namespace Articles\Field\Category;

class Controller extends \Ideal\Field\AbstractController
{
    protected $getter; // объект доступа к редактируемым данным

    public function setModel($model, $fieldName, $groupName = 'general')
    {
        parent::setModel($model, $fieldName, $groupName);

        // Загоняем в $this->list список значений select
        $className = $this->field['class'];
        $this->getter = new $className($this->model, $fieldName);
    }


    public function getInputText()
    {
        $list = $this->getter->getList();
        $variants = $this->getter->getVariants();
        $html = '<select class="' . $this->widthEditField . '" name="' . $this->htmlName .'" id="' . $this->htmlName .'">';
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
        $this->newValue = null;

        $newValue = $this->pickupNewValue();

        $item = array(
            'fieldName' => $this->htmlName,
            'value' => null,
            'message' => '',
            'sqlAdd' => $this->getter->getSqlAdd($newValue)
        );

        return $item;
    }

}