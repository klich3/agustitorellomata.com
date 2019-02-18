$(function() 
{
	//page edit product
	$.template('preload', '<i class="uk-icon-spin uk-icon-spinner"></i> Actualizando');
	$.template('message', '{{if status == 200}}<div class="uk-text-success">Cambios guardados <i class="uk-icon-check-circle"></i></div>{{else}}<div class="uk-text-danger">Cambios guardados <i class="uk-icon-warning"></i></div>{{/if}}');
	
	//category
	//id, category_title
	$.template('categoryItem', '<li class="uk-margin uk-margin-small" data-cat-id="${id}"><div class="uk-panel uk-panel-box uk-panel-box-primary uk-sortable bg-green"><div class="uk-grid"><div class="uk-width-2-3"><i class="uk-sortable-handle uk-icon uk-icon-bars uk-margin-small-right"></i> <span class="uk-text-small">${category_title}</span></div><div class="uk-width-1-3"><div class="uk-button-group uk-margin uk-margin-small uk-float-right"><a href="javascript:void(0);" class="uk-button uk-button-primary uk-button-small" data-modal="categoria" title="editar categoría" data-uk-tooltip><i class="uk-icon-pencil"></i></a><a href="javascript:void(0);" class="uk-button uk-button-primary uk-button-small" title="eliminar categoría" data-action="delItemCategoria" data-uk-tooltip><i class="uk-icon-trash"></i></a></div></div></div><div class="uk-width-1-1 uk-placeholder"><ul class="uk-grid uk-grid-small uk-grid-width-1-3 uk-sortable" data-uk-sortable="{group:\'list\'}" data-items-order></ul></div></div></li>');
	
	//item
	//id, item_title, thumb
	$.template('productItem', '<li data-id="${id}" data-gid="${gallery_id}" class="uk-margin-small-bottom"><div class="uk-panel uk-panel-box bg-green"><div class="uk-panel-badge uk-badge"><i class="uk-sortable-handle uk-icon uk-icon-bars uk-margin-small-right"></i> <span class="uk-text-small">${item_title}</span></div><div class="uk-panel-teaser"><a href="{{if thumb}}${thumb}{{else}}'+fn_base+'images/nofoto.png{{/if}}" data-uk-lightbox><img src="{{if thumb}}${thumb}{{else}}'+fn_base+'images/nofoto.png{{/if}}" class="uk-thumbnail-expand uk-margin uk-margin-small" /></a></div><div class="uk-button-dropdown uk-width-1-1" data-uk-dropdown="{mode:\'click\'}"><button class="uk-button uk-width-1-1">acciones <i class="uk-icon-caret-down"></i></button><div class="uk-dropdown uk-dropdown-small"><ul class="uk-nav uk-nav-dropdown"><li><a href="javascript:void(0);" data-gallery><i class="uk-icon-picture-o"></i> galería de imagenes</a></li>'+
	//'<li><a href="javascript:void(0);" data-modal="variaciones"><i class="uk-icon-random"></i> variaciones del producto</a></li>'+
	'<li><a href="javascript:void(0);" data-modal="product" data-uk-tooltip><i class="uk-icon-pencil uk-text-success"></i> edición rápida</a></li><li><a href="'+fn_base+'admin-productos?action=editProduct&id={id}" data-uk-tooltip><i class="uk-icon-pencil uk-text-success"></i> edición completa</a></li><li><a href="javascript:void(0);" data-action="delItemProduct"><i class="uk-icon-trash uk-text-danger"></i> eliminar producto</a></li></ul></div></div></div></li>');
	
	//edit Modal Categoria
	$.template('editModalCategoria', '<form class="uk-form"><div class="uk-form-row"><label for="f_menu_name">Nombre de la categoría (<span class="uk-text-small">solo menu</span>)</label><input type="text" name="f_menu_name" id="f_menu_name" value="${menu_name}" class="uk-width-1-1" data-hash-reference="f_hash" /></div><div class="uk-form-row"><label for="f_hash">Hash (Automático)</label><input type="text" name="f_hash" id="f_hash" value="${hash}" class="uk-width-1-1"/></div><hr/>'+
		'{{each(i, v) lang_data}}<div class="uk-form-row"><label for="f_title_${i}">Título en (<span class="uk-text-bold uk-text-small">${i}</span>)</label><input type="text" name="f_title_${i}" id="f_title_${i}" value="${v}" class="uk-width-1-1" /></div>{{/each}}'+
	'<div class="uk-form-row"><input type="hidden" name="id" value="${id}" /><a href="javascript:void(0);" data-submit="upCategoria" class="uk-button uk-button-primary uk-float-right"><i class="uk-hidden uk-icon-spin uk-icon-spinner"></i> Guardar cambios</a></div></form>');
	
	//edit Modal Product
	$.template('editModalProduct', '{{if product && stock.length !== 0 && sc}}<form class="uk-form"><div class="uk-form-row">Activo <input type="checkbox" name="f_active" value="1" {{if product.active == 1}}checked{{/if}} class="uk-width-1-1" /></div><div class="uk-form-row"><label for="f_menu_title">Título del producto (<span class="uk-text-small">solo menu</span>)</label><input type="text" name="f_menu_title" id="f_menu_title" value="${product.menu_title}" class="uk-width-1-1" data-hash-reference="f_hash" /></div><div class="uk-form-row"><label for="f_hash">Hash (Automático)</label><input type="text" name="f_hash" id="f_hash" value="${product.hash}" class="uk-width-1-1 uk-"/></div><hr/>{{each(i, v) product.lang_data}}<div class="uk-form-row"><label for="f_title_${i}">Título en (<span class="uk-text-bold uk-text-small">${i}</span>)</label><input type="text" name="f_title_${i}" id="f_title_${i}" value="${v}" class="uk-width-1-1" /></div>{{/each}}'+
			
		//stock + colores + tallas + dimensiones
		'<hr><div class="uk-form-row"><div class="uk-grid"><div class="uk-width-1-2"><label for="stock[f_size_id]">Tamaño</label><select name="stock[f_size_id]" class="uk-width-1-1">{{each(si, sv) sc.size}}<option value="${sv.id}" {{if stock[0].size_id == sv.id}}selected{{/if}}>${sv.lang_parse}</option>{{/each}}</select></div><div class="uk-width-1-2"><label for="stock[f_color_id]">Color</label><select name="stock[f_color_id]" class="uk-width-1-1">{{each(ci, cv) sc.color}}<option value="${cv.id}" {{if stock[0].color_id == cv.id}}selected="true"{{/if}}>${cv.lang_parse}</option>{{/each}}</select></div><div class="uk-width-1-2"><label for="stock[f_precio_coste]">Precio coste</label><input type="number" name="stock[f_precio_coste]" value="${stock[0].precio_coste}" class="uk-width-1-1" /></div><div class="uk-width-1-2"><label for="stock[f_precio_venta]">Precio final (<strong class="uk-text-small">venta</strong>)</label><input type="number" name="stock[f_precio_venta]" value="${stock[0].precio_venta}" class="uk-width-1-1" /></div><div class="uk-width-1-2"><label for="stock[f_stock_min]">Stock minimo</label><input type="number" name="stock[f_stock_min]" value="${stock[0].stock_min}" class="uk-width-1-1" /></div><div class="uk-width-1-2"><label for="stock[f_stock_base]">Stock base</label><input type="number" name="stock[f_stock_base]" value="${stock[0].stock_base}" class="uk-width-1-1" /></div><div class="uk-width-1-1"><label for="stock[f_stock_count]">Stock actual</label><input type="number" name="stock[f_stock_count]" value="${stock[0].stock_count}" class="uk-width-1-1" /></div></div></div><div class="uk-form-row"><input type="hidden" name="id" value="${product.id}" /><a href="javascript:void(0);" data-submit="upProduct" class="uk-button uk-button-primary uk-float-right"><i class="uk-hidden uk-icon-spin uk-icon-spinner"></i> Guardar cambios</a></div>{{else}}<div class="uk-alert uk-alert-danger uk-width-1-1">Algo a ido mal no se han cargado datos basicos del producto, cierre esta ventana y vuelva a intentarlo de nuevo.</div>{{/if}}'+
		
		'</form>');
	
	//administracion color / size
	$.template('admColoSizeListItem', '<li data-id="${id}" data-lang-json=\'${lang_data}\'><div class="uk-grid"><div class="uk-width-6-10">${lang_parse}</div><div class="uk-width-4-10"><div class="uk-button-group uk-float-right"><a href="javascript:void(0);" data-action="edit${type}Item" class="uk-button uk-button-small"><i class="uk-icon-pencil"></i></a><a href="javascript:void(0);" data-action="del${type}Item" class="uk-button uk-button-small uk-button-danger"><i class="uk-icon-trash"></i></a></div></div></div></li>');
	
	$.template('admColorSize', '<div class="uk-grid uk-grid-small uk-width-1-1 uk-margin"><div class="uk-width-1-2"><p>Lista de colores</p><ul class="uk-list uk-list-line uk-list-striped uk-width-1-1" data-admv-color>{{each(i, v) color}}'+
		'{{tmpl({id:v.id, lang_parse:v.lang_parse, type:"Color", lang_data:v.lang_data}) "admColoSizeListItem"}}'+
	'{{/each}}</ul></div><div class="uk-width-1-2"><p>Lista de tamaños (tallas)</p><ul class="uk-list uk-list-line uk-list-striped uk-width-1-1" data-admv-size>{{each(i, v) size}}'+
		'{{tmpl({id:v.id, lang_parse:v.lang_parse, type:"Size", lang_data:v.lang_data}) "admColoSizeListItem"}}'+
	'{{/each}}</ul></div></div><hr/>'+

	'<form class="uk-form uk-width-1-1"><div class="uk-form-row"><span class="uk-text-bold">Crear un color o tamaño / talla</span></div><div class="uk-form-row"><select class="uk-width-1-1" name="f_ctAdmType"><option value="color">color</option><option value="size">tamaño / talla</option></select></div><div class="uk-form-row"><ul class="uk-grid uk-grid-width-1-${lang.length}">{{each(i, v) lang}}<li><input type="text" name="f_lang_data[${v}]" value="" placeholder="Idioma: (${v})" class="uk-width-1-1" /></li>{{/each}}</ul></div><div class="uk-form-row"><span class="uk-badge uk-badge-notification">Nota:</span> Es importante y recomendable borrar contenido (<i class="uk-icon-eraser"></i>) antes de crear nuevo item.</div><div class="uk-form-row"><input type="hidden" name="id" value="" /><div class="uk-button-group uk-float-right"><a href="javascript:void(0);" data-action="cleanForm" class="uk-button uk-button-success"><i class="uk-icon-eraser"></i></a><a href="javascript:void(0);" data-action="ctUpColorSize" class="uk-button uk-button-primary"><i class="uk-icon-save"></i></a></div></div></form>');
	
	var admin_productos = function() 
	{
		if (debug) console.log("[plugin] admin-productos");
		
		$(document).on('stop.uk.sortable', '[data-category-container]', fn_order);
		$(document).on('change.uk.sortable', '[data-producto-container]', fn_uncat_items);
		$(document).on('change.uk.sortable', '[data-items-order]', fn_cat_items);
		$(document).on('click', '[data-submit]', e_submit_handler);
		$(document).on('click', '[data-action]', e_action_handler);
		$(document).on('click', '[data-modal]', e_editModal_handler);
		
		//stock variacion
		$(document).on('click', '[data-stock="variaciones"]', e_variacion_handler);
	}
	
	//variacion de stock
	e_variacion_handler = function()
	{
		var ele = $(this),
			dom_modal = $('#editModal', document),
			modal = UIkit.modal(dom_modal, {modal: false});
		
		if(debug) console.log('[>] e_variacion_handler');
		
		if(!modal.isActive())
		{
			modal.show();
			
			/*
			dom_modal.find('[data-modal-container]').html($.tmpl("galleryModalControl", 
			{
				
			}));
			*/
		}
	}
	
	//mostramos modal con form de editar
	e_editModal_handler = function()
	{
		//close dropdown
		$('.uk-button-dropdown.uk-open').removeClass('uk-open');
		
		var ele = $(this),
			dom_type = ele.attr('data-modal'),
			dom_modal = $('#editModal', document),
			dom_container = dom_modal.find('[data-modal-container]'),
			modal = UIkit.modal(dom_modal, {modal: false});
		
		if(!modal.isActive())
		{
			modal.show();
			
			switch(dom_type)
			{
				case "admVariedad":
					fn_call_ajax("getColorSizes", null, function()
					{
						$('[data-modal-container]').html($.tmpl('modalPreloader'));
					}, function(d)
					{
						if(debug) console.log(d);
						
						if(d.status == 200)
						{ 
							$('[data-modal-container]').html($.tmpl('admColorSize', d.data));
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
				break;
				
				case 'variaciones':
					$('[data-modal-container]').html($.tmpl('modalPreloader'));
					return;
				break;
				
				case "product":
				case "categoria":
					var dom_id = (dom_type == "product") ? ele.parents('[data-id]').attr('data-id') : ele.parents('[data-cat-id]').attr('data-cat-id');
			
					//fn_call_ajax = function(fn_hash, fn_data, fn_before, fn_success)
					fn_call_ajax("getEditData", {
						'id':dom_id,
						'type':dom_type
					}, function()
					{
						$('[data-modal-container]').html($.tmpl('modalPreloader'));
					}, function(d)
					{
						if(debug) console.log(d);
						
						if(d.status == 200)
						{ 
							if(dom_type == 'categoria') $('[data-modal-container]').html($.tmpl('editModalCategoria', d.data));
							if(dom_type == 'product') $('[data-modal-container]').html($.tmpl('editModalProduct', d.data));
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
				break;
			}
		}
	}
		
	//ordenamos categorias y productos
	fn_order = function(e)
	{
		var ele = $(this),
			dom_cats = $('[data-category-container]').find('[data-cat-id]'),
			data_order = [];
		
		if(dom_cats.length !== 0) for(var c in dom_cats)
		{
			if(!$.isNumeric(c)) continue;
			
			var dom_item = $(dom_cats[c]),
				dom_id = dom_item.attr('data-cat-id'),
				dom_order = dom_item.index();
			
			data_order.push(
			{
				'id':dom_id,
				'or':dom_order
			});
		}
		
		//fn_call_ajax = function(fn_hash, fn_data, fn_before, fn_success)
		if(data_order.length !== 0) fn_call_ajax('orCategoria', {data:data_order}, null, null);
	}
	
	//movemos items para quitar categoria
	fn_uncat_items = function()
	{
		var ele = $(this),
			dom_items = ele.find('li[data-id]'),
			dom_uncat_ids = [];
		
		if(dom_items.length !== 0) for(var i in dom_items)
		{
			if(!$.isNumeric(i)) continue;
			
			var dom_id = $(dom_items[i]).attr('data-id');
			
			dom_uncat_ids.push(dom_id);
		}
		
		//fn_call_ajax = function(fn_hash, fn_data, fn_before, fn_success)
		fn_call_ajax('uncatProduct', {
			'ids':dom_uncat_ids
		}, null, null);
	}
	
	//asignamos una categoria al producto
	fn_cat_items = function()
	{
		var ele = $(this),
			dom_items = ele.find('li[data-id]'),
			dom_cat_id = ele.parents('[data-cat-id]').attr('data-cat-id');
			dom_data = [];
		
		if(dom_items.length !== 0) for(var i in dom_items)
		{
			if(!$.isNumeric(i)) continue;
			
			var dom_item = $(dom_items[i]),
				dom_id = dom_item.attr('data-id'),
				dom_order = dom_item.index();
			
			dom_data.push(
			{
				'id':dom_id,
				'or':dom_order,
				'cat_id':dom_cat_id
			});
		}
		
		//fn_call_ajax = function(fn_hash, fn_data, fn_before, fn_success)
		fn_call_ajax('catProduct', {
			'data':dom_data
		}, null, function(d)
		{
			if(debug) console.log(d);
		});
	}
	
	e_action_handler = function(e)
	{
		var ele = $(this),
			dom_action_type = ele.attr('data-action'),
			dom_item_id = null,
			dom_type_mods = null,
			dom_form_ser = null;
		
		switch(dom_action_type)
		{
			case "cleanForm":
				$(':input, textarea', ele.parents('form')).not('select').val('');
				return;
			break;
			
			case "delItemProduct":
				dom_item_id = ele.parents('[data-id]').attr('data-id');
			break;
			
			case "delItemCategoria":
				dom_item_id = ele.parents('[data-cat-id]').attr('data-cat-id');
			break;
			
			case "ctUpColorSize":
				dom_item_id = ele.parents('[data-id]').attr('data-id');
				dom_type_mods = ele.parents('form').find('[name="f_ctAdmType"]').val();
				dom_form_ser = ele.parents('form').serialize();
			break;
			
			case "editSizeItem":
			case "editColorItem":
			case "delSizeItem":
			case "delColorItem":
				if(ele.parents('[data-admv-color]').length !== 0) dom_type_mods = 'color';
				if(ele.parents('[data-admv-size]').length !== 0) dom_type_mods = 'size';
				dom_item_id = ele.parents('[data-id]').attr('data-id');
			break;
		}
		
		if(dom_action_type == 'editSizeItem' || dom_action_type == 'editColorItem')
		{
			dom_lang_data = $.parseJSON(ele.parents('[data-id]').attr('data-lang-json'));
			
			ele.parents('[data-modal-container]').find('input[name="id"]').val(dom_item_id);
			ele.parents('[data-modal-container]').find('select[name="f_ctAdmType"] option[value="'+dom_type_mods+'"]').attr('selected', true);
			
			var fn_languages = $.parseJSON(st_languages);
			if(fn_languages.length !== 0) for(var l in fn_languages)
			{
				if(!$.isNumeric(l)) continue;
				ele.parents('[data-modal-container]').find('[name="f_lang_data['+fn_languages[l]+']"]').val(dom_lang_data[fn_languages[l]]);
			}
			return;
		}
		
		//fn_call_ajax = function(fn_hash, fn_data, fn_before, fn_success)
		fn_call_ajax(dom_action_type, {
			'id':dom_item_id,
			'type':dom_type_mods,
			'data':dom_form_ser
		}, null, function(d)
		{
			if(debug) console.log(d);
			
			if(d.status == 200)
			{
				switch(dom_action_type)
				{
					case "delItemProduct":
						ele.parents('[data-id]').remove();
					break;
					
					case "delItemCategoria":
						//mover elementos a desactivados
						var dom_in_category = ele.parents('[data-cat-id]').find('[data-items-order] li');
						
						if(dom_in_category.length !== 0) dom_in_category.appendTo('[data-producto-container]');
						
						$('[data-producto-container]').trigger('change.uk.sortable');
						
						ele.parents('[data-cat-id]').remove();
					break;
					
					case "delColorItem":
					case "delSizeItem":
						ele.parents('li').remove();
					break;
					
					case "ctUpColorSize":
						var fn_dom_by_type = (dom_type_mods == 'color') ? '[data-admv-color]' : '[data-admv-size]',
							fn_tmpl_type = (dom_type_mods == 'color') ? 'Color' : 'Size';
						
						if(!d.data.create) $(fn_dom_by_type+' [data-id="'+d.data.id+'"]', '[data-modal-container]').remove();
						
						//creamos uno nuevo
						$.tmpl('admColoSizeListItem', 
						{
							'id':d.data.id,
							'lang_data':d.data.lang_data,
							'lang_parse':d.data.lang_parse,
							'type':fn_tmpl_type
						}).appendTo(fn_dom_by_type);
						
							
						//clean up
						$(':input, textarea', ele.parents('form')).not('selected').val('');
						
						$('#editModal').on('hide.uk.modal', function()
						{
							location.reload();
						});
					break;
				}
			}
			
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
			
		console.log("[>] submit form ["+dom_type+"]");
		
		if(dom_type == 'upProducto') $('[data-status-message]', document).addClass('uk-hidden');
		
		//fn_call_ajax = function(fn_hash, fn_data, fn_before, fn_success)
		fn_call_ajax(dom_type, dom_ser, function()
		{
			if(dom_type == 'upProducto') $('[data-status-message]', document).html($.tmpl('preload')).removeClass('uk-hidden');
			dom_form.find('i.uk-hidden').removeClass('uk-hidden');
		}, function(d)
		{
			if(dom_type !== 'upProducto') dom_form.find('i').addClass('uk-hidden');
			
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
				switch(dom_type)
				{
					case "addCategoria":
						//añadir item al stage
						$.tmpl('categoryItem', d.data).appendTo('[data-category-container]');
					break;
					
					case "addProducto":
						//añadir item al stage
						$.tmpl('productItem', d.data).appendTo('[data-producto-container]');
						location.reload();
					break;
					
					case "upCategoria":
					case "upProduct":
						var modal = UIkit.modal($('#editModal', document), {modal: false});
						
						if(modal.isActive()) modal.show();
						location.reload();
					break;
					
					case "upProducto":
						$('[data-status-message]', document).html($.tmpl('message', d)).removeClass('uk-hidden');
					break;
				}
				
				if(dom_type !== 'upProducto')
				{
					//clear modal
					$(':input, textarea', dom_form).not('[type="hidden"]').val('');
					$('option:first', $('select', dom_form)).attr('selected', 'selected');
					
					//close modal
					ele.parents('.uk-modal.uk-open').find('.uk-modal-close').trigger('click');
				}
			}
		});
	}
	
	admin_productos();
});