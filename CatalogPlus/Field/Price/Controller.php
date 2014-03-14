<?php
namespace CatalogPlus\Field\Price;

class Controller extends \Ideal\Field\AbstractController
{
    protected static $instance;


    public function getInputText()
    {
        $value = htmlspecialchars($this->getValue());
        return '<input type="text" class="form-control ' . $this->widthEditField
            . '" name="' . $this->htmlName
            . '" id="' . $this->htmlName
            .'" value="' . $value .'">';
    }

}