+function ($) { "use strict";
	var appName = 'Draggable';
		
	/**
	 * Needs to be here, do not edit
	 */
	var appID = appName.replace(/[^a-z]+/gi, '').replace(/(.)([A-Z])/g, "$1-$2").toLowerCase();var appDataHandler = '[data-drag-handle]';	var oc = 'oc.'+appName; var Base = $.oc.foundation.base, BaseProto = Base.prototype; var Application = function (element, options) { this.$el = $(element); this.options = options || {}; this.appID = appID; this.appName = appName; this.oc = oc; $.oc.foundation.controlUtils.markDisposable(element); Base.call(this); this.sysInit(); }; Application.prototype = Object.create(BaseProto); Application.prototype.constructor = Application;
    
    Application.prototype.handlers = function(type) {
    	var body = $(document.body);
    	if(type == 'on') {
    		/** remove the stupid positioning of modals */
    		if(this.$el.hasClass('modal-content')) {
        		this.handleModelWrapper();
        	}
    		
    		this.$draghandle = this.$el.find('[data-drag-handle]');
    		this.dragging = false;
    		this.offsetX = 0;
    		this.offsetY = 0;
    		this.$el.css({
    				'-webkit-transition':'none',
    				'-moz-transition': 'none',
    				'-ms-transition': 'none',
    				'-o-transition': 'none',
    				'transition': 'none'
    		});
    		this.queue = [];
    		this.animating = false;
    	}
    	else {
    		this.$draghandle = null;
    		this.dragging = null;
    		this.offsetX = null;
    		this.offsetY = null;
    	}
    	
    	
    	
    	this.$el[type]('mousedown','[data-drag-handle]',this.proxy(this.mouseDown))
    	body[type]('mouseup',this.proxy(this.mouseUp))
    	body[type]('mousemove',this.proxy(this.mouseMove));
    	body[type]('mouseleave',this.proxy(this.mouseUp))
    	
    	
    };
    
    Application.prototype.handleModelWrapper = function() {
    	var parent = this.$el.closest('.modal-dialog');
    	 if(!parent.hasClass('dragging')) {
    		 var offset = parent.offset();
    		 parent.addClass('dragging');
    		 this.$el.css({left:offset.left+'px',top:offset.top+'px'});
    	 }
    }
    
    Application.prototype.animate = function() {
    	if(this.queue != null) {
    		this.animating = true;
	    	if(this.queue.length > 0) {
	    		var last = this.queue.pop();
	    		var foo = this.$el[0].style;
	    		foo.left =  last.x+'px';
	        	foo.top =  last.y+'px';
	    		this.queue = [];
	    	}
	    	requestAnimationFrame(this.proxy(this.animate));
    	}
    	else {
    		this.animating = false;
    	}
    }
    
    /**
     * Application JS is bound to any element that has [data-apprequest] defined in it's tag.
     * Make sure that element is a child of a tag containin data-appid so the ajax request
     * will go to the correct element
     * @param e
     */
    
    /**
     * Pushing out the event asap out of the way so it won't block the event queue to 
     * be discareded by the browser
     */
    Application.prototype.mouseMove = function(e) {
        if (this.dragging) {
        	var that = this;
        	
        	function far() {
        		that.queue.push({x:e.pageX  - that.offsetX , y:e.pageY  - that.offsetY});
        		if(!that.animating) {
        			requestAnimationFrame(that.proxy(that.animate));
        		}
        	}
        	window.setTimeout(far,0);
        	
        }
    }
    
    Application.prototype.mouseUp = function(e) {
    	
        this.dragging = false;
        
    }
    
    Application.prototype.mouseDown = function(e) {
    		
			if(e.target.hasAttribute('data-drag-handle')) {
				
	        		var offset = this.$el.offset();
	        		this.offsetX = e.pageX-offset.left;
	        		this.offsetY = e.pageY-offset.top;
	        	
	        	
	        	this.dragging = true;
	            
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
    	var $elems = $(appDataHandler).closest('.modal-content');
    	
    	/**
    	 * Start to do our checks.
    	 * but only if we have elements to process!
    	 */
    	if($elems.length > 0) {
    		
    		$elems.each(function(index, elem) {
    			
    			var $elem = $(elem);
    			$elem.addClass('metro-popup-content');
    			
    			var $control = $elems.closest('.control-popup');
    			$control.addClass('metro-control');
    			
    			/**
    			 * Loop through the siblings to find the nearest of each
    			 * We limit the scope by adding the classes of the elemnts
    			 * we are looking for.
    			 * These are not the elements we are looking for...
    			 */
	    		var $siblings = $control.parent().children('.popup-backdrop, .metro-control');
	    		
	    		/**
	    		 * Traditional for loop, deal with it.
	    		 * 
	    		 */
	    		for(var c=0;c < $siblings.length;c++) {
	    			var $current = $($siblings.get(c));
	    			
	    			if($current.hasClass('metro-control')) {
	    				/**
	    				 * Double check if it's a proper element and such.
	    				 * We use hide because it's bound in a closure from october
	    				 * We don't wish to throw errors to octobers code :-)
	    				 */
	    				if(c > 0) {
		    				var $test = $($siblings.get(c-1));
		    				if($test.hasClass('popup-backdrop')) {
		    					$test.hide();
		    				}
	    				}
	    			}
	    			
	    		}
	    		
    		});
    		
    		$elems[appName]();
    	}
    	
    	
    	
    })

}(window.jQuery);