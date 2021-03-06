$(function() 
{
	$.template('addLangField', '<div class="uk-grid uk-grid-small uk-margin uk-margin-small" data-lang-item><div class="uk-width-2-3"><input name="langs[]" value="${lang}" class="uk-width-1-1" /></div><div class="uk-width-1-3"><a href="javascript:void(0);" data-action="delLang" class="uk-button uk-button-danger uk-width-1-1"><i class="uk-icon-trash"></i></a></div></div>');
	
	$.template('tableLangThead', '<th data-lang="${lang}" class="uk-text-center">${lang}</th>');
	$.template('tableLangRow', '<td data-lang="${lang_k}"><textarea name="${key}[${lang_k}]" rows="2" class="uk-width-1-1">${lang_v}</textarea></td>');
	
	//ignore oprion
	$.template('ignoreSelLangRow', '<option value="${code}">${code}</option>');
	
	//order
	$.template('orderLangRow', '<li data-id="${code}" class="uk-nestable-item"> \
		<div class="uk-nestable-panel"> \
			<div class="uk-nestable-handle uk-float-left"> \
				<i class="uk-nestable-handle uk-icon-bars uk-margin-small-right"></i> \
			</div> \
			<span class="noselect">${code}</i> \
			</span> \
		</div> \
	</li>');
	
	
	var admin_options = function() 
	{
		if (debug) console.log("[plugin] admin-options");
		
		$(document).on('click', '[data-submit]', e_submit_handler);
		$(document).on('click', '[data-action]', e_action_handler);
		$(document).on('change.uk.nestable', '[data-order-lang-container]', fn_order);
		
		$(document).on('change', '[data-translations] textarea', e_save_translation_handler);
	}
	
	//order langs
	fn_order = function()
	{
		var ele = $(this),
		dom = $('[data-order-lang-container]').find('[data-id]'),
		data_order = [];
	
		if(dom.length !== 0) for(var c in dom)
		{
			if(!$.isNumeric(c)) continue;
			data_order.push($(dom[c]).attr('data-id'));
		}
		
		fn_call_ajax("orLang", {
			data: data_order
		}, null, function(d)
		{
			//notify
			UIkit.notify(
			{
				message : d.message,
				status  : 'info',
				timeout : 5000,
				pos     : 'top-center'
			});
		});
	}
	
	//to actions
	e_action_handler = function()
	{
		var ele = $(this),
			dom_type = ele.attr('data-action'),
			dom_table = $('[data-translations] table'),
			dom_thead = dom_table.find('thead'),
			dom_tbody = dom_table.find('tbody'),
			dom_ser,
			up_langs = [];
			
		switch(dom_type)
		{
			case "delLangIgnore":
				ele.parents('[data-lang-item]').remove();
				dom_sels = $('[data-lang_ignore-container]').find('input');
			break;
			
			case "addLangIgnore":
				var getDomLang = $('select[name="addlangignore"]').val();
				
				$.tmpl('addLangField', 
				{
					'lang':getDomLang
				}).appendTo('[data-lang_ignore-container]');
				
				dom_sels = $('[data-lang_ignore-container]').find('input');
			break;
			
			case "addLang":
				var getDomLang = $('select[name="addlang"]').val(),
					dom_list_langs = $('[data-lang-item] :input');
				
				//prevent add exist one
				if(dom_list_langs.length > 0) for(var l in dom_list_langs)
				{
					if(!$.isNumeric(l)) continue;
					
					var val = $(dom_list_langs[l]).val();
					
					if(val == getDomLang)
					{
						//notify
						UIkit.notify(
						{
						    message : 'Este idioma ya existe.',
						    status  : 'info',
						    timeout : 5000,
						    pos     : 'top-center'
						});
						
						return;
					}
				}
				
				//ignorelist
				$.tmpl('ignoreSelLangRow', 
				{
					'code':getDomLang
				}).appendTo('select[name="addlangignore"]');
				
				//order list
				$.tmpl('orderLangRow', 
				{
					'code':getDomLang
				}).appendTo('[data-order-lang-container]');
				
				//----
				$.tmpl('addLangField', 
				{
					'lang':getDomLang
				}).appendTo('[data-lang-container]');
				
				//add rows of languages in table head
				$.tmpl('tableLangThead', {
					'lang':getDomLang
				}).appendTo(dom_thead.find('tr'));
				
				//add rows of languages in table head
				var fn_items_tbody = dom_tbody.find('tr');
				
				if(fn_items_tbody.length !== 0) for(i in fn_items_tbody)
				{
					if(!$.isNumeric(i)) continue;
					
					var fn_input = $(fn_items_tbody[i]).find('[data-disable] input'),
						fn_input_name = fn_input.attr('name');
						
					$.tmpl('tableLangRow', {
						'lang_k':getDomLang,
						'key':fn_input_name,
						'lang_v':''
					}).appendTo(fn_items_tbody[i]);
				}
				
				//añadimos traduccion
					
				var row_clone = $('[data-translations] tbody tr:eq(0)').clone(true);
	
				row_clone.find('.bg-gray input').attr('name', 'translation[lang_country_'+getDomLang+']').attr('value', 'lang_country_'+getDomLang);
				row_clone.find('textarea').val('');
				row_clone.find('td[data-lang]').each(function(i, o)
				{
					var ele = $(o),
						dom_lang_id = ele.attr('data-lang');
					
					ele.find('textarea').attr('name', 'translation[lang_country_'+getDomLang+']['+dom_lang_id+']');
				});
				
				row_clone.appendTo('[data-translations] tbody');
				
				dom_sels = $('[data-lang-container]').find('input');
				
				//notify
				UIkit.notify(
				{
				    message : 'Idioma añadido, en el lado derecho se ha añadido un campo nuevo para traducir!',
				    status  : 'info',
				    timeout : 5000,
				    pos     : 'top-center'
				});
			break;
			
			case "delLang":
				var dom_delItem = ele.parents('[data-lang-item]'),
					dom_item_lang = dom_delItem.find('input').val(),
					dom_def = $('select[name="defaultLang"]').val();
				
				if(dom_item_lang == dom_def) return;
				
				dom_delItem.remove();
				
				//remove table lang translations
				dom_thead.find('[data-lang="'+dom_item_lang+'"]').remove();
				dom_tbody.find('[data-lang="'+dom_item_lang+'"]').remove();
				
				//order
				$('[data-order-lang-container] li[data-id="'+dom_item_lang+'"]').remove();
				
				//ignore
				$('select[name="addlangignore"] option[value="'+dom_item_lang+'"]').remove();
				
				//remove translate
				$('[data-translations] tbody').find('input[value=lang_country_'+dom_item_lang+']').parents('tr').remove();
				
				dom_sels = $('[data-lang-container]').find('input');
			break;
		}
			
		if(dom_sels.length) for(var i in dom_sels)
		{
			if(!$.isNumeric(i)) continue;
			
			up_langs.push($(dom_sels[i]).val());
		}
		
		//call
		fn_call_ajax(dom_type, {
			data: up_langs	
		}, null, function(d)
		{
			//notify
			UIkit.notify(
			{
				message : d.message,
				status  : 'info',
				timeout : 5000,
				pos     : 'top-center'
			});
		});
	}
	
	//submit modals
	e_submit_handler = function()
	{
		var ele = $(this),
			dom_form = ele.parents('form'), 
			dom_type = dom_form.find('[data-submit]').attr('data-submit'), 
			dom_ser = dom_form.serialize();
			
		if(debug) console.log("[>] submit form ["+dom_type+"]");
		
		//fn_call_ajax = function(fn_hash, fn_data, fn_before, fn_success)
		fn_call_ajax(dom_type, dom_ser, function()
		{
			dom_form.find('[data-submit="'+dom_type+'"] i.uk-hidden').removeClass('uk-hidden');
		}, function(d)
		{
			dom_form.find('[data-submit="'+dom_type+'"] i').addClass('uk-hidden');
			
			if(debug) console.log(d);
			
			//notify
			UIkit.notify(
			{
			    message : d.message,
			    status  : 'info',
			    timeout : 5000,
			    pos     : 'top-center'
			});
		});
	}
	
	e_save_translation_handler = function()
	{
		var ele = $(this),
			dom = ele.parents('tr'),
			dom_key = dom.find('input').val(),
			dom_val = ele.parents('td').find('textarea').val(),
			dom_lang = ele.parents('[data-lang]').attr('data-lang');
		
		fn_call_ajax('upLang', {
			k: dom_key,
			v: dom_val,
			l: dom_lang
		}, null, function(d)
		{
			if(debug) console.log(d);
			
			if(d.status == 200)
			{
				dom.find('[data-lang]').addClass('bg-green');
				
			}else{
				dom.find('[data-lang]').addClass('bg-red');
				
				//notify
				UIkit.notify(
				{
				    message : d.message,
				    status  : 'info',
				    timeout : 5000,
				    pos     : 'top-center'
				});
			}
		});
	}
	
	admin_options();
});