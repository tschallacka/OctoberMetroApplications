<?php namespace Tschallacka\OctoberMetroApplications\Applications;

use BackendAuth;
use Yaml;
use stdClass;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use October\Rain\Database\Model;
use October\Rain\Exception\ApplicationException;
use Session;
use ReflectionClass;
use Backend\Classes\WidgetBase;
use Illuminate\Database\Query\Expression as Raw;


class ApplicationBase extends WidgetBase 
{
    protected $name = 'n_a';
    protected $version = '1.0.0';
    protected $scripts = [];
    protected $css = [];
    /**
     * @var \Tschallacka\OctoberMetroApplications\Applications\ApplicationController $controller
     */
    protected $controller;
    protected $permissions = [];
    protected static $widgets = null;
    protected $widgetsinstances = [];
    protected $formFields = [];
    protected $model = null;
    private $applicationID = null;
    private $baseurl = null;
    protected $applicationDir = null;
    protected $defaultAlias = 'n_a';

    use \Backend\Traits\FormModelSaver;
    
    public function __construct($controller, $config = []) 
    {
        if(!array_key_exists('alias', $config)) {
            $config['alias'] = $this->getName();
        }
        $this->defaultAlias = $this->getName();
        parent::__construct($controller, $config);
    }
    
    public function bindToController() 
    { 
        parent::bindToController();
    }
    
    public function preInit() 
    {
            
    }

    /**
     * returns the directory of the children where they are located.
     */
    protected function getDir() 
    {
        if(is_null($this->applicationDir)) {
            $reflector = new ReflectionClass(get_class($this));
            $this->applicationDir = dirname($reflector->getFileName());
        }
        return $this->applicationDir;
    }

    /**
     * Render a list via the controller list render
     * @param string $listname name of the list config in the controller
     */
    protected function listRender($listname) 
    {
        $this->controller->makeLists();
        return $this->controller->listRender($listname);
    }

    /**
     * Add a list configuration to the controller
     * @param string $listname Adds the list configuration to the Controller.
     *              A configuration.yaml with the name of the "$listname.yaml" needs to be placed
     *              In the applications config directory.
     */
    protected function addListConfiguration($listname) 
    {

        if(property_exists($this->controller,'listConfig')) {

            if(!is_array($this->controller->listConfig)) {

                $old = $this->controller->listConfig;
                $this->controller->listConfig = [$old];

            }

            $this->controller->listConfig[$listname] = $this->getPath('config/'.$listname.'.yaml');
        }

    }

    protected function createApplicationForm($config_file,$model,$context='create') 
    {
        
        $config = $this->getApplicationConfig($config_file,false);
        $config->model = $model;
        $config->arrayName = class_basename($model);
        
        $config->context = $context;
        $config->alias = studly_case($config->arrayName.'-'.$config_file);
        //$config->noInit = true;
        
        $widget = $this->controller->createApplicationForm($config); 
        
        return $widget;
         
    }

    /**
     * Set the application version
     * @param string $version
     */
    protected function setVersion($version='1.0.0') 
    {
        $this->version = $version;
    }

    /**
     * Geta associated Model
     * @return Model
     */
    public function getModel() 
    {
        return $this->model;
    }

    /**
     * Set model to be used with application instance
     * @param Model $model
     */
    public function setModel(Model $model = null) 
    {
        $this->model = $model;
    }

    /**
     * Gets the application version
     * @return string 
     */
    public function getVersion() 
    {
        return $this->version;
    }
    
    /**
     * Returns a javascript data hanlder 
     * @param string $name The name to add
     * @return string data-$appId-$name
     */
    public function getDataHandler($name='') 
    {
        return 'data-'.$this->getApplicationID().'-'.$name;
    }
    
    private function generateApplicationID() 
    {

        $appID = preg_replace('/[^a-z]+/i','',$this->name);
        $appID = preg_replace('/(.)([A-Z])/','$1-$2',$appID);
        $appID = strtolower($appID);
        $this->applicationID = $appID;

    }

    public function getApplicationID() 
    {
        if(is_null($this->applicationID)) {
            $this->generateApplicationID();
        }
        return $this->applicationID;

    }

    public function getId($key=null) 
    {
        return $this->getApplicationID().'-'.$key;
    }


