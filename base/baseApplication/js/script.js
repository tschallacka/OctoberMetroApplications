+function ($) { "use strict";

	/**
	 * Set here your default application name.
	 */
	var appName = 'BaseApplication';
		
	/**
	 * Needs to be here, do not edit. Some default calculations to make your life easier.s
	 */
	var appID = appName.replace(/[^a-z]+/gi, '').replace(/(.)([A-Z])/g, "$1-$2").toLowerCase();var appDataHandler = '[data-'+appID+']';	var oc = 'oc.'+appName; var Base = $.oc.foundation.base, BaseProto = Base.prototype; var Application = function (element, options) { this.$el = $(element); this.options = options || {}; this.appID = appID; this.appName = appName; this.oc = oc; $.oc.foundation.controlUtils.markDisposable(element); Base.call(this); this.sysInit(); }; Application.prototype = Object.create(BaseProto); Application.prototype.constructor = Application;
    
	/**
     * ================================================================================================================
     *            ****                       edit below this line                             ****
     * ================================================================================================================
     */
	
	
    Application.prototype.handlers = function(type) {
    	this.$el[type]('click',this.proxy(this.onClick));
    };
    
    
    Application.prototype.onClick = function() {
    	console.log(appName + " has been clicked");
    };
    
    /**
     * Close the current modal
     */
    Application.prototype.closeModal = function() {
    	if(this.isModal()) {
    		this.$el.trigger('close.oc.popup');
    	}
    }
    /**
     * Close modals with specific name
     */
    Application.prototype.closeModalByName = function(name) {
    	$('[data-app-modal="'+name+'"').trigger('close.oc.popup');
    }
    /**
     * Simple test to determine if you are working in a modal caused by the app or not.
     * @returns {Boolean} true if is modal, false if not in modal
     */
    Application.prototype.isModal = function() {
    	return !(this.modalEquals(null));
    }
    /**
     * Fetch the name of the modal
     * @returns {String} the name of the modal, null of not in modal 
     */
    Application.prototype.getModalName = function() {
    	return this.$el.data('app-modal');
    }
    /**
     * Test if given name equals name of current modal
     * @param modalname The name of modal to test
     * @returns {Boolean} if it equals, or not
     */
    Application.prototype.modalEquals = function(modalname) {
    	return this.getModalName() == modalname;
    }
    /**
     * Returns a data handler for a sub button in a modal
     * @param handler the name of the handler
     * @returns {String} data-app-id-handler
     */
    Application.prototype.dataHandler = function(handler) {
    	return 'data-'+appId+'-'+handler;
    } 
    /**
     * ================================================================================================================
     *            ****                       Do not edit below this line                             ****
     * ================================================================================================================
     */
    
    Application.prototype.sysDestroy = function() {
    	this.handlers('off');
    	this.$el.off('dispose-control', this.proxy(this.dispose))
        this.$el.removeData(this.oc);
    	
        this.$el = null

        // In some cases options could contain callbacks, 
        // so it's better to clean them up too.
        this.options = null

        BaseProto.dispose.call(this)
    };
    
    Application.prototype.request = function(requestname,data) {
    	return this.$el.request(this.$el.data('apphandler') + requestname,data);
    }
    
    Application.prototype.sysInit = function() {
    	this.$el.one('dispose-control', this.proxy(this.sysDestroy));
    	this.handlers('on');
    }
    Application.DEFAULTS = {
        appName: appName,
        appID: appID,
        appDataHandler: appDataHandler
    }

    // PLUGIN DEFINITION
    // ============================

    var old = $.fn[appName]

    $.fn[appName] = function (option) {
        var args = Array.prototype.slice.call(arguments, 1), items, result
        
        items = this.each(function () {
            var $this   = $(this);
            var data    = $this.data(oc);
            var options = $.extend({}, Application.DEFAULTS, $this.data(), typeof option == 'object' && option);
            
            if (!data) {
            	$this.data(oc, (data = new Application(this, options)));
            }
            if (typeof option == 'string') {
            	result = data[option].apply(data, args);
            	
            }
            
            if (typeof result != 'undefined') {
            	return false;
            }
            ;
        });

        return result ? result : items
    };

    $.fn[appName].Constructor = Application;

    $.fn[appName].noConflict = function () {
        $.fn[appName] = old
        return this
    };

    // Add this only if required
    $(document).render(function (){
    	var $elems = $(appDataHandler);
    	$elems[appName]();
    	
    });

}(window.jQuery);