$(function() 
{
	//page content
	$.template('preloadPageContent', '<div class="uk-width-1-1 uk-text-center preloader"><span class="uk-icon-spin uk-icon-spinner">&nbsp;</span></div>');
	$.template('msg', '<div class="uk-text-{{if status == 200}}success{{else}}danger{{/if}}">{{html message}}</div>');
	
	//parser del json
	$.template('parsePageContent', '{{if data}} {{each(i, o) data}} {{tmpl(data[i]) "pageAddGrid"}} {{/each}} {{/if}}');
	
	//grid
	$.template('pageAddGrid', '<div class="{{if type.match(/(mt-|pt-|pb-|mb-)/g)}}uk-width-1-1 ${type}{{else}}${type}{{if boxheight}} height-s {{/if}}{{/if}} uk-margin-bottom uk-margin-small" data-grid-element="${type}"><div class="uk-placeholder uk-padding-remove"><div class="uk-width-1-1">&nbsp;<div class="uk-sortable-handle uk-icon uk-icon-bars uk-text-small"></div>'+
		//menu inside element
		'{{tmpl() "pageAddGridOptions"}}'+
		
		//content
		'<div class="uk-width-1-1" data-content>{{if dom}} {{if dom.length !== 0}} {{tmpl(dom) "pageAddElement"}} {{/if}} {{/if}} {{if type.match(/(slider)/g)}}<img src="'+fn_base_script+'images/admin-slider-dump.png" class="e" />{{/if}}</div>'+
	'</div></div>');
	
	//menu inside element
	$.template('pageAddGridOptions', '{{if type.match(/(w-)/g)}} <a href="javascript:void(0);" data-page-parser="modalpageelements" data-tab="text" class="uk-text-small" title="Añadir/modificar contenido" data-uk-tooltip><i class="uk-icon-plus"></i></a> {{/if}} <a href="javascript:void(0);" data-page-parser="cleanupelement" class="uk-text-small" title="Eliminar elemento con el contenido" data-uk-tooltip><i class="uk-icon-trash uk-text-danger"></i></a>'+
		//opciones extra sobre el grid
		'<span class="uk-float-right uk-text-small uk-margin-small-right"><a href="javascript:void(0);" title="Opciones extendidas de este elemento (grid)" data-uk-tooltip data-page-parser="modalpageelements" data-tab="grid"><i class="uk-icon-puzzle-piece uk-text-"></i></a></span>'+
		
		//descripcion de lo que esta aplicado al row del grid
		'<span class="uk-float-right uk-text-small uk-margin-small-right uk-width-3-10 uk-text-truncate" data-uk-tooltip title="${type}" data-element-title>${type}</span>'+
		
		//mensaje sobre oculto
		'<span class="uk-float-right uk-text-small uk-margin-small-right uk-width-1-10 uk-text-center" data-status-element><i class="uk-icon-eye uk-text-success" title="Este elemento en esta resolución sera visible" data-uk-tooltip></i><i class="uk-icon-eye-slash uk-text-danger" title="Este elemento ahora mismo no esta visible" data-uk-tooltip></i></span>'+
			//'{{if type.match(/(w-)/g) && type !== "w-1-1"}}<span title="Igualar en altura entre elementos marcados" data-uk-tooltip><input type="checkbox" name="boxheight" value="1" {{if boxheight}}checked{{/if}}></span>{{/if}}&nbsp;</span>'+
	'</div>');
	
	//elements
	$.template('pageAddElement', '{{if type}}'+
		'<div data-dom="text" class="uk-width-1-1">{{html value}}</div>'+
	'{{/if}}');
	
	//option each
	$.template('selectOptionTMPL', '{{if data && data.lst}} {{each(i, v) data.lst}} <option value="${v.id}">${v.title}</option> {{/each}} {{/if}}');
	
	//page messages
	$.template('preload', '<i class="uk-icon-spin uk-icon-spinner"></i> Actualizando');
	$.template('message', '{{if status == 200}}<div class="uk-text-success"><i class="uk-icon-check-circle"></i> Cambios guardados.</div>{{else}}<div class="uk-text-danger">{{if message}}{{html message}}{{else}}<i class="uk-icon-warning"></i> Ui, no se ha podido guardar.{{/if}}</div>{{/if}}');
	
	//rel page
	$.template('relItem', '{{if status == 200}}<li data-id="${data.id}"><div class="uk-grid uk-grid-small"><div class="uk-width-2-3"><strong>[${data.lang}]</strong> ${data.obj_title}</div><div class="uk-width-1-3"><a href="javascript:void(0);" data-action="delRelPage" class="uk-button uk-button-small uk-button-danger uk-float-right"><i class="uk-icon-trash"></i></a></div></div></li>{{/if}}');
	
	var admin_pages = function() 
	{
		if (debug) console.log("[plugin] admin-pages");
		
		$(document).on('click', '[data-action]', e_action_handler);
		$(document).on('click', '[data-submit]', e_submit_handler);
		
		//blog
		$(document).on('click', '[data-blog-action]', e_blog_action_handler);
		
		//page type
		$(document).on('change', 'select[name="type"]', e_change_type_page);
		
		//page actions
		$(document).on('click', '[data-page-add-element]', fn_pageAddElement_handler);
		$(document).on('click', '[data-page-parser]', fn_pageParserActions_handler);
		$(document).on('change.uk.sortable', '[data-uk-sortable]', fn_process_for_save);
		
		//quitamos class edit al cerrar el modal
		$('#pageElements.uk-modal').on(
		{
			'show.uk.modal': function()
			{
				//edit elements gallery selector
				fn_readGallerys_selector();
			},
			
			'hide.uk.modal': function()
			{
				$('[data-grid-element].edit').removeClass('edit');
			}
		});
		
		//page parser
		$('[data-pagecontainer]').each(function()
		{
			$('[data-pagecontainer]').html($.tmpl('preloadPageContent'));
			
			fn_parse_pageContent();
		});
	}
	
	//change page type show hide content or options
	e_change_type_page = function()
	{
		var ele = $(this),
			page_selected = ele.find('option:selected').val();
			
		switch(page_selected)
		{
			case "3": //blog
				$('[data-resumen]').removeClass('uk-hidden');
			break;
			
			case "2": //article
				$('[data-resumen]').removeClass('uk-hidden');
			break;
			
			case "1": //pagina
				$('[data-resumen]').addClass('uk-hidden');
			break;
		}
	}
	
	//blog actions (comments)
	e_blog_action_handler = function()
	{
		var ele = $(this),
			dom_id = ele.parents('[data-id]').attr('data-id'),
			form_pid = ele.parents('form[data-id]').attr('data-id'),
			dom_parent_id = ele.attr('data-parent'),
			dom_type = ele.attr('data-blog-action'),
			dom_modal = $('#editModalComment', document),
			modal = UIkit.modal(dom_modal, {modal: false});
			
		if(debug) console.log('[>] e_blog_action_handler id['+dom_id+'] action['+dom_type+']');
		
		switch(dom_type)
		{
			case 'edit':
				if(!modal.isActive())
				{
					modal.show();
					
					var dom_id = ele.parents('[data-id]').attr('data-id');
				
					//fn_call_ajax = function(fn_hash, fn_data, fn_before, fn_success)
					fn_call_ajax("blogCommentsActions", {
						id:dom_id,
						type:'getCommentData',
						data:null
					}, null, function(d)
					{
						if(debug) console.log(d);
						
						if(d.status == 200)
						{
							//clean
							editors[0].setValue('');
							$('.uk-modal.uk-open form').find('textarea').val('');
							$('.uk-modal.uk-open form').find('input').val('');
							
							clearTimeout($.data(this, 'modalOpen'));
							$.data(this, 'modalOpen', setTimeout(function()
							{
								$('.uk-modal.uk-open').find('[name="c_autor"]').val(d.data.author);
								$('.uk-modal.uk-open').find('[name="c_autor_email"]').val(d.data.author_mail);
								$('.uk-modal.uk-open').find('[name="parent_id"]').val(d.data.p_id);
								$('.uk-modal.uk-open').find('[name="id"]').val(d.data.id);
								$('.uk-modal.uk-open').find('[name="pid"]').val(form_pid);
								$('.uk-modal.uk-open').find('[name="type"]').val('edit');
								$('.uk-modal.uk-open').find('[data-submit]').text('Guardar');
								
								(d.data.comment.length !== 0) ? editors[1].setValue(d.data.comment, true) : editors[1].setValue('', false);
							}, 900));
						}else{
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
				return;
			break;
			
			case 'reply':
				if(!modal.isActive())
				{
					modal.show();
					
					$('.uk-modal.uk-open').find('[name="c_autor"]').val('Administrador');
					$('.uk-modal.uk-open').find('[name="c_autor_email"]').val('info@glunt.es');
					$('.uk-modal.uk-open').find('[name="parent_id"]').val(dom_parent_id);
					$('.uk-modal.uk-open').find('[name="id"]').val('');
					$('.uk-modal.uk-open').find('[name="pid"]').val(form_pid);
					$('.uk-modal.uk-open').find('[name="type"]').val('reply');
					$('.uk-modal.uk-open').find('[name="pageContentText"]').val('');
					$('.uk-modal.uk-open').find('[data-submit]').text('Responder');
					
					editors[1].setValue('', false);
				}
				return;
			break;
		}
		
		//fn_call_ajax = function(fn_hash, fn_data, fn_before, fn_success)
		fn_call_ajax('blogCommentsActions', 
		{
			id:dom_id,
			type:dom_type
		}, null, function(d)
		{
			if(debug) console.log(d);
			
			//notify
			UIkit.notify(
			{
			    message : d.message,
			    status  : 'info',
			    timeout : 5000,
			    pos     : 'top-center'
			});
			
			if(d.status == 200)
			{
				if(dom_type == 'del') $('[data-comments]').find('li[data-id="'+dom_id+'"]').remove();
				if(dom_type == 'approbed') ele.parents('[data-id="'+dom_id+'"]' ).find('[data-badge-status]').text('Aprobado y visible');
				if(dom_type == 'desapprobed') ele.parents('[data-id="'+dom_id+'"]' ).find('[data-badge-status]').text('No aprobado');
			}
		});
	}
	
	//page actions
	fn_pageParserActions_handler = function()
	{
		var ele = $(this),
			dom_type = ele.attr('data-page-parser'),
			dom_container = $('[data-uk-sortable]', document);			
		
		if(debug) console.log('[>] fn_pageParserActions_handler type['+dom_type+']');
		
		switch(dom_type)
		{
			case "cleanupall":
				//limpia todo el grid
				var fn_items = dom_container.find('div [data-page-parser="cleanupelement"]');
				
				if(debug) console.log(fn_items);
				
				if(fn_items.length !== 0) 
				{	
					for(var i in fn_items)
					{
						if(!$.isNumeric(i)) continue;
						
						$(fn_items[i]).trigger('click');
					}
					
					//limpiamos el back
					$('textarea[name="pageContent"]').val('');
				}
			break;
			
			case "cleanupelement":
				//limpia un elemento
				$(this).parents('[data-grid-element]').remove();
			break;
			
			//pasamos opciones al abrir el modal
			case "modalpageelements":
				var modal = UIkit.modal('#pageElements', {modal: false}),
					domContainer = $('#pageElements').data('domContainer', ele.parents('[data-grid-element]').find('[data-content]')),
					dom_element_cnt = ele.parents('[data-grid-element]').attr('data-grid-element'),
					dom_element_type = ele.parents('[data-grid-element]').find('[data-dom]').attr('data-dom'),
					dom_tab_selected = (ele.attr('data-tab')) ? ele.attr('data-tab') : null, //button tiene data-tab
					fn_content;
				
				if(!modal.isActive()) modal.show();
				
				clearTimeout($.data(this, 'modalOpen'));
				$.data(this, 'modalOpen', setTimeout(function()
				{
					//show tab item
					if(dom_tab_selected == null || dom_tab_selected == undefined || dom_tab_selected == 'text') dom_tab_selected = (/(slider)/.test(dom_element_cnt)) ? 'slider' : 'text';
					
					var dom_tab = $('.uk-modal.uk-open', document),
						fn_indx = dom_tab.find('.uk-tab #'+dom_tab_selected).parents('li').index();
			        
			        UIkit.switcher(dom_tab.find('.uk-tab')).show(fn_indx);
					
			        //clean
					editors[0].setValue('');
					$('.uk-modal.uk-open form').find('textarea').val('');
					$('.uk-modal.uk-open form').find('input').val('');
			        
			        //rellenamos campos con datos del stage
			        if(dom_element_type == 'text')
			        {
				        fn_content = ele.parents('[data-grid-element]').find('[data-dom="'+dom_element_type+'"]').html();
						(fn_content.length !== 0) ? editors[0].setValue(fn_content, true) : editors[0].setValue('', false);
					}
					
					//modificamos el texto
					fn_content = ele.parents('[data-grid-element]').find('[data-dom="'+dom_element_type+'"]').html();
					(fn_content !== undefined && fn_content.length !== 0) ? editors[0].setValue(fn_content, true) : editors[0].setValue('', false);
					
					//add edit class
					ele.parents('[data-grid-element]').addClass('edit');
					
					if((/(slider)/.test(dom_element_cnt) ? dom_element_cnt : undefined))
					{
						fn_readGallerys_selector(function()
						{
							var modal_opts = $('.uk-modal.uk-open form[data-slider-opts]'),
								slider_parser = dom_element_cnt.match(/^slider-(.*?)\-(.*?)\-(.*?)\s/);
								
							modal_opts.find('[name="slider[showArrows]"]').val(slider_parser[1]);
							modal_opts.find('[name="slider[showDots]"]').val(slider_parser[2]);
							modal_opts.find('[name="slider[g_id]"]').val(slider_parser[3]);
						});
					}
					
					//grid opciones asignamos las opciones 1ra vez simple
					if(dom_element_cnt !== undefined)
					{
						var modal_opts_container = $('.uk-modal.uk-open [data-grid-options]'),
							dom_inp = modal_opts_container.find('input[type="checkbox"]'),
							dom_sel = modal_opts_container.find('select');
						
						//limpiamos
						dom_inp.prop('checked', false);
						dom_sel.val(0);
						
						//seleccionamos segun
						if((/\s/.test(dom_element_cnt) ? dom_element_cnt : undefined))
						{
							//check box
							var doms_to_select = dom_element_cnt.split(' ');
							
							if(doms_to_select.length !== 0) for(var i in doms_to_select)
							{
								if(doms_to_select[i] == '') continue;
								if(!$.isNumeric(i)) continue;
							
								//checkbox
								modal_opts_container.find('input[type="checkbox"][name="'+doms_to_select[i]+'"]').prop('checked', true);
							
								var ele_reg_sel_or_chech = new RegExp(/(w|p|m)?(w|s|m|l|xl|t|b|l|r)\-/g),
									dom_n = doms_to_select[i].match(ele_reg_sel_or_chech),
									dom_checks = new RegExp(/(hs|hm|hl|hxl|g|gc|mt-n|mb-n|ml-n|mr-n|pl-n|pr-n|pt-n|pb-n|border|mc)/g);
								
								if(doms_to_select[i].match(ele_reg_sel_or_chech) && doms_to_select[i].search(dom_checks) == -1)
								{
									var dom_val = doms_to_select[i].replace(ele_reg_sel_or_chech, ''),
										dom_name = (dom_n[0]) ? dom_n[0].split('-') : undefined,
										dom_name = (dom_name !== undefined || dom_name.length !== 0) ? dom_name[0] : undefined;
									if(dom_name == undefined) return;
									
									//select
									modal_opts_container.find('select[name="'+dom_name+'"] option[value="'+dom_val+'"]').prop('selected', true);
								}
							}
							
							return;
						}else{
							//simple
							var dom_val = dom_element_cnt.replace(/w-/i, '');
							
							$('.uk-modal.uk-open [data-grid-options]').find('select[name="w"]').val(dom_val);
						}
					}

					/*
					if(dom_element_type == 'img' || dom_element_type == 'embed')
					{
						fn_content = ele.parents('[data-grid-element]').find('[data-dom="'+dom_element_type+'"]').attr('data-value');
						
						var fn_input = (dom_element_type == 'img') ? 'image' : 'embed';
						
						$('.uk-modal form [name="'+fn_input+'"]').val(fn_content);
					}
					*/
				}, 900));
			break;
		}
		
		//procesamos para guardado
		fn_process_for_save();
	}
	
	//leer galerias rellenamos en el modal de editor de elementos > slider
	fn_readGallerys_selector = function(fn_callback)
	{
		fn_call_ajax('manageGallery', 
		{
			'type':'manage',
			'pid':null,
			'gid':null
		}, function()
		{
			
		}, function(d)
		{
			if(d.status == 200)
			{
				$('.uk-modal.uk-open [data-slider-opts] select[name="slider[g_id]"]').html($.tmpl("selectOptionTMPL", d));
				
				//callback
				if(fn_callback) fn_callback.call(this);
			}else{
				$('.uk-modal.uk-open [data-slider-opts] select[name="slider[g_id]"]').addClass('uk-form-danger');
			}
		});
	}
	
	//page add elements desde modal
	fn_pageAddElement_handler = function(e)
	{
		var ele = $(this),
			dom_type = ele.attr('data-page-add-element'),
			dom_container = $('[data-pagecontainer]', document),
			dom_addElement = ele.parents('.uk-modal').find('input[name="addElement"]:checked').val(),
			dom_ele_pos = false;
		
		if(debug) console.log('[>] fn_pageAddElement_handler type['+dom_type+']');
		
		switch(dom_type)
		{
			//texto contenido interios
			case "text":
				var dom_container = $('#pageElements.uk-modal').data('domContainer'),
					fn_value;
				
				//if(dom_type == 'img') fn_value = $('.uk-modal input[name="image"]').val();
				//if(dom_type == 'embed') fn_value = $('.uk-modal input[name="embed"]').val();
				if(dom_type == 'text') fn_value = $('.uk-modal [name="pageContentText"]').val();
				
				$(dom_container).html($.tmpl('pageAddElement', 
				{
					type:dom_type,
					value:fn_value
				}));
				
				//clean modal inputs
				$('.uk-modal.uk-open form').find('textarea').val('');
				$('.uk-modal.uk-open form').find('input').val('');

				//limpiamos el wysihtml
				editors[0].setValue('');
			break;
			
			//añadimos elemento al stage con config predeterminado margin padding
			case (/(mt-|pt-|pb-|mb-)/.test(dom_type) ? dom_type : undefined):
				//añadimos elemento
				if(dom_addElement == 'down')
				{
					//añadimos al final
					$('[data-grid-tmpl]').append($.tmpl('pageAddGrid', 
					{
						type:'w-1-1 '+dom_type
					}));
				}else{
					//añadimos al principio
					dom_container.after($.tmpl('pageAddGrid', 
					{
						type:'w-1-1 '+dom_type
					}));
				}
			break;
			
			//añadimos elemntos w-1-1 w-xxx
			case (/(w-)/.test(dom_type) ? dom_type : undefined):
				//añadimos elemento
				if(dom_addElement == 'down')
				{
					//añadimos al final
					$('[data-grid-tmpl]').append($.tmpl('pageAddGrid', 
					{
						type:dom_type
					}));
				}else{
					//añadimos al principio
					dom_container.after($.tmpl('pageAddGrid', 
					{
						type:dom_type
					}));
				}
			break;

			//opciones extras
			case "gridOpt":
				var modal_opts_container = $('.uk-modal.uk-open [data-grid-options]'),
					domal_opts = modal_opts_container.parents('form').find('input[type="checkbox"]:checked, select option:selected:not([value="0"])'),
					dom_out_opts = '',
					dom_el = $('[data-grid-element].edit'),
					dom_parser_addon_loc = dom_el.attr('data-grid-element').match(/^slider-(.*?)\-(.*?)\-(.*?)\s/),
					dom_parser_addon = (/(slider)/.test(dom_el.attr('data-grid-element'))) ? dom_parser_addon_loc[0]+' ' : ''; //mantenemos slider y añadimos nuevos cambios en el elemento
				
				if(domal_opts.length !== 0)
				{
					for(var i in domal_opts)
					{
						if(!$.isNumeric(i)) continue;
						
						var el = $(domal_opts[i]);
						
						if(el.is('input[type="checkbox"]'))
						{
							//checkbox
							dom_out_opts += el.attr('name')+' ';
							
							//asignamos directamente solo control del grid
							if(/g$/.test(el.attr('name')))
							{
								dom_out_opts = 'w-1-1 g';
								break;
							}
							
							if(/gc$/.test(el.attr('name')))
							{
								dom_out_opts = 'w-1-1 gc';
								break;
							}
							//end asignamos directamente solo control del grid
						}else{
							//select
							if(el.val() !== 0) dom_out_opts += el.parents('select').attr('name')+'-'+el.val()+' ';
						}
					}
					
					dom_out_opts = dom_parser_addon+dom_out_opts;
					
					dom_el.attr('data-grid-element', dom_out_opts).attr(
					{
						'class':'uk-margin-bottom uk-margin-small '+dom_out_opts,
						'title':dom_out_opts
					});
					dom_el.find('[data-element-title]').text(dom_out_opts);
				}
			break;
			
			//slider opciones aplicamos en el slider
			case "sliderOpt":
				var modal_opts = $('.uk-modal.uk-open form[data-slider-opts]'),
					dom_el = $('[data-grid-element].edit'),
					dom_slider_arrows = modal_opts.find('[name="slider[showArrows]"]').val(),
					dom_slider_dots = modal_opts.find('[name="slider[showDots]"]').val(),
					dom_gid = modal_opts.find('[name="slider[g_id]"]').val(),
					dom_parser_addon_loc = dom_el.attr('data-grid-element'),
					dom_parser_addon = (/(slider)/.test(dom_parser_addon_loc)) ? dom_parser_addon_loc.replace(/slider-(.*?)\-(.*?)\-(.*?)\s/, '') : dom_parser_addon_loc; //mantenemos todo menos slider
				
				//clean
				modal_opts.find('[name="slider[g_id]"]').removeClass('uk-form-danger');
				
				if(!dom_gid)
				{
					//eliminar slider
					dom_out_opts = dom_parser_addon;
					
					dom_el.find('[data-content]').html('');
				}else{
					dom_out_opts = 'slider-'+dom_slider_arrows+'-'+dom_slider_dots+'-'+dom_gid+' '+dom_parser_addon;
					
					dom_el.find('[data-content]').html('<img src="'+fn_base_script+'images/admin-slider-dump.png" class="e" />');
				}
				//modificamos titulo
				dom_el.attr('data-grid-element', dom_out_opts).attr(
				{
					'class':'uk-margin-bottom uk-margin-small '+dom_out_opts,
					'title':dom_out_opts
				});
				dom_el.find('[data-element-title]').text(dom_out_opts);
				
				//clean modal options
				modal_opts.find('[name="slider[showArrows]"]').val(0);
				modal_opts.find('[name="slider[showDots]"]').val(0);
				modal_opts.find('[name="slider[g_id]"]').val(0);
			break;
		}
		
		if(dom_type !== 'gridOpt' || dom_type !== 'text') dom_ele_pos = (dom_addElement == 'down') ? $('[data-grid-tmpl] [data-grid-element]:last-child').position().top : 0;
		
		if(dom_ele_pos !== false) $('html, body').animate(
		{
			scrollTop: dom_ele_pos+'px'
		}, 600);
		
		$('.uk-modal.uk-open .uk-modal-close.uk-close', document).trigger('click');
		
		fn_process_for_save();
	}
	
	//pricesamos el contenido para guardar json con elementos
	fn_process_for_save = function()
	{
		if(debug) console.log('[>] fn_process_for_save');
		
		var dom_msg = $('[data-page-message]', document),
			dom_form_json = $('[name="pageContent"]'),
			dom_elem = $('[data-uk-sortable] [data-grid-element]'),
			data = [];
		
		dom_msg.html($.tmpl('msg', 
		{
			status:200,
			message:'<i class="uk-icon-spin uk-icon-spinner"></i> Preparango para guardar'
		}));
		
		//process
		if(dom_elem.length !== 0) for(var i in dom_elem)
		{
			if(!$.isNumeric(i)) continue;
			
			var f_type = $(dom_elem[i]).attr('data-grid-element'),
				dom_attr = $(dom_elem[i]).find('[data-dom]'),
				dom_attr_array = {},
				f_boxheight = $(dom_elem[i]).find('[name="boxheight"]').is(':checked');
				
			if(dom_attr.length !== 0) for(var o in dom_attr)
			{
				if(!$.isNumeric(o)) continue;
				
				var o_type = $(dom_attr[o]).attr('data-dom'),
					o_val = (o_type == 'text') ? $(dom_attr[o]).html() : $(dom_attr[o]).attr('data-value');
				
				//procesamos texto
				if(o_type == 'text')
				{
					o_val = o_val.replace(/(\r\n|\n|\r|\t)/gm, "");
					o_val = o_val.replace(/(\")/gm, "&quot;");
					o_val = o_val.replace(/(\')/gm, "&#39;");
					//o_val = $("<textarea/>").html(o_val).html();
				}
				
				dom_attr_array = {
					type:o_type,
					value:o_val
				};
			}
			
			data[i] = {
				type:f_type,
				boxheight:f_boxheight,
				dom:dom_attr_array
			}
		}
		
		if(data.length !== 0)
		{
			dom_form_json.val(JSON.stringify(data));
			
			dom_msg.html($.tmpl('msg', 
			{
				status:200,
				message:'<i class="uk-icon-check-circle"></i> Listo para guardar'
			}));
			
			$('[data-status-message]', document).html($.tmpl('message', {
				status:400,
				message:'<i class="uk-icon-warning uk-text-color-warning"></i> Hay cambios que quizas quería guardar...'
			})).removeClass('uk-hidden');
			
		}else{
			dom_msg.html($.tmpl('msg', 
			{
				status:400,
				message:'No hay nada que guardar'
			}));
		}
	}
	
	//process json
	fn_parse_pageContent = function()
	{
		var dom_data = $('noscript#pageContent').contents(),
			dom_msg = $('[data-page-message]', document),
			dom_container = $('[data-pagecontainer]'),
			dom_json = (dom_data[0].data) ? $.parseJSON(dom_data[0].data) : dom_data[0].data;
		
		//error
		if(dom_json == undefined || dom_json == NaN || !dom_json)
		{
			dom_msg.html($.tmpl('msg',
			{
				status:400,
				message:'No hay contenido'
			}));
			
			dom_container.empty();
			
			return;
		}
		
		if(dom_json.length !== 0)
		{
			dom_container.html('');
			dom_container.after($.tmpl('parsePageContent', {data:dom_json}));
		}
	}
	
	//actions modals
	e_action_handler = function()
	{
		var ele = $(this),
			dom_type = ele.attr('data-action'),
			dom_data_ser = (ele.parents('form').length !== 0) ? ele.parents('form').serialize() : null,
			dom_id = ele.parents('[data-id]').attr('data-id'),
			dom_rid = null;
			
		if(debug) console.log("[>] e_action_handler");
		
		if(dom_type == 'delRelPage') dom_data_ser = null;
		if(dom_type == 'addRelPage')
		{
			$('[name="a_rel"]').removeClass('uk-form-danger');
			
			if($('[name="a_rel"]').val() == '' || $('[name="a_rel"]').val() == undefined || $('[name="a_rel"]').val() == NaN)
			{
				$('[name="a_rel"]').addClass('uk-form-danger');
				return;	
			}
			
			dom_rid = $('[name="a_rel"]').val();
			dom_data_ser = 'vice='+$('[name="a_vice"]').val();
		}
		
		//fn_call_ajax = function(fn_hash, fn_data, fn_before, fn_success)
		fn_call_ajax(dom_type, 
		{
			id:dom_id,
			rid:dom_rid,
			data:dom_data_ser
		}, null, function(d)
		{
			if(debug) console.log(d);
			
			//notify
			UIkit.notify(
			{
			    message : d.message,
			    status  : 'info',
			    timeout : 5000,
			    pos     : 'top-center'
			});
			
			if(d.status == 200)
			{
				if(dom_type == 'delPage') ele.parents('[data-id]').remove();
				if(dom_type == 'delRelPage') ele.parents('[data-id="'+dom_id+'"]').remove();
				if(dom_type == 'addRelPage')
				{
					$.tmpl('relItem', d).appendTo('[data-rel-container]');
				}
			}
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
		
		if(dom_type == 'upPage') $('[data-status-message]', document).addClass('uk-hidden');
		
		//fn_call_ajax = function(fn_hash, fn_data, fn_before, fn_success)
		fn_call_ajax(dom_type, dom_ser, function()
		{
			if(dom_type == 'blogCommentsActions') return;
			if(dom_type == 'upPage') $('[data-status-message]', document).html($.tmpl('preload')).removeClass('uk-hidden');
			dom_form.find('i.uk-hidden').removeClass('uk-hidden');
		}, function(d)
		{
			if(dom_type !== 'upPage') dom_form.find('i').addClass('uk-hidden');
			
			if(debug) console.log(d);
			
			//notify
			UIkit.notify(
			{
			    message : d.message,
			    status  : 'info',
			    timeout : 5000,
			    pos     : 'top-center'
			});
			
			if(dom_type == 'upPage') $('[data-status-message]', document).html($.tmpl('message', d)).removeClass('uk-hidden');
			
			if(d.status == 200)
			{
				if(dom_type == 'addPage')
				{
					//clear modal
					$(':input, textarea', dom_form).not('[type="hidden"]').val('');
					$('option:first', $('select', dom_form)).attr('selected', 'selected');
					
					//close modal
					ele.parents('.uk-modal.uk-open').find('.uk-modal-close').trigger('click');
				
					location.href = "?action=editPage&id="+d.data.id;
				}
				
				if(dom_type == 'blogCommentsActions') location.reload();
			}
		});
	}
	
	admin_pages();
});