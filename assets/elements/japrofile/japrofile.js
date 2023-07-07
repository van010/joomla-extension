/**
 * $JA#COPYRIGHT$
 */

var JAProfileConfig = function (options){

	var $ = jQuery;
	this.vars = Object.assign({
		el: $(`#${options}`)
	} || []);
	
	this.init = function(){
		var vars = this.vars;
		vars.group = 'jaform';

		if(vars.el){
			vars.el.on('change', function(){
				JAFileConfig.inst.changeProfile(this.value);
			});
		}
		
		var adminlist = $('#module-sliders');
		if(adminlist.length > 0){
			adminlist = adminlist.getElement('ul.adminformlist');
			if(adminlist){
				$('<li>', {
					class: 'clearfix level2'
				}).appendTo(adminlist);
			}
		}
	};
	
	this.compareVersions = function(a, b) {
		var v1 = a.split('.');
		var v2 = b.split('.');
		var maxLen = Math.min(v1.length, v2.length);
		for(var i = 0; i < maxLen; i++) {
			var res = parseInt(v1[i]) - parseInt(v2[i]);
			if (res != 0){
				return res;
			}
		}
		return 0;
	};
	
	this.changeProfile = function(profile_){
		if (profile_ === '') return;
		var profile = $(`#${profile_}`);
		var profile_name = profile.length > 0 ? profile.val() : profile_;
		console.log(`change profile: ${profile_name}`);

		this.vars.active = profile_name;
		this.fillData();
		
		if(typeof JADepend != 'undefined' && JADepend.inst){
			JADepend.inst.update();
		}
		this.btnGroup();
	};
	
	this.btnGroup =function (){
		(function($) {
			$(".btn-group input:checked").each(function()
			{	
				$(this).parent('fieldset').find('label').removeClass('active btn-success btn-danger btn-primary');
				
				if ($(this).val() == '') {
					$("label[for=" + $(this).attr('id') + "]").addClass('active btn-primary');
				} else if ($(this).val() == 0 || $(this).val().toLowerCase() == 'false' || $(this).val().toLowerCase() == 'no') {
					$("label[for=" + $(this).attr('id') + "]").addClass('active btn-danger');
				} else {
					$("label[for=" + $(this).attr('id') + "]").addClass('active btn-success');
				}
			});
		})(jQuery);
	};
	
	this.serializeArray = function(){
		var vars = this.vars,
			els = [],
			allelms = document.adminForm.elements,
			pname1 = vars.group + '\\[params\\]\\[.*\\]',
			pname2 = vars.group + '\\[params\\]\\[.*\\]\\[\\]',
			il = allelms.length;

		for (var i = 0; i < il; i++){
		    var el = $(allelms[i]);
			var el_name = el.attr('name');

			if (!el_name) continue;
			if (el_name.match(pname1) || el_name.match(pname2)){
				els.push(el);
			}
		}
		
		return els;
	};

	this.test = function (regex, params) {
		// return ((typeof(regex) === 'regexp') ? regex : new RegExp('' + regex, params)).test(this);
		return ((typeof regex === 'object' && regex instanceof RegExp) ? regex : new RegExp('' + regex, params)).test(this);
	}

	this.fillData = function (){
		var vars = this.vars,
			els = this.serializeArray(),
			profile = JAFileConfig.profiles[vars.active];
		if(els.length === 0 || !profile) return;

		$.each(els, (idx, el) => {
			const name = this.getName(el);
			const values = (profile[name] != undefined) ? profile[name] : '';

			this.setValuesToHtmlTag(el, values);

			//J3.0 compatible
			if(jQuery(el).next().hasClass('chzn-container') && typeof jQuery != 'undefined'){
				var chosen = jQuery(el).trigger('liszt:updated').data('chosen');
				if(chosen){
					chosen.current_value = values;
				}
			}
		}, this);
	};
	
	this.setValuesToHtmlTag = function(el, vals){
		const js_node = el[0];
		const tag_name = js_node.localName
		const tag_type = js_node.type;

		if(tag_name === 'select'){
			var selected = false;
			var all_options = js_node.options;
			for(var i = 0; i < all_options.length; i++){
				var option = all_options[i];
				option.selected = false;
				if (vals.includes(option.value)) {
					option.selected = true;
					selected = true;
				}
			}
			
			if(!selected){
				all_options[0].selected = true;
			}
		}else {
			if(tag_type === 'checkbox' || tag_type === 'radio'){
				el.attr('checked', vals.includes(el.val()));
				const curr_label = $(`label[for="${el.attr('id')}"]`);
				// curr_label[0].className;
			} else {
				el.attr('value', vals);
			}
		}
	};
	
	this.getName = function(el){
		var matches = el.attr('name').match(this.vars.group + '\\[params\\]\\[([^\\]]*)\\]');
		if (matches){
			return matches[1];
		}
		
		return '';
	};
	
	/****  Functions of Profile  ----------------------------------------------   ****/
	this.deleteProfile = function(){
		if(confirm(JAFileConfig.langs.confirmDelete)){			
			this.submitForm(JAFileConfig.mod_url + '?jaction=delete&profile=' + this.vars.active, {}, 'profile');
		}		
	},
	
	this.cloneProfile = function (){
		var nname = prompt(JAFileConfig.langs.enterName);
		
		if(nname){
			nname = nname.replace(/[^0-9a-zA-Z_-]/g, '').replace(/ /, '').toLowerCase();
			if(nname == ''){
				alert(JAFileConfig.langs.invalidName);
				return this.cloneProfile();
			}
			
			JAFileConfig.profiles[nname] = JAFileConfig.profiles[this.vars.active];
			
			this.submitForm(JAFileConfig.mod_url + '?jaction=duplicate&profile=' + nname + '&from=' + this.vars.active, {}, 'profile');
		}
	};
	
	this.saveProfile = function (task){
		/* Rebuild data */		
		
		if(task){
			JAFileConfig.profiles[this.vars.active] = this.rebuildData();
			this.submitForm(JAFileConfig.mod_url,
				JAFileConfig.profiles[this.vars.active], this.vars.active,'profile', task);
		}
	};
	
	this.submitForm = function(url, data, profile, type, task){
		/*if(JAFileConfig.run){
			JAFileConfig.ajax.cancel();
		}*/

		// index.php?option=com_ajax&module=jajlex_articles&method=fetch_data&mod_id=802&limit=7&offset=0&format=json&lang=it
		const save_endpoint = `index.php?option=com_ajax&module=jaslideshow&method=save&format=json`;

		const baseUrl = Joomla.getOptions('system.paths').root;
		var encodedData = encodeURIComponent(JSON.stringify(data));
		const url_params = {
			option: 'com_ajax',
			module: 'jaslideshow',
			method: 'save_profile',
			profile: profile,
			source_data: encodedData,
			// mod_id: mod_id,
			format: 'json',
		};

		const params_string = new URLSearchParams(url_params).toString();
		const query_string = baseUrl + '/index.php?' + params_string.replace(/%2C/g, ','); // Replace %2C with a comma
		// const data_query = '&' + encodeURI('jform[params][' + data + ']');
		
		JAFileConfig.run = true;
		console.log(data);
		$.ajax({
			method: 'post',
			url: query_string,
			// data: data_query,
			dataType: 'json',
			success: function (res) {
				var obj = res || {};
				if (res.error){
					console.log(`Error: ${res.error}`);
				}else{
					console.log(`${obj.message} | ${obj.profile} | ${obj.type}`);
				}
				Joomla.submitform(task, document.getElementById('module-form') || document.getElementById('modules-form'));
			}
		})
    	
		/*JAFileConfig.ajax = new Request.JSON({
			url: url, 
			onComplete: function(result){
				
				JAFileConfig.run = false;
				
				if(result == ''){
					return;
				}
				
				if(!task){
					alert(result.error || result.successful);
				}

				var vars = this.vars;
				if(result.profile){
					switch (result.type){	
						case 'new':
							Joomla.submitbutton(document.adminForm.task.value);
						break;
						
						case 'delete':
							if(result.template == 0 || typeof(result.template) == 'undefined'){
								var opts = vars.el.options;
								
								for(var j = 0, jl = opts.length; j < jl; j++){
									if(opts[j].value == result.profile){
										vars.el.remove(j);
										break;
									}
								}
								//J3.0 compatible
								if(vars.el.hasClass('chzn-done') && typeof jQuery != 'undefined'){
									jQuery(vars.el).trigger('liszt:updated');
								}
								
							}
							
							vars.el.options[0].selected = true;					
							this.changeProfile(vars.el.options[0].value);
							
						break;
						
						case 'duplicate':
							vars.el.options[vars.el.options.length] = new Option(result.profile, result.profile);							
							vars.el.options[vars.el.options.length - 1].selected = true;
							this.changeProfile(result.profile);
							//J3.0 compatible
							if(vars.el.hasClass('chzn-done') && typeof jQuery != 'undefined'){
								jQuery(vars.el).trigger('liszt:updated');
							}
						break;
						
						default:
					}
				}
			}.bind(this),
			
			onSuccess: function(){
				if(task){
					Joomla.submitform(task, document.getElementById('module-form') || document.getElementById('modules-form'));
				}
			}
		}).post(request);*/
	};
	
	this.rebuildData = function (){
		var els = this.serializeArray();
		var json = {};
		$.each(els, (idx, el_) => {
			const el = $(el_);
			const value = this.getValueFrom(el);
			if(Object.keys(value).length > 0){
				var key = Object.keys(value)[0]
				var val = value[key];
				json[key] = val;
			}
		});
		
		return json;
	}

	this.getValueFrom = function (el) {
		const js_el = el[0];
		const tag_name = js_el.localName; // select, input,...
		const tag_type = js_el.type; // select-one, text, radio, checkbox,..
		const el_name = this.getName(el);
		var obj_value = {};

		if (js_el.nodeType === 1){
			if (tag_name === 'select'){
				obj_value[el_name] = el.val();
				return obj_value;
			}
			if (tag_type === 'text'){
				obj_value[el_name] = el.val();
				return obj_value;
			}
			if ((tag_type === 'radio' || tag_type === 'checkbox') && js_el.checked){
				obj_value[el_name] = el.val();
				return obj_value;
			}
		}
		return obj_value;
	}

	this.init();
};

var JAFileConfig = window.JAFileConfig || {};