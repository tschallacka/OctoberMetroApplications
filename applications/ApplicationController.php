<?php namespace Tschallacka\OctoberMetroApplications\Applications;

use October\Rain\Exception\ApplicationException;
use ReflectionClass;
use Backend\Classes\ControllerBehavior;


/**
 * User Applications Back-end Controller
 */
class ApplicationController extends ControllerBehavior
{
    protected $applications = [];
    public function __construct($controller)
    {
        parent::__construct($controller);
        $this->loadAssets();
    }
    
    private function getAppContainer($app) 
    {
        return '<div class="app-container ' . $app->getApplicationID() . '">'.
                $app->render().
                '</div>';
    }
    
    private function addApplication($appClass, $name) 
    {
        $app = new $appClass($this->controller);
        if($app instanceof ApplicationBase) {
            $this->applications[$app->getApplicationID()] = $app;
            $app->bindToController();
            $app->preInit();
            return $app->getApplicationID();
        }
    }
    
    public function getApplication($appId) 
    {
        if(array_key_exists($appId, $this->applications)) {
            return $this->applications[$appId];
        }
        return null;
    }
    
    public function renderApp($appId) 
    {
        if($app = $this->getApplication($appId)) {
            return $this->getAppContainer($app);
        }
        return '';
    }
    
    public function getRegisteredAppsList() 
    {
        return collect(array_keys($this->applications));
    }
    
    public function registerApplication($appName, $name=null) 
    {
        if(is_null($name)) {
            $name = $appName;
        }
        if(class_exists($appName)) {
            return $this->addApplication($appName,$name);
        }
        else {
            $appSpace = 'Applications';
            $app = implode('\\',['', $appSpace,$appName,$appName]);
            if(class_exists($app)) {
                return $this->addApplication($app,$name);
            }
            else {
                $reflector = new ReflectionClass(get_class($this->controller)); // class Foo of namespace A
                $namespace = $reflector->getNamespaceName();
                $pluginspace = substr($namespace,0,strpos($namespace,'\\Controller') );
                $app = implode('\\',['',$pluginspace,$appSpace,$appName,$appName]);
                if(class_exists($app)) {
                    return $this->addApplication($app,$name);
                }
                else {
                    throw new ApplicationException('Cannot find application '.$appName . '
                              Please consider using full namespace to register the application');
                }
            }
        }
    }

    public function renderApps() 
    {
        $this->loadAssets();
        $str = '';
        foreach($this->applications as $app) {
            $str .= $this->getAppContainer($app);

        }

        return $str;
    }
    /**
     * Creates a form and binds it to the controller
     * @param $config
     * @return \Backend\Widgets\Form
     */
    public function createApplicationForm($config) 
    {
        
        /*
         * Form Widget with extensibility
         */
        $formWidget = $this->makeWidget('Backend\Widgets\Form', $config);
        
        
        $formWidget->bindToController();
        /*
         * Detected Relation controller behavior
         */
        
        return $formWidget;
    }

    public function loadApplicationAssets() 
    {
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
    public function getUrlBase($url='') 
    {
        $basepath = __DIR__.DIRECTORY_SEPARATOR;
        $basepath = str_replace('\\','/',substr($basepath,strpos($basepath,'plugins')-1));
        return $basepath.$url;
    }

    private function loadAssets()
    {
        $this->addCss($this->getUrlBase('assets/css/metro.css'), 'Util.Desktop');
        $this->addCss($this->getUrlBase('assets/css/metro-icons.css'), 'Util.Desktop');
        $this->addCss($this->getUrlBase('assets/css/application.css'), 'Util.Desktop');

        $this->addJs($this->getUrlBase('assets/js/metro.js'), 'Util.Desktop');
        $this->addJs($this->getUrlBase('assets/js/application.js'), 'Util.Desktop');
        $this->addJs($this->getUrlBase('assets/js/listsearchelement.js'), 'Util.Desktop');
        $this->addJs($this->getUrlBase('assets/js/linkopenclick.js'), 'Util.Desktop');
        $this->addJs($this->getUrlBase('assets/js/apppopup.js'), 'Util.Desktop');
        $this->addJs($this->getUrlBase('assets/js/draggable.js'), 'Util.Desktop');
        $this->addJs($this->getUrlBase('assets/js/panelopenclick.js'), 'Util.Desktop');

    }

    public function onAppRequest() 
    {
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