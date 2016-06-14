## October Metro Applications ##

This should make it easy to implement applications into your octoberCMS installation.

In your controller where you wish to integrate applications have a view(for example index.html)
where you have the following code

    <?php echo $this->renderApps();?>

In the controller itself add the following *use* statement

    use Applications\ApplicationController;
	
and have the extend the applicationcontroller

    class UserApplications extends ApplicationController
	
Then in the controller register your applications you wish to display

    public function __construct()
        {
            parent::__construct();
            $this->registerApplication('UserSettingsApplication');
            $this->registerApplication('FooBar');
            $this->loadApplicationAssets();
            BackendMenu::setContext('ExitControl.Desktop', 'desktop', 'userapplications');
        }
		
To create a new application:

    $> php appCreate create:application FooBar
	
in the folder where appCreate resides.

Then the application will be added in the applications folder where you can edit it and add the functionality you wish.


