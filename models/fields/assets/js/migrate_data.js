/**
 * $JA#COPYRIGHT$
 */
var jaDataMigrator = window.jaDataMigrator || {};

(function($){
    jaDataMigrator.process = function (task){
        $("#ja-result").html("Processing ...");
        //send the ajax request to the server
        $.ajax({
            type: 'POST',
            url: "index.php?option=com_ajax&plugin=jacontenttype&format=html&view=convert&tmpl=component&jatask="+task,
            //dataType : "json",
            data: $("#style-form").serializeArray(),
            success: function(data,textStatus,jqXHR) {
                $("#ja-result").html(data);
            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                $("#ja-result").html(data);
            }
        });
    }

    function selectChildren(val, idparent) {
    	$(".subcat[percat="+(idparent)+"]").each(function(){
			if (val == 'ignore') {
                $(this).val('ignore');
                $(this).trigger("liszt:updated");
            } else {
                $(this).val('auto');
                $(this).trigger("liszt:updated");
            }
    		if (parseInt($(".subcat[percat="+($(this).attr("idc"))+"]").length) != 0) {
        		selectChildren(val, $(this).attr("idc"));
        	}
    	});
    }

    function addSelectAuto(exgselect, exfselect) {
    	for (x in exgselect) {
    		if (isInt(x)) {
				var list = $('.extraGroup[idg="'+x+'"]'),
					field = $(".extraField-"+x),
					txt = $('.extratext[idg="'+x+'"]');
    			list.val(exgselect[x]);
    			field.empty();
	            field.append(selectList[exgselect[x]]);

				var val = field.data('value'); 
				if(val != 'ignore' && val != 'auto') {
					val = 'ctm_'+val.replace('ctm_', '');
				}
				field.find('option[value="'+val+'"]').attr('selected', 'selected');
	            field.trigger("liszt:updated");
    			if (exgselect[x] == 'auto') {
	    			txt.css('visibility', '');
	    		} else {
	    			txt.css('visibility', 'hidden');
	    		}
			}
    	}
    	for (y in exfselect) {
    		if (isInt(y)) {
				var val = exfselect[y];
				if(val != 'ignore' && val != 'auto') {
					val = 'ctm_'+val.replace('ctm_', '');
					val = val.toLowerCase();
				}
    			$('.exf[idf="'+y+'"]').val(val);
    			if (val == 'auto') {
    				$('.extratext[idf="'+y+'"]').css('visibility', '');
    			} else if (exfselect[y] == 'auto') {
    				$('.extratext[idf="'+y+'"]').css('visibility', 'hidden');
    			}
   				$('.exf[idf="'+y+'"]').trigger("liszt:updated");
			}
   		}
    }

    // function to remove select auto when merged. //bug
    function removeAutoselect () {
    	$('.extraGroup').each(function(){
    		if ($(this).attr('merged') == 1) {
    			var mer = $(this);
    			mer.find('option').each(function(){
    				if (mer.val() != $(this).val()) {
    					$(this).remove();
    				}
    			});
    		}
    	});
		$('.exf').each(function(){
    		if ($(this).attr('merged') == 1) {
//     			$(this).find('option')[0].remove();
				$('.extratext[idf="'+$(this).attr("idf")+'"]').css('visibility', 'hidden');
    		}
    	});
    }

    function isInt(value) {
	    var er = /^-?[0-9]+$/;

	    return er.test(value);
	}

    $(document).ready(function(){
        $(".catID").change(function(){
        	if ($("select.catID[idc="+$(this).attr('percat')+"]").val() == 'ignore') {
        		$(this).val('ignore');
        		$(this).trigger("liszt:updated");
        		return;
        	}
        	if ($(".subcat[percat="+($(this).attr("idc"))+"]").length != 0) {
        		selectChildren($(this).val(), $(this).attr("idc"));
        	}
        });

        $(".extraGroup").change(function(){
			var list = $(".extraField-"+$(this).attr("idg"));
			var txt = $('.extratext[idg="'+$(this).attr("idg")+'"]');
        	if ($(this).val() == 'auto') {
        		list.next().next().css('visibility', '');
        		txt.css('visibility', '');
        	} else if ($(this).val() != 'ignore') {
        		list.next().next().css('visibility', 'hidden');
        		txt.css('visibility', 'hidden');
        	} else {
        		//txt.find('input').val('');
        		//list.next().next().find('input').val('');
        		list.next().next().css('visibility', 'hidden');
        		txt.css('visibility', 'hidden');
        	}
            list.empty();
            list.append(selectList[$(this).val()]);
			//list.find('option[value="'+list.data('value')+'"]').attr('selected', 'selected');
            list.trigger("liszt:updated");
        });
        $(".exf").change(function(){
        	if ($(this).val() == 'auto') {
        		$('.extratext[idf="'+$(this).attr("idf")+'"]').css('visibility', '');
        	} else {
        		$('.extratext[idf="'+$(this).attr("idf")+'"]').find('input').val('');
        		$('.extratext[idf="'+$(this).attr("idf")+'"]').css('visibility', 'hidden');
        	}
        });
        addSelectAuto(exgselect, exfselect);
        //removeAutoselect();

        $(".fixj35").change(function(){
        	$(".fixj35").each(function(){
        		nid = $(this).attr('jid');
	        	$('#'+nid).val($(this).val());
        	});
        });
    });
})(jQuery);


