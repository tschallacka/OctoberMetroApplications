<?php namespace NamespaceLocation\Applications\BaseApplication;
use Applications\ApplicationBase;


class BaseApplication extends ApplicationBase {
    protected $name = 'BaseApplication';
    protected $css = ['style.css'];
    protected $scripts = ['script.js'];
    protected $permissions = [];


}