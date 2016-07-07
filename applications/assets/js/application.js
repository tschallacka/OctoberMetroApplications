+function ($) { "use strict";
	var appName = 'MasterApplicationControl';
		
	/**
	 * Needs to be here, do not edit
	 */
	var appID = appName.replace(/[^a-z]+/gi, '').replace(/(.)([A-Z])/g, "$1-$2").toLowerCase();var appDataHandler = '[data-apprequest]';	var oc = 'oc.'+appName; var Base = $.oc.foundation.base, BaseProto = Base.prototype; var Application = function (element, options) { this.$el = $(element); this.options = options || {}; this.appID = appID; this.appName = appName; this.oc = oc; $.oc.foundation.controlUtils.markDisposable(element); Base.call(this); this.sysInit(); }; Application.prototype = Object.create(BaseProto); Application.prototype.constructor = Application;
    
    Application.prototype.handlers = function(type) {
    	
    	this.$el[type]('click',this.proxy(this.onClick));
    };
    
    
    
    /**
     * Application JS is bound to any element that has [data-apprequest] defined in it's tag.
     * Make sure that element is a child of a tag containin data-appid so the ajax request
     * will go to the correct element
     * @param e
     */
    
    Application.prototype.onClick = function(e) {
    	/**
    	 * Find the closest originating event caster(in case event originated from child element
    	 */
    	var $this = $(e.target).closest('[data-apprequest]');
    	
    	/**
    	 * Prepare data object
    	 */
    	var data = {};
    	
    	/**
    	 * Pass application ID so right application can handle the call
    	 */
    	var appid = $this.closest('[data-appid]').data('appid');
    	/**
    	 * The request function within the application
    	 */
    	var request = $this.data('apprequest');
    	
    	/**
    	 * Test for existance of raw data
    	 */
    	var rawdata = $this.data('apprequest-data');
    	var data = {};
    	if(rawdata) {
    		eval('data={'+rawdata+'}');
    	}
    	
    	var success = $this.data('apprequest-success');
    	if(success) {
    		eval('data.success = function(data,status,xhr){'+success+'}');
    	}
    	else {
    	    data.success = function(data) {console.log(data);};
    	}
    	
    	var update = $this.data('apprequest-update');
    	if(update) {
    		eval('data.update = {'+update+'}');
    	}
    	
    	var confirm = $this.data('apprequest-confirm');
    	if(confirm) {
    		data.confirm = confirm;
    	}
    	
    	var redirect = $this.data('apprequest-redirect');
    	if(redirect) {
    		data.redirect = redirect;
    	}
    	
    	var beforeUpdate = $this.data('apprequest-beforeUpdate');
    	if(beforeUpdate) {
    		eval('data.beforeUpdate = function(data,status,xhr){'+beforeUpdate+'}');
    	}
    	
    	var error = $this.data('apprequest-error');
    	if(error) {
    		eval('data.error = function(xhr,status,error){'+error+'}');
    	}
    	
    	var complete = $this.data('apprequest-complete');
    	if(complete) {
    		eval('data.complete = function(context,textStatus,xhr){'+complete+'}');
    	}
    	
    	/**
    	 * Send apprequest
    	 */
    	$this.appRequest(appid,request,data);
    	
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
    }
    
    Application.prototype.sysInit = function() {
    	this.$el.one('dispose-control', this.proxy(this.sysDestroy));
    	this.handlers('on');
    }
    Application.DEFAULTS = {
        someParam: null
    }

    // PLUGIN DEFINITION
    // ============================

    var old = $.fn[appName]
    $.fn.appRequest = $.appRequest =  function(appid,request,data) {
    	var sendobject = {
    		data:{
    			appid:appid,
    			request:request
    		}
    	};
    	var transposefuntions = ['success','update','confirm','redirect','beforeUpdate','error','complete'];
    	for(var c=0;c<transposefuntions.length;c++) {
    		if(data && data.hasOwnProperty(transposefuntions[c])) {
    			sendobject[transposefuntions[c]] = data[transposefuntions[c]];
    			delete data[transposefuntions[c]];
    		}
    	}
    	sendobject.data.data = data;
    	$.request('onAppRequest', sendobject);
    }
    
    /**
     * Open a popup model with an app :-)
     */
    $.fn.appPopup = $.appPopup = function(appid,request,data) {
    	
    	var sendobject = {
    	    handler:'onAppRequest',
    		extraData:{
    			appid:appid,
    			request:request,       
    		}
    	};
    	
    	sendobject.extraData.data = data;
    	
    	$.popup(sendobject); 
    	
    }
   
    
    $.getAppId = $.fn.getAppId = function() {
		return this.closest('[data-appid]').data('appid');
	}
    
    
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
        })

        return result ? result : items
    }

    $.fn[appName].Constructor = Application

    $.fn[appName].noConflict = function () {
        $.fn[appName] = old
        return this
    }

    // Add this only if required
    $(document).render(function (){
    	var $elems = $(appDataHandler);
    	$elems[appName]();
    	
    })

}(window.jQuery);