<?php namespace NamespaceLocation\Applications\BaseApplication;
use Tschallacka\OctoberMetroApplications\Applications\ApplicationBase;


class BaseApplication extends ApplicationBase 
{
    protected $name = 'BaseApplication';
    protected $css = ['style.css'];
    protected $scripts = ['script.js'];
    protected $permissions = [];


}