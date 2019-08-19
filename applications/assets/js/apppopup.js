+function ($) { "use strict";
	var appName = 'AppOpenPopupHandlerWithDataAttributeTags';
		
	/**
	 * Needs to be here, do not edit
	 */
	var appID = appName.replace(/[^a-z]+/gi, '').replace(/(.)([A-Z])/g, "$1-$2").toLowerCase();var appDataHandler = '[data-app-popup]';	var oc = 'oc.'+appName; var Base = $.oc.foundation.base, BaseProto = Base.prototype; var Application = function (element, options) { this.$el = $(element); this.options = options || {}; this.appID = appID; this.appName = appName; this.oc = oc; $.oc.foundation.controlUtils.markDisposable(element); Base.call(this); this.sysInit(); }; Application.prototype = Object.create(BaseProto); Application.prototype.constructor = Application;
    
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
    	if(this.$el.get(0).hasAttribute('data-popup-handler')) {
    		var data = {};
    		if(this.$el.get(0).hasAttribute('data-popup-data')) {
    			eval('data = {'+this.$el.data('popup-data')+'}');
    		}
    		else if(this.$el.get(0).hasAttribute('data-apprequest-data')) {
    			eval('data = {'+this.$el.data('apprequest-data')+'}');
    		}
    		
    		this.$el.appPopup(this.$el.getAppId(),this.$el.data('popup-handler'),data);
    	}
    	
    	
    	
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
    $.fn.appPopup = $.appPopup = function(appid,request,data,size) {
    	var sendobject = {
    	    handler:'onAppRequest',
    	    size: (typeof size === 'undefined' ? 'widthsixhundered' : size),
    		extraData:{
    			appid:appid,
    			request:request
    		}
    	};
    	
    	sendobject.extraData.data = data;
    	$(window).one('shown.bs.modal',function(e) {
    		var $target = $(e.target);
    		$target.on('hide.bs.modal',function() {
    			$target.find('[data-disposable]').each(function(i,e){$(e).trigger('dispose-control')});
    		})
    		if($target.find('.modal-dialog').height() > $target.height()) {
    			$target.animate({
				    scrollTop: $target.find(".modal-content").offset().top - 150,
				});
    		}
    	});
    	
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
    	if($elems.length > 0) {
    		$elems[appName]();
    	}
    	
    })

}(window.jQuery);