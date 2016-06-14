<?php namespace Applications;
use Backend;
use BackendAuth;
use Yaml;
use File;
use stdClass;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use October\Rain\Database\Model;
class ApplicationBase {
    protected $name = 'n_a';
    protected $version = '1.0.0';
    protected $scripts = [];
    protected $css = [];
    protected $controller;
    protected $permissions = [];
    protected static $widgets = null;
    protected $widgetsinstances = [];
    protected $formFields = [];
    protected $model = null;

    public function bindToController($controller) {
        $this->controller = $controller;
    }

    protected function setVersion($version='1.0.0') {
        $this->version = $version;
    }

    public function getModel() {
        return $this->model;
    }

    public function setModel($model = null) {
        $this->model = $model;
    }

    /**
     * Gets the application version
     * @return version
     */
    public function getVersion() {
        return $this->version;
    }

    public function getApplicationID() {
        $appID = preg_replace('/[^a-z]+/i','',$this->name);
        $appID = preg_replace('/(.)([A-Z])/','$1-$2',$appID);
        $appID = strtolower($appID);
        return $appID;

    }
    /**
     * Returns an array() of the scripts this application uses
     */
    public final function getScripts() {
        $ret = [];
        foreach($this->scripts as $value) {
            $ret[] = $this->getUrl('js/'.$value);
        }
        return $ret;
    }

    /**
     * Returns the url to the resource relative tot he applications directory
     */
    public function getUrl($url='') {
        $basepath = dirname(__FILE__).DIRECTORY_SEPARATOR;
        $basepath = str_replace('\\','/',substr($basepath,strpos($basepath,'plugins')-1));
        return $basepath.strtolower($this->getName()).'/'.$url;
    }

    /**
     * Returns the path to the given resource, relative from the applications root path
     * @param string $resource
     */
    public function getPath($resource='') {
        return __DIR__ . DIRECTORY_SEPARATOR . strtolower($this->getName()) . DIRECTORY_SEPARATOR . $resource;
    }

    /**
     * Returns an array() of the css files this application uses
     */
    public final function getCSS() {
        $ret = [];

        foreach($this->css as $value) {
            $ret[] = $this->getUrl('css/'.$value);
        }
        return $ret;
    }

    /**
     * Returns application name
     */
    public function getName() {
        return $this->name;
    }

    public function getWidget($name,$vars) {
        $path =__DIR__ . DIRECTORY_SEPARATOR.'widgets/'.$name.'/'.$name;
        return $this->renderfile($path, $vars);
    }
    /**
     * Renders a partial in the view directory
     * @param unknown $path
     * @param unknown $vars
     */
    public function getPartial($path, $vars) {
        $filepath = $this->getPath('view/'. $path);
        return $this->renderfile($filepath,$vars);

    }

    protected function checkWidgets() {
        if(is_null(self::$widgets)) {
            self::$widgets = Yaml::parse(file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'widgets'.DIRECTORY_SEPARATOR.'widgets.yaml'));
        }
    }

    protected function widgetTypeExists($name) {
        return array_key_exists($name, self::$widgets);
    }

    protected function getWidgetInstance($name,$config) {
        if($this->widgetTypeExists($name)) {
            $widget = self::$widgets[$name];
            return new $widget($this,$config);
        }
        return null;
    }

    public function renderForm($configfile) {
        $this->checkWidgets();

        $config = $this->getConfig($configfile);

        $ret  = '';
        $this->formFields = [];
        echo dump($config);
        foreach($config->fields as $fieldname => $widgetconfig) {
            if(isset($widgetconfig->type) && $this->widgetTypeExists($widgetconfig->type)) {
                $type = $widgetconfig->type;
            }
            else {
                $type = $widgetconfig->type = 'formfield';
            }
            $widget = $this->getWidgetInstance($type,$widgetconfig);
            $widget->setFieldName($fieldname);
            $this->widgetsinstances[$fieldname] = $widget;
            $this->formFields[$fieldname] = $widget;
        }
        foreach($this->formFields as $field) {
            $ret .= $field->render();
        }
        return $ret;
    }

    public function getFormField($name) {

        if(array_key_exists($name,$this->formFields)) {
            return $this->formFields[$name];
        }
        return null;

    }
    /**
     * Returns active registered widget by name;
     * @param unknown $name
     */
    public function getActiveWidget($name) {
        if(array_key_exists($name,$this->widgetsinstances)) {
            return $this->widgetsinstances[$name];
        }
        return null;
    }
    public function getConfig($config) {
        return $this->makeConfigFromArray(Yaml::parse(file_get_contents($this->getPath('config'.DIRECTORY_SEPARATOR.$config.'.yaml'))));
    }
    /**
     * Makes a config object from an array, making the first level keys properties a new object.
     * Property values are converted to camelCase and are not set if one already exists.
     * @param array $configArray Config array.
     * @param boolean $strict To return an empty object if $configArray is null
     * @return stdObject The config object
     */
    public function makeConfigFromArray($configArray = [],$strict = true)
    {
        $object = new stdClass();

        if (!is_array($configArray)) {
            if(!$strict && !is_null($configArray)) {
                return $configArray;
            }
            return $object;
        }

        foreach ($configArray as $name => $value) {
            if(is_array($value)) {
                $makeobject = true;
                foreach($value as $key => $val) {
                    if(is_numeric(substr($key,0,1))) {
                        $makeobject = false;
                    }
                    if(is_array($val)) {
                        $value[$key] = $this->makeConfigFromArray($val,false);
                    }
                }
                if($makeobject) {
                    $object->{$name} = $this->makeConfigFromArray($value,false);
                }
                else {
                    $object->{$name} = $value;
                }

            }
            else {
                $object->{$name} = $value;
            }
        }

        return $object;
    }

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
     * Renders the application partial as requested. Default is just app.html.
     * @param string $file The file to render
     * @param array $vars Variables to pass along as values to the html file for rendering
     */
    public function render($file='app',$vars=[]) {
        $render = true;
        foreach($this->permissions as $value) {
            if(!(BackendAuth::getUser()->hasAccess($value))) {
                $render = false;
                break;
            }
        }
        $initial =[
                'controller'=>$this->controller,
                'name'=>$this->getName(),
        ];
        $vars = array_merge($initial,$vars);

        return $render ? $this->getPartial($file,$vars):'';
    }

}