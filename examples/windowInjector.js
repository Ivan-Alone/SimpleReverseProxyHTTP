var fakeWindow = window;

(function() {
	var clone = function clone(o, copyProto, copyNested, iter) {
		if (iter == undefined) {
			iter = 0;
		}
	        function Create(i){
	        	for(i in o){
				if(o.hasOwnProperty(i)) this[i] = ( copyNested && typeof o[i] == "object"  && iter < 2)
	        		? clone(o[i], true, true, iter+1) : o[i];
		        }
		}
		if(copyProto && o != null && o.__proto__ != undefined) Create.prototype = o.__proto__;
		return new Create();
	}

	var getLocation = function(href) {
		var l = document.createElement("a");
		l.href = href;
		return l;
	};

	fakeWindow = Object.clone(window, false, true);

	var loc = getLocation(window.location.href);
	loc.host = 'rutracker.org'

	fakeWindow.location.href = loc.toString();
})();
