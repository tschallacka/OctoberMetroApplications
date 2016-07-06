<?php namespace Applications;
use Backend;
use BackendAuth;
use Yaml;
use File;
use stdClass;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use October\Rain\Database\Model;
use Backend\Widgets\Form;
use October\Rain\Exception\ApplicationException;
use Session;

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
    private $applicationID=null;

    public function bindToController($controller) {
        $this->controller = $controller;
        $this->init();
    }
    /**
     * This gets called after the application is boudn to the controller.
     */
    protected function init() {

    }
    protected function listRender($listname) {
        $this->controller->makeLists();
        return $this->controller->listRender($listname);
    }
    protected function addListConfiguration($listname) {
        if(property_exists($this->controller,'listConfig')) {
            if(!is_array($this->controller->listConfig)) {
                $old = $this->controller->listConfig;
                $this->controller->listConfig = [$old];
            }
            $this->controller->listConfig[$listname] = str_replace('/',DIRECTORY_SEPARATOR,'../../vendor/applications/'.strtolower($this->getName()).'/config/'.$listname.'.yaml');
        }
    }

    protected function setVersion($version='1.0.0') {
        $this->version = $version;
    }
    /**
     * Geta associated Model
     * @return Model
     */
    public function getModel() {
        return $this->model;
    }
    /**
     * Set model to be used with application instance
     * @param Model $model
     */
    public function setModel(Model $model = null) {
        $this->model = $model;
    }

    /**
     * Gets the application version
     * @return version
     */
    public function getVersion() {
        return $this->version;
    }

    private function generateApplicationID() {
        $appID = preg_replace('/[^a-z]+/i','',$this->name);
        $appID = preg_replace('/(.)([A-Z])/','$1-$2',$appID);
        $appID = strtolower($appID);
        $this->applicationID = $appID;
    }

    public function getApplicationID() {
        if(is_null($this->applicationID)) {
            $this->generateApplicationID();
        }
        return $this->applicationID;

    }

    public function getId($key) {
        return $this->getApplicationID().'-'.$key;
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

        $filepath = __DIR__ . DIRECTORY_SEPARATOR . strtolower($this->getName()) . DIRECTORY_SEPARATOR . $resource;

        if(!file_exists($filepath)) {

            $validexts = ['.html','.htm','.php','.txt'];

            foreach($validexts as $extension) {
                if(file_exists($filepath.$extension)) {
                    $filepath .= $extension;
                    return $filepath;
                }
            }

            /**
             * Arrived here, not found, try backup in "base" directory
             * @var basepath to base directory
             */
            $filepath = __DIR__ . DIRECTORY_SEPARATOR . 'base' . DIRECTORY_SEPARATOR . $resource;

            if(!file_exists($filepath)) {
                foreach($validexts as $extension) {
                    if(file_exists($filepath.$extension)) {
                        $filepath .= $extension;
                        return $filepath;
                    }
                }

                /** arrived here, not found, throw exception **/
                throw new FileNotFoundException('File does not exist '.$filepath);
            }

            else {
                return $filepath;
            }
        }
        else {
            return $filepath;
        }

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

        $ret = '';
        $this->formFields = [];
        //echo dump($config);
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

        $this->vars = $vars;
        extract($vars);
        ob_start();
        include $filepath;
        $renderedView = ob_get_clean();
        return $renderedView;
    }

    /**
     * Renders a popup model that can be returned.
     * @param unknown $title The title of the popup modal
     * @param unknown $content The content to display.
     */
    public function renderPopup($title,$content) {
        return $this->render('___data-popup.html',[
            'contents' => $content,
            'title' => $title,
        ]);
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

    /**
     * Renders a list given the list configuration.
     * Make sure to define a $listConfig property in your application when using this
     * @param string $listName
     * @throws ApplicationException
     */
    protected function renderList($listName,$withWrap = true) {
        $list = $this->getList($listName);

        return  ($withWrap ? $this->render('___list_header.html',$list):'').
                $this->render('___list.html',$list).
                ($withWrap ? $this->render('___list_footer.html',$list):'');
    }
    /**
     * Retrieve application sensitive session variable.
     * @param string $key to to store it under
     * @param object $default the value to store
     */
    protected function getSession($key,$default=null) {
        return Session::get($this->getApplicationID().$key,$default);
    }

    /**
     * Set the session variable in the application
     * Don't forget to call Session::save() when
     * done putting in variables.
     * @param string $key The key to save under
     * @param object $data The data to store
     */
    protected function setSession($key,$data) {
        Session::put($this->getApplicationID().$key,$data);
    }
    /**
     * Default list config is empty
     *
     * Example config:
     * <pre>
     &nbsp;newsletters:
     &nbsp;           columnsSelectClosure:myAppCallback
     &nbsp;           model: ExitControl\Communication\Models\Newsletter
     &nbsp;           maxToDisplayPerPage : 10
     &nbsp;           destinationLink : exitcontrol/communication/newsletter/update/
     &nbsp;           columsToList:
     &nbsp;                  name:
     &nbsp;                     label: Newsletter name
     &nbsp;                     searchable: true
     &nbsp;                  members:
     &nbsp;                     label: Number of members
     &nbsp;                     searchable: false
     &nbsp;
     &nbsp;           defaultColumnToSort: name
     * </pre>
     *  Important values to add:
     *  <ul>
     *      <li> <strong>columnsSelectClosure</strong>: optional. if string it will try to call this method in
     *                                                   application first, if not found, then in model.
     *                                                   can be empty, if so will use columnsToList instead.</li>
     *      <li> <strong>Model</strong>: The base for the list</li>
     *      <li> <strong>maxToDisplayPerPage</strong>: int, how many to display per page</li>
     *      <li> <strong>destinationLink</strong>: link where each item should point to</li>
     *      <li> <strong>columnsToList</strong>: Array with key value pair. Key should be model field, value the label to display</li>
     *      <li> <strong>DefaultColumnToSort</strong>: The default column that should be sorted. </li>
     *  </ul>
     *
     * @var array
     */
    protected $listConfig = [
    ];
    /**
    * My code sample
    * <pre>
    &nbsp; $this->someConfig['settingname'] = [
        &nbsp;                                   'model' => 'foo\bar\baz',
        &nbsp;                                   'columns' => ['bla','blo','bleh'],
        &nbsp;                                   'sortorder'=> 'asc',
        &nbsp;                                   'defaultsort' => 'bla',
        &nbsp;                                   ];
    * </pre>
    * bla bla bla
    */
    public $someConfig = [];


    /**
     * Provide a Class to use as basis for the list.
     * In the closure define which fields to Select.
     * @param unknown $class The class to select
     * @param unknown $closure
     */
    protected function getList($listName) {
        $config = $this->listConfig->{$listName};
        $closure = (property_exists($config,'columnsSelectClosure')  ? $config->columnsSelectClosure : null);

        $default = $config->columsToList;

        if(is_null($closure)) {
            $closure = function($query) use ($default){
                $arr = [];
                foreach($default as $key => $value) {
                    $arr[] = $key;
                }
                $query->select($arr);
            };
        }

        $vars =[
            'searchValue' => getListSearchValue($listName),

            'orderColumn' => $this->getSession($listName.'orderColumn',$config->defaultColumnToSort),

            'sortDirection' => $this->getSession($listName.'sortDirection','asc'),

            'page' => $this->getSession($listName.'pageNumber',0),

            'limitPerPage' => $this->getSession($listName.'limitPerPage',$config->maxToDisplayPerPage),

            'columsToList' => $config->columsToList,

            'destinationLink' => (property_exists($config, 'destinationLink') ? $config->destinationLink:''),

            'listName' => $listName,
        ];
        $model = $config->model;


        extract($vars);

        $query = $model::with([]);
        if(is_string($closure)) {
            if(is_callable([$this,$closure])) {
                $this->$closure($query);
            }
            else {
                if(is_callable([$model,$closure])) {
                    $model->$closure($query);
                }
                else {
                    throw new ApplicationException("Method $closure not found in ".$this->getName(). " or in $model");
                }
            }
        }
        else {
            $closure($query);
        }

        if(!empty($searchValue)) {
            $searchArr = [];
            foreach($default as $key => $properties) {
                if(property_exists($properties,'searchable') && $properties->searchable) {
                    $query->orWhere($key,'like',"%$searchValue%");
                }
            }

        }
        $vars['totalCount'] = $query->count();
        $this->setSession($listName.'totalCount',$vars['totalCount']);

        $query = $query->orderBy($orderColumn,$sortDirection)
        ->take($limitPerPage)
        ->skip($page * $limitPerPage);

        $vars['list'] = $query->get();

        /**
         * Little gotcha catcher. if result count suddenly is zero, but we are not on the
         * first page, try to load the first page.
         */
        if($vars['list']->count() == 0 && $page > 0) {
            $this->setSession($listName.'pageNumber',0);
            $foobar = $this->getList();
            $vars['list'] = $foobar['list'];
        }
        Session::save();
        $vars['start'] = ($page * $limitPerPage) + 1;
        $vars['to'] = $vars['list']->count() + $vars['start'] - 1;

        return $vars;
    }
    /**
     * Ajax handler for sorting of lists
     * @param unknown $data
     * @return string[]
     */
    public function onSort($data) {
        $listName = $data['listName'];
        $config = $this->listConfig->{$listName};

        $column = $this->getSession($listName.'orderColumn', 'name');
        $direction = $this->getSession($listName.'sortDirection', 'asc');
        if($column == $data['column']) {
            $this->setSession($listName.'sortDirection',$direction == 'asc' ? 'desc' : 'asc');
        }
        else {
            $this->setSession($listName.'sortDirection', 'asc');
            $this->setSession($listName.'orderColumn', $data['column']);
        }
        Session::save();

        $contents = $this->renderList($listName,false);
        return ['#'.$this->getId('list-container') => $contents];
    }
    /**
     * Ajax handler for paging through lists
     * @param unknown $data
     */
    public function onPaginate($data) {
        $listName = $data['listName'];
        $config = $this->listConfig->{$listName};
        $model = $config->model;

        $max = $this->getSession($listName.'totalCount',null);
        if(is_null($max)) {
            $max = $model::count();
        }
        $default = $config->columsToList;

        if(ceil($max / $config->maxToDisplayPerPage) > $data['page'] && $data['page'] > -1) {
            $this->setSession($listName.'pageNumber',$data['page']);
            Session::save();
        }

        $contents = $this->renderList($listName,false);
        return ['#'.$this->getId('list-container') => $contents];
    }
    /**
     * Returns the current search value for given list
     * @param string $listName
     */
    public function getListSearchValue($listName,$default='') {
        return $this->getSession($listName.'searchValue',$default);
    }
    /**
     * Changes the current search value for given list
     * @param string $listName The name of the list
     * @param string $value The search key
     */
    public function setListSearchValue($listName,$value) {
        $this->setSession($listName.'searchValue',$value);
        Session::save();
    }

    public function onListSearch($data) {
        $listName = $data['listName'];
        $searchValue = trim($data['searchValue']);

        $this->setListSearchValue($listName, $searchValue);

        $contents = $this->renderList($listName,false);
        return ['#'.$this->getId('list-container') => $contents];
    }
}