    /**
     * Returns an array() of the scripts this application uses
     */
    public final function getScripts() 
    {
        $ret = [];
        foreach($this->scripts as $value) {
            $ret[] = $this->getUrl('assets/js/'.$value);
        }
        return $ret;
    }
    
    /**
     * Saves a from model from a form widget
     * @param Model $model
     * @param  $formWidget
     */
    public function saveFormModel($model, $formWidget) 
    {
        $modelsToSave = $this->prepareModelsToSave($model, $formWidget->getSaveData());
        foreach ($modelsToSave as $modelToSave) {
            $modelToSave->save(null, $formWidget->getSessionKey());
        }
        return true;
    }
    
    /**
     * Return the base url to the application directory.
     */
    public function getBaseUrl() 
    {
        if(is_null($this->baseurl)) {
            $basepath = $this->getDir() . DIRECTORY_SEPARATOR;
            /**
             * remove the root directory from the absolute path, to get
             * our url access point :-)
             */
            $basepath = str_replace(base_path(), '', $basepath);
            /**
             * Turn directory separators into forward slashes
             */
            $basepath = str_replace(DIRECTORY_SEPARATOR,'/',$basepath);
            $this->baseurl = $basepath;
        }
        return $this->baseurl;
    }

    /**
     * Returns the url to the resource relative tot the applications directory
     */
    public function getUrl($url='') 
    {
        return $this->getBaseUrl() . $url;
    }

    /**
     * Returns the path to the given resource, relative from the applications root path
     * @param string $resource
     */
    public function getPath($resource='') 
    {

        //$filepath = __DIR__ . DIRECTORY_SEPARATOR . strtolower($this->getName()) . DIRECTORY_SEPARATOR . $resource;
        $filepath = $this->getDir() . DIRECTORY_SEPARATOR . $resource;
        //traceLog($filepath);
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
    public final function getCSS() 
    {
        $ret = [];

        foreach($this->css as $value) {
            $ret[] = $this->getUrl('assets/css/'.$value);
        }
        return $ret;
    }

    /**
     * Returns application name
     */
    public function getName() 
    {
        return $this->name;
    }

    public function getWidget($name,$vars) 
    {
        $path =__DIR__ . DIRECTORY_SEPARATOR . 'widgets'. DIRECTORY_SEPARATOR . $name.DIRECTORY_SEPARATOR.$name;
        return $this->renderfile($path, $vars);
    }
    
    /**
     * Renders a partial in the view directory
     * @param string $path
     * @param array $vars
     */
    public function getPartial($path, $vars) 
    {
        $filepath = $this->getPath('view'. DIRECTORY_SEPARATOR . $path);
        return $this->renderfile($filepath,$vars);
    }

    public function getApplicationConfig($config,$asobjects = true) 
    {
        if($asobjects) {
            return $this->makeConfigFromArray(
                Yaml::parse(
                    file_get_contents(
                        $this->getPath('config' .DIRECTORY_SEPARATOR . $config . '.yaml'
                            )
                        )
                    )
            );
        }
        else {
            $object = new stdClass();
            
            $configArray = Yaml::parse(
                    file_get_contents(
                        $this->getPath('config' .DIRECTORY_SEPARATOR . $config . '.yaml'
                        )
                    )
                );
            foreach ($configArray as $name => $value) {
                $_name = camel_case($name);
                $object->{$name} = $object->{$_name} = $value;
            }
            
            return $object;
            
        }
    }
    

    /**
     * Makes a config object from an array, making the first level keys properties a new object.
     * Property values are converted to camelCase and are not set if one already exists.
     * @param array $configArray Config array.
     * @param boolean $strict To return an empty object if $configArray is null
     * @return \stdClass The config object
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

    private function renderfile($filepath, $vars) 
    {
        $this->vars = $vars;
        extract($vars);
        ob_start();
        include $filepath;
        $renderedView = ob_get_clean();
        return $renderedView;
    }

    /**
     * Renders a popup model that can be returned.
     * @param string $title The title of the popup modal
     * @param string $content The content to display.
     */
    public function renderPopup($title,$content,$appModalID='popup') 
    {
        return $this->render('___data-popup.html',[
            'contents' => $content,
            'title' => $title,
        	'appModalID' => $appModalID,
        ]);
    }
    
    /**
     * Renders the application partial as requested. Default is just app.html.
     * @param string $file The file to render
     * @param array $vars Variables to pass along as values to the html file for rendering
     */
    public function render($file='app', $vars=[]) 
    {
        $render = true;
        $count = 0;        
        
        if(!is_null(BackendAuth::getUser()) && !(BackendAuth::getUser()->hasAccess($this->permissions))) {
            $render = false;                
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
    protected function renderList($listName,$withWrap = true) 
    {
        $list = $this->getList($listName);

        return  ($withWrap ? $this->render('___list_header.html',$list):'').
                $this->render('___list.html',$list).
                ($withWrap ? $this->render('___list_footer.html',$list):'');
    }
    
    /**
     * Retrieve application sensitive session variable.
     * @param string $key to to store it under
     * @param object $default the value to store
     *
    protected function getSession($key=null,$default=null) {
        return Session::get($this->getApplicationID().$key,$default);
    }/

    /**
     * Set the session variable in the application
     * Don't forget to call Session::save() when
     * done putting in variables.
     * @param string $key The key to save under
     * @param object $data The data to store
     */
    protected function setSession($key,$data) 
    {
        parent::putSession($key,$data);
        //Session::put($this->getApplicationID().$key,$data);
    }
    /**
     * Default list config is empty
     *
     * Example config:
     * <pre>
     &nbsp;newsletters:
     &nbsp;           columnsSelectClosure:myAppCallback
     &nbsp;           model: Util\Communication\Models\Newsletter
     &nbsp;           maxToDisplayPerPage : 10
     &nbsp;           destinationLink : util/communication/newsletter/update/
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
     * @param string $class The class to select
     * @param callable $closure
     */
    protected function getList($listName) 
    {
        $config = $this->listConfig->{$listName};
        $closure = (property_exists($config,'columnsSelectClosure')  ? $config->columnsSelectClosure : null);
        $handleSelectInClosure = (property_exists($config,'handleSelectInClosure')  ? $config->columnsSelectClosure === 'true' || $config->columnsSelectClosure == true || $config->columnsSelectClosure == 1 : false);
        
        
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
            'searchQuery' => $this->getListSearchValue($listName),

            'orderColumn' => $this->getSession($listName.'orderColumn',$config->defaultColumnToSort),

            'sortDirection' => $this->getSession($listName.'sortDirection',(property_exists($config,'sortDirection') ? $config->sortDirection : 'asc')),

            'page' => $this->getSession($listName.'pageNumber',0),

            'limitPerPage' => $this->getSession($listName.'limitPerPage',$config->maxToDisplayPerPage),

            'columsToList' => $config->columsToList,
            'attributes' => (property_exists($config, 'attributes') ? $config->attributes:''),
            'destinationLink' => (property_exists($config, 'destinationLink') ? $config->destinationLink:''),
        	'onclick' => (property_exists($config, 'onclick') ? $config->onclick:''),
            'apprequest' => (property_exists($config, 'apprequest') ? $config->apprequest:''),
            'apppopup' => (property_exists($config, 'apppopup') ? $config->apppopup:''),
            'apprequestdata' => (property_exists($config, 'apprequestdata') ? $config->apprequestdata:''),
            'listName' => $listName,
        ];
        $model = $config->model;
		$model = new $model();

        extract($vars);

        $query = $model->newQuery();
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
		
        
        
        
        
        /**
         * Collect all relationships, preventing double joins etc..
         * @var unknown
         */
        $with = [];
        $withRelations = [];
        foreach($default as $key => $properties) {
        	if(property_exists($properties,'type') && $properties->type == 'relation') {
        		if(!in_array($properties->relationName, $with)) {
        			$with[]=$properties->relationName;
        		}
        	}
        }
        
        /**
         * Join only the belongsTo and the HasOne tables.
         */
        if(count($with)) {
	        foreach($with as $key) {
	        	$relation = $model->{$key}();
	        	$withRelations[$key] =  $relation;
	        	if($relation instanceof \October\Rain\Database\Relations\BelongsTo) {
	        		$query->leftJoin($relation->getRelated()->getTable() .' as '.$key,
	        				$relation->getParent()->getTable().'.'.$relation->getForeignKey(),'=',$key.'.'.$relation->getOtherKey());
	        	}
	        	if($relation instanceof \October\Rain\Database\Relations\HasOne) {
	        		$query->leftJoin($relation->getRelated()->getTable() . ' as '.$key,
	        				$relation->getQualifiedParentKeyName(),'=',$key.'.'.substr($relation->getForeignKey(),strpos($relation->getForeignKey(),'.')+1));
	        	}
	        }
	        
	        $query->with($with);
        }
        $select = [];
        
        if(!empty($searchQuery)) {
        	$arr = explode('|',$searchQuery);
        	$query->where(function($query) use($arr, $default, $model){
            	foreach($arr as $searchValue) {
            		foreach($default as $key => $properties) {
            			if(property_exists($properties, 'searchable') && $properties->searchable == 'true') {
            				if(property_exists($properties,'type') && $properties->type == 'relation') {
            					$query->orWhere($properties->relationName.'.'.$key,'like',"%$searchValue%");
            				}
            				else {
            					$query->orWhere($model->getTable().'.'.$key,'like',"%$searchValue%");
            				}
            			}
            		}
            	}
        	});
        	
        }
        $vars['totalCount'] = $query->count();
        $this->setSession($listName.'totalCount',$vars['totalCount']);
        foreach($default as $key => $properties) {
        	
        	if(property_exists($properties,'type') && $properties->type == 'relation') {
        	    if(property_exists($properties, 'valueFrom')) {
            		if($key == $orderColumn) {
            			$orderColumn = $properties->relationName.'.'.$properties->valueFrom;
            		}
            		$select[] = $properties->relationName.'.'.$properties->valueFrom.' as '.$key;
        	    }
    	        if(property_exists($properties, 'valueFromSelect')) {
    	            if($key == $orderColumn) {
    	                $order = new Raw($properties->valueFromSelect);
    	            }
    	            $select[] = $properties->valueFromSelect.' as '.$key;
    	        }
        	    
        	}
        	else {
        		if($key == $orderColumn) {
        			$orderColumn = $model->getTable().'.'.$key;
        		}
        		$select[] = $model->getTable().'.'.$key.' as '.$key;
        	}
        }
        /**
         * If the closure doesn't handle the selects
         */
        if(!$handleSelectInClosure) {            
            /**
             * Test if the primary key has been selected yet, by primary key name AND by fully qualyfied name
             */
            //traceLog($select);
            if(!in_array($model->getKeyName(), $select) 
                && 
               !in_array($model->getTable().'.'.$model->getKeyName(),$select)
               && !in_array($model->getTable().'.'.$model->getKeyName() . ' as '.$model->getKeyName(),$select)) {
                /**
                 * add it to select list as fully qualified name.
                 */
                $select[] = $model->getTable().'.'.$model->getKeyName();
                
            }
            $query->select(new Raw(implode(',',$select)));
        }
		//throw new \Exception(implode(', ',$select)); 
        $query = $query->orderBy($orderColumn,$sortDirection); 	
		
        
        $query->take($limitPerPage)
        		->skip($page * $limitPerPage);
        
        //throw new \Exception($query->toSql());
        $vars['list'] = $query->get();
		//throw new \Exception($query->toSql());
        /**
         * Little gotcha catcher. if result count suddenly is zero, but we are not on the
         * first page, try to load the first page.
         */
        if($vars['list']->count() == 0 && $page > 0) {
            $this->setSession($listName.'pageNumber',0);
            $foobar = $this->getList($listName);
            $vars['list'] = $foobar['list'];
        }
        
        Session::save();
        
        $vars['start'] = ($page * $limitPerPage) + 1;
        $vars['to'] = $vars['list']->count() + $vars['start'] - 1;
		$vars['searchValue'] = $searchQuery;
        return $vars;
    }
    /**
     * Ajax handler for sorting of lists
     * @param array $data
     * @return string[]
     */
    public function onSort($data) 
    {
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
     * @param array $data
     */
    public function onPaginate($data) 
    {
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
    public function getListSearchValue($listName,$default='') 
    {
        return $this->getSession($listName.'searchValue',$default);
    }
    
    /**
     * Changes the current search value for given list
     * @param string $listName The name of the list
     * @param string $value The search key
     */
    public function setListSearchValue($listName,$value) 
    {
        $this->setSession($listName.'searchValue',$value);
        Session::save();
    }

    public function onListSearch($data) 
    {
        $listName = $data['listName'];
        $searchValue = trim($data['searchValue']);

        $this->setListSearchValue($listName, $searchValue);

        $contents = $this->renderList($listName,false);
        return ['#'.$this->getId('list-container') => $contents];
    }
}