$(function() 
{
	$.template('message', '{{if status == 200}}<div class="uk-text-success">Cambios guardados <i class="uk-icon-check-circle"></i></div>{{else}}<div class="uk-text-danger">Ui, no se ha podido guardar. <i class="uk-icon-warning"></i></div>{{/if}}');
	$.template('table_row', '{{if data}}<tr data-year="${data.a_year}"><td class="{bg_class}"><input type="text" name="i_iva[${data.a_year}]" value="${data.a_year}" class="uk-width-1-1"/></td><td><input type="text" name="i_iva[${data.a_year}][iva]" value="${data.a_iva}" class="uk-width-1-1" /></td><td><input type="text" name="i_iva[${data.a_year}][irpf]" value="${data.a_irpf}" class="uk-width-1-1" /></td><td><a href="javascript:void(0);" data-action="delIva" class="uk-button uk-button-small uk-button-danger uk-float-right"><i class="uk-icon-trash"></i></a></td></tr>{{/if}}');
		
	var admin_iva = function() 
	{
		if (debug) console.log("[plugin] admin-iva");
		
		$(document).on('click', '[data-action]', e_action_handler);
	}
	
	//to actions
	e_action_handler = function()
	{
		var ele = $(this),
			dom_type = ele.attr('data-action'),
			dom_data_ser = ele.parents('form').serialize(),
			dom_data_year = ele.parents('tr[data-year]').attr('data-year');
		
		$('[data-status-message]', ele.parents('form')).addClass('uk-hidden');
			
		//fn_call_ajax = function(fn_hash, fn_data, fn_before, fn_success)
		fn_call_ajax(dom_type, 
		{
			'year':dom_data_year,
			'data':dom_data_ser
		}, null, function(d)
		{
			if(debug) console.log(d);
			
			$('[data-status-message]', ele.parents('form')).html($.tmpl('message', d)).removeClass('uk-hidden');
			
			if(d.status == 200)
			{
				if(dom_type == 'addIva')
				{
					//clear
					$(':input, textarea', ele.parents('form')).not('[type="hidden"]').val('');
					$.tmpl('table_row', d).appendTo('table tbody[data-iva-containers]');
				}
				
				if(dom_type == 'delIva') ele.parents('tr[data-year]').remove();
			}
		});
	}
	
	admin_iva();
});