$(function() 
{
	$.template('message', '{{if status == 200}}<div class="uk-text-success">Cambios guardados <i class="uk-icon-check-circle"></i></div>{{else}}<div class="uk-text-danger">Cambios guardados <i class="uk-icon-warning"></i></div>{{/if}}');
	
	//types
	$.template('editTypeForm', '{{if status == 200}}<form class="uk-form"><div class="uk-form-row"><label for="f_title">Nombre de servicio</label><input type="text" name="f_title" id="f_title" value="${data.title}" class="uk-width-1-1"/></div><hr/>'+
        '{{if data.lang_title && data.lang_title.length !== 0}} {{each(i, v) data.lang_title}}<div class="uk-form-row"><label for="f_title_${v.code}">Título en (<span class="uk-text-bold uk-text-small">${v.code}</span>)</label><input type="text" name="f_title_${v.code}" id="f_title_${v.code}" value="${v.title}" class="uk-width-1-1" /></div>{{/each}} {{else}} {{each(i, v) data.lang}}<div class="uk-form-row"><label for="f_title_${v}">Título en (<span class="uk-text-bold uk-text-small">${v}</span>)</label><input type="text" name="f_title_${v}" id="f_title_${v}" value="" class="uk-width-1-1" /></div>{{/each}} {{/if}}'+
		'<div class="uk-form-row"><input type="hidden" name="s_id" value="${data.s_id}" /><a href="javascript:void(0);" data-action="upCompanyType" class="uk-button uk-button-primary uk-float-right"><i class="uk-hidden uk-icon-spin uk-icon-spinner"></i> Guardar</a></div></form>'+
	'{{else}}<h1>Error</h1><p>El formulario no se ha cargado bien. Cierre y vuelva intentar los pasos.</p>{{/if}}');
    
	$.template('typeRow', '<li data-id="${id}"><div class="uk-grid uk-grid-small"><div class="uk-width-1-10"><input type="checkbox" name="f_type_active[${id}]" value="1"/></div><div class="uk-width-7-10"><span class="uk-text-small">${title} (<i>${serv_title}</i>)</span></div><div class="uk-width-2-10"><div class="uk-button-group uk-float-right"><a href="javascript:void(0);" data-action="editCompanyType" class="uk-button uk-button-small uk-button-success"><i class="uk-icon-pencil"></i></a><a href="'+fn_base_script+'?action=detailsTarifas&s_id=${s_id}&t_id=${id}" class="uk-button uk-button-small uk-button-primary"><i class="uk-icon-usd"></i></a><a href="javascript:void(0);" data-action="delCompanyType" class="uk-button uk-button-danger uk-button-small"><i class="uk-icon-trash"></i></a></div></div></div></li>');
	
	//prices
	$.template('editPriceForm', '{{if status == 200}}'+
		'<form class="uk-form"><div class="uk-form-row"><label for="f_country">País</label><select name="f_country" class="uk-width-1-1">'+
			'{{if data.country_list}} {{each(i, v) data.country_list}}<option value="${id}" {{if data.c_id == v.id}}selected{{/if}}>${v.country_name}</option>{{/each}} {{/if}}'+
		'</select></div><div class="uk-form-row"><label for="f_peso">Peso (Kg)</label><input type="number" min="0" name="f_peso" value="${data.kg}" class="uk-width-1-1" /></div><div class="uk-form-row"><label for="f_price">Precio</label><input type="number" min="0" name="f_price" value="${data.precio}" class="uk-width-1-1" /></div><div class="uk-form-row"><input type="hidden" name="id" value="${data.id}" /><a href="javascript:void(0);" data-action="upPrice" class="uk-button uk-button-small uk-button-success uk-float-right">Actualizar</a></div></form>'+
	'{{else}}<h1>Error</h1><p>El formulario no se ha cargado bien. Cierre y vuelva intentar los pasos.</p>{{/if}}');
	
	$.template('priceTableRow', '{{if data}}<tr data-id="${data.id}" class="bg-green"><td class="uk-text-small">${data.country_name}</td><td class="uk-text-small uk-text-center">${data.f_peso}</td><td class="uk-text-small uk-text-center">${data.f_price}</td><td><div class="uk-button-group uk-float-right"><a href="javascript:void(0);" data-action="getPriceEdit" class="uk-button uk-button-success uk-button-small"><i class="uk-icon-pencil"></i></a><a href="javascript:void(0);" data-action="delPrice" class="uk-button uk-button-danger uk-button-small"><i class="uk-icon-trash"></i></a></div></td></tr>{{/if}}');
	
	var admin_shipping = function() 
	{
		if (debug) console.log("[plugin] admin-shipping");
		
		$(document).on('click', '[data-action]', e_action_handler);
	}
	
	e_action_handler = function(e)
	{
		var ele = $(this),
			dom_action_type = ele.attr('data-action'),
			dom_form = ele.parents('form'),
			dom_form_ser = dom_form.serialize(),
			dom_item_id = null;
		
		if(dom_action_type == 'editCompanyType' || dom_action_type == 'getPriceEdit')
		{
			dom_item_id = ele.parents('[data-id]').attr('data-id');
			
			var dom_modal = $('#editModal', document),
				dom_container = dom_modal.find('[data-modal-container]'),
				modal = UIkit.modal(dom_modal);
				
			if(!modal.isActive()) modal.show();
		}
		
		if(dom_action_type == 'delCompany' || dom_action_type == 'delCompanyType' || dom_action_type == 'getPriceEdit' || dom_action_type == 'delPrice') dom_item_id = ele.parents('[data-id]').attr('data-id');
		
		//fn_call_ajax = function(fn_hash, fn_data, fn_before, fn_success)
		fn_call_ajax('sippingManage', {
			'type':dom_action_type,
			'data':dom_form_ser,
			'id':dom_item_id
		}, null, function(d)
		{
			if(debug) console.log(d);
			
			if(d.status == 200)
			{
				if(dom_action_type == 'addCompany') location.href = "?action=editCompany&id="+d.data.id;
				if(dom_action_type == 'delCompany') location.href = fn_base_script+'admin-shipping';
				
				if(dom_action_type == 'addCompanyType')
				{
					//close modal
					ele.parents('.uk-modal.uk-open').find('.uk-modal-close').trigger('click');
					
					//add to stage
					$.tmpl('typeRow', d.data).appendTo('[data-type-container]');
				}
				
				if(dom_action_type == 'delPrice') ele.parents('tr[data-id="'+dom_item_id+'"]').remove();
				
				if(dom_action_type == 'editCompanyType') $('[data-modal-container]').html($.tmpl('editTypeForm', d));
				if(dom_action_type == 'upCompanyType' || dom_action_type == 'upPrice') location.reload();
				if(dom_action_type == 'delCompanyType') ele.parents('li[data-id="'+dom_item_id+'"]').remove();
				if(dom_action_type == 'upCompany')  dom_form.find('[data-message]').html($.tmpl('message', d));
				
				if(dom_action_type == 'getPriceEdit') $('[data-modal-container]').html($.tmpl('editPriceForm', d));
				if(dom_action_type == 'addPrice')
				{
					//add to stage
					$.tmpl('priceTableRow', d).prependTo('[data-list-container]');
					
					//message
					dom_form.find('[data-message]').html($.tmpl('message', d));
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
	
	admin_shipping();
});