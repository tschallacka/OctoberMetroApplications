<?php namespace Applications\Widgets\FormField;

use Applications\Widgets\WidgetBase;
use October\Rain\Database\Model;

class FormField extends WidgetBase {
    protected $name = 'formfield';
    protected $model = null;

    protected function initWidget() {
         $this->setModel($this->getApplication()->getModel());

    }

    public function setModel($model=null) {
        $this->model = $model;
    }



    public function getId($name='') {
        if($this->model) {
            $class = class_basename($this->model);
            return $class.$name;
        }
    }
    public function formFieldName() {
        if($this->model) {
            $class = class_basename($this->model);
            return $class.'['.$this->getFieldName().']';
        }
    }
    public function getPostValue() {
        if($this->model) {
            $class = class_basename($this->model);
            if(post($class)) {

            }
        }
    }
    public function prepareVars() {
        $vars = [];
        if($fieldname = $this->formFieldName()) {
            $vars['name'] = $fieldname;
        }
        if($id = $this->getId()) {
            $vars['id'] = $id;
        }
        if($this->model && $value = $this->model->getAttribute($fieldname)) {
            $vars['value'] = $value;
        }
        return $vars;
    }

    public function render() {
        $vars = $this->prepareVars();
        echo 'x';
        return $this->getPartial('formfield', $vars);
    }

}