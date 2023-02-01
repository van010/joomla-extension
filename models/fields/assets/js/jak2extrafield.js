

function jaExtraFieldParams(id, obj) {
    if(obj.value.replace(/^\d+\:/, '') == 'valuerange' || obj.value.replace(/^\d+\:/, '') == 'rangeslider'){
        $(id+'format').show();
    }else{
        $(id+'format').hide();
    }
	for(var i=0; i<obj.options.length; i++) {
		opt = obj.options[i];
		tp = opt.value.replace(/^\d+\:/, '');

		if($(id+tp)) {
			if(opt.selected) {
				$(id+tp).show();
			} else {
				$(id+tp).hide();
			}
		}
	}
	
}