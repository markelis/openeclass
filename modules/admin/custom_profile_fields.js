(function () {
    'use strict';

    var byId = function (id) { return document.getElementById(id); },

        loadScripts = function (desc, callback) {
    	    var deps = [], key, idx = 0;

    	    for (key in desc) {
    	        deps.push(key);
    	    }

    	    (function _next() {
    	        var pid,
    	        name = deps[idx],
    	        script = document.createElement('script');

    	        script.type = 'text/javascript';
    	        script.src = desc[deps[idx]];

    	        pid = setInterval(function () {
    	            if (window[name]) {
    	                clearTimeout(pid);

    	                deps[idx++] = window[name];

    	                if (deps[idx]) {
    	                    _next();
    	                } else {
    	                    callback.apply(null, deps);
    	                }
    	            }
    	        }, 30);

    	        document.getElementsByTagName('head')[0].appendChild(script);
    	    })()
    	},

        console = window.console;


        if (!console.log) {
            console.log = function () {
                alert([].join.apply(arguments, ' '));
        };
    }

    // Multi groups
    Sortable.create(byId('multi'), {
        animation: 150,
        draggable: '.tile',
        handle: '.tile__name'
    });

    [].forEach.call(byId('multi').getElementsByClassName('tile__list'), function (el){
        Sortable.create(el, {
            group: 'photo',
            animation: 150,
            filter: ".ignore-item",
            onAdd: function (evt) {
                //check if source list remains empty
                if (evt.from.childNodes.length == 0) {
                    evt.from.innerHTML = "<tr class='ignore-item'><td colspan='9'>&nbsp;</td></tr>";
                }
            	
                //check if target list has only the transfered element and the empty one
                if (evt.item.parentElement.childNodes.length == 2) {
                    var childs = evt.item.parentElement.childNodes;
                    for (var i=0; i<childs.length; i++) {
                        if(childs[i].className == 'ignore-item') {
                            evt.item.parentElement.removeChild(evt.item.parentElement.childNodes[i]);
                        }
                    }
                }
            }
        });
    });
	
})();
