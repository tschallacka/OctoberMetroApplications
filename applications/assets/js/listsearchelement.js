+function ($) { "use strict";
	var appName = 'ListSearchBarHandler';
		
	/**
	 * Needs to be here, do not edit
	 */
	var appID = appName.replace(/[^a-z]+/gi, '').replace(/(.)([A-Z])/g, "$1-$2").toLowerCase();var appDataHandler = '[data-search-in-list-element]';	var oc = 'oc.'+appName; var Base = $.oc.foundation.base, BaseProto = Base.prototype; var Application = function (element, options) { this.$el = $(element); this.options = options || {}; this.appID = appID; this.appName = appName; this.oc = oc; $.oc.foundation.controlUtils.markDisposable(element); Base.call(this); this.sysInit(); }; Application.prototype = Object.create(BaseProto); Application.prototype.constructor = Application;
    
    Application.prototype.handlers = function(type) {
    	if(type == 'on') {
    		this.lastTimeout = 0;
    		this.timeoutsToClear = [];
    	}
    	else {
    		this.lastTimeout = null;
    		this.clearTimeouts();
    		this.timeoutsToClear = null;
    		
    	}
    	this.$el[type]('keydown',this.proxy(this.keyDown));
    };
    
    Application.prototype.clearTimeouts = function() {
    	var foo;
		while(foo = this.timeoutsToClear.pop()) {
			window.clearTimeout(foo);
		}
    }
    Application.prototype.sendSearch = function() {
    	var val = this.$el.val();
    	/**
    	 * Pass application ID so right application can handle the call
    	 */
    	var appid = this.$el.closest('[data-appid]').data('appid');
    	
    	var rawdata = this.$el.data('apprequest-data');
    	var data = {};
    	if(rawdata) {
    		eval('data={'+rawdata+'}');
    	}
    	data['searchValue'] = val;
    	data['complete'] = this.proxy(this.afterRefresh);
    	this.$el.appRequest(appid,'onListSearch',data);
    }
    Application.prototype.afterRefresh = function() {
    	this.$el.focus();
    }
    /**
     * Application JS is bound to any element that has [data-apprequest] defined in it's tag.
     * Make sure that element is a child of a tag containin data-appid so the ajax request
     * will go to the correct element
     * @param e
     */
    
    Application.prototype.keyDown = function(e) {
    	var currentTime = new Date().getTime();
    	if(this.timeoutsToClear.length > 1) {
    	    this.clearTimeouts();	
    	}
    	this.timeoutsToClear.push(window.setTimeout(this.proxy(this.sendSearch),500));
    	 
    	
    	
    	
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
    $.fn.appPopup = $.appPopup = function(appid,request,data) {
    	var sendobject = {
    	    handler:'onAppRequest',
    		extraData:{
    			appid:appid,
    			request:request
    		}
    	};
    	sendobject.extraData.data = data;
    	$.popup(sendobject);
    	
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