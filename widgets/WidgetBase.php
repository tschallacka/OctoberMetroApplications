<?php namespace Applications\Widgets;

use Applications\ApplicationBase;
use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

abstract class WidgetBase {
    protected $name;
    protected $config;
    protected $application;
    protected $fieldName = '';
    public function getName() {
        return $this->name;
    }
    public function __construct(ApplicationBase $app, $config) {
        $this->application = $app;
        $this->config = $config;
        $this->initWidget();
    }
    /**
     * Renders a partial in the view directory
     * @param unknown $path
     * @param unknown $vars
     */
    public function getPartial($path, $vars) {

        $filepath = $this->getPath(DIRECTORY_SEPARATOR.'view'.DIRECTORY_SEPARATOR.$path);

        return $this->renderfile($filepath,$vars);
    }

    public function setFieldName($name) {
        $this->fieldName = $name;
    }

    public function getFieldName() {
        return $this->fieldName;
    }

    protected function getApplication() {
        return $this->application;
    }

    protected function getConfig($option=null) {
        return is_null($option) ? $this->config : $this->config->{$option};
    }

    /**
     * Called to initialise the widget
     */
    protected abstract function initWidget();
    /**
     * Called to render the widet
     */
    public abstract function render();

    private function renderfile($filepath, $vars) {
        if(!file_exists($filepath)) {
            if(file_exists($filepath.'.html')) {
                $filepath .= '.html';
            }
            elseif(file_exists($filepath.'.htm')) {
                $filepath .= '.htm';
            }
            elseif(file_exists($filepath.'.php')) {
                $filepath .= '.php';
            }
            elseif(file_exists($filepath.'.txt')) {
                $filepath .= '.txt';
            }
            else {
                throw new FileNotFoundException('File does not exist '.$filepath);
            }
        }

        $this->vars = $vars;
        extract($vars);
        ob_start();
        include $filepath;
        $renderedView = ob_get_clean();

        return $renderedView;
    }
    /**
     * Returns the path to the given resource, relative from the applications root path
     * @param string $resource
     */
    public function getPath($resource='') {
        return __DIR__ . DIRECTORY_SEPARATOR . strtolower($this->getName()) . DIRECTORY_SEPARATOR . $resource;
    }
}


