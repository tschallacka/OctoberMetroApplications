<?php namespace Applications;

use Backend\Classes\Controller;
use Applications\ApplicationBase;
use October\Rain\Exception\ApplicationException;
use ReflectionClass;


/**
 * User Applications Back-end Controller
 */
class ApplicationController extends Controller
{
    protected $applications = [];
    public function __construct()
    {
        parent::__construct();
        $this->loadAssets();

    }
    private function getAppContainer($app) {
        return '<div class="app-container '.$app->getApplicationID().'">'.
                $app->render().
                '</div>';
    }
    private function addApplication($appClass,$name) {
        $app = new $appClass($this);
        if($app instanceof ApplicationBase) {
            $app->alias = $name;
            $this->applications[$app->getApplicationID()] = $app;
            if(!property_exists($this, 'widget')) {

                dd($this);
            }
            $app->bindToController();
        }
    }
    public function registerApplication($appName,$name=null) {
        if(is_null($name)) {
            $name = $appName;
        }
        if(class_exists($appName)) {
            $this->addApplication($appName,$name);
        }
        else {
            $appSpace = 'Applications';
            $app = implode('\\',['', $appSpace,$appName,$appName]);
            if(class_exists($app)) {
                $this->addApplication($app,$name);
            }
            else {
                $reflector = new ReflectionClass(get_class($this)); // class Foo of namespace A
                $namespace = $reflector->getNamespaceName();
                $pluginspace = substr($namespace,0,strpos($namespace,'\\Controller') );
                $app = implode('\\',['',$pluginspace,$appSpace,$appName,$appName]);
                if(class_exists($app)) {
                    $this->addApplication($app,$name);
                }
                else {
                    throw new ApplicationException('Cannot find application '.$appName . '
                              Please consider using full namespace to register the application');
                }
            }
        }
    }

    public function renderApps() {
        $str = '';
        foreach($this->applications as $app) {
            $str .= $this->getAppContainer($app);

        }

        return $str;
    }

    public function createApplicationForm($config) {
        /*
         * Form Widget with extensibility
         */
        $this->formWidget = $this->makeWidget('Backend\Widgets\Form', $config);

        $this->formWidget->bindEvent('form.extendFieldsBefore', function () {
            $this->controller->formExtendFieldsBefore($this->formWidget);
        });

        $this->formWidget->bindEvent('form.extendFields', function ($fields) {
            $this->controller->formExtendFields($this->formWidget, $fields);
        });

        $this->formWidget->bindEvent('form.beforeRefresh', function ($saveData) {
            return $this->controller->formExtendRefreshData($this->formWidget, $saveData);
        });

        $this->formWidget->bindEvent('form.refreshFields', function ($fields) {
            return $this->controller->formExtendRefreshFields($this->formWidget, $fields);
        });

        $this->formWidget->bindEvent('form.refresh', function ($result) {
            return $this->controller->formExtendRefreshResults($this->formWidget, $result);
        });

        $this->formWidget->bindToController();
        /*
         * Detected Relation controller behavior
         */
        if ($this->controller->isClassExtendedWith('Backend.Behaviors.RelationController')) {
            $this->controller->initRelation($config->model);
        }
    }

    public function loadApplicationAssets() {
        foreach($this->applications as $app) {
            $css = $app->getCSS();
            foreach($css as $val) {
                $this->addCss($val,$app->getVersion());
            }
            $js = $app->getScripts();
            foreach($js as $val) {
                $this->addJs($val,$app->getVersion());
            }
        }
    }

    /**
     * Returns the url to the resource relative tot he applications directory
     */
    public function getUrlBase($url='') {
        $basepath = __DIR__.DIRECTORY_SEPARATOR;
        $basepath = str_replace('\\','/',substr($basepath,strpos($basepath,'plugins')-1));
        return $basepath.$url;
    }

    public function loadAssets()
    {
        $this->addCss($this->getUrlBase('assets/css/metro.css'), 'ExitControl.Desktop');
        $this->addCss($this->getUrlBase('assets/css/metro-icons.css'), 'ExitControl.Desktop');
        $this->addCss($this->getUrlBase('assets/css/application.css'), 'ExitControl.Desktop');

        $this->addJs($this->getUrlBase('assets/js/metro.js'), 'ExitControl.Desktop');
        $this->addJs($this->getUrlBase('assets/js/application.js'), 'ExitControl.Desktop');
        $this->addJs($this->getUrlBase('assets/js/listsearchelement.js'), 'ExitControl.Desktop');
        $this->addJs($this->getUrlBase('assets/js/linkopenclick.js'), 'ExitControl.Desktop');
        $this->addJs($this->getUrlBase('assets/js/apppopup.js'), 'ExitControl.Desktop');
        $this->addJs($this->getUrlBase('assets/js/draggable.js'), 'ExitControl.Desktop');

    }

    public function onAppRequest() {
        $appid = post('appid');

        if(isset($this->applications[$appid])) {

            $app = $this->applications[post('appid')];
            $function = post('request');
            if(strpos($function,'on') !== 0) {
                throw new ApplicationException('Ajax calls function names must start with on. function '.$function.'() does not conform.');
            }
            if(method_exists($app,$function)) {
                return $app->{$function}(post('data'));
            }
            else {
                throw new ApplicationException('Method '.$function . '() does not exist in '.$appid);
            }

        }

        else {
            throw new ApplicationException('Application '.$appid.' not found!');
        }

        return [];
    }
}