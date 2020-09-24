$(function() 
{
	
	var admin_cliente = function() 
	{
		if (debug) console.log("[plugin] admin-usuarios");
		
		$(document).on('click', '[data-action]', e_action_handler);
		$(document).on('click', '[data-submit]', e_submit_handler);
	}
	
	//actions modals
	e_action_handler = function()
	{
		var ele = $(this),
			dom_type = ele.attr('data-action'),
			dom_data_ser = (ele.parents('form').length !== 0) ? ele.parents('form').serialize() : null,
			dom_id = ele.parents('[data-id]').attr('data-id'),
			dom_user_id = ele.parents('[data-user-id]').attr('data-user-id');
			
		console.log("[>] e_action_handler");
		
		//fn_call_ajax = function(fn_hash, fn_data, fn_before, fn_success)
		fn_call_ajax(dom_type, 
		{
			id:dom_id,
			user_id:dom_user_id,
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
				switch(dom_type)
				{
					case "delUsuarios":
						ele.parents('[data-id]').remove();
					break;
					
					case "upUsuarios":
						var dom_email = ele.parents('[data-id]').find('input[name^="f_user_email"]').val();
						$('input[name^="f_email"]', document).val(dom_email);
					break;
					
					case "upPassUsuarios":
						//clear fields
						$(':input, textarea', ele.parents('form')).not('[type="hidden"]').val('');
					break;
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
			
		console.log("[>] submit form ["+dom_type+"]");
		
		//fn_call_ajax = function(fn_hash, fn_data, fn_before, fn_success)
		fn_call_ajax(dom_type, dom_ser, function()
		{
			dom_form.find('i.uk-hidden').removeClass('uk-hidden');
		}, function(d)
		{
			dom_form.find('i').addClass('uk-hidden');
			
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
				//clear modal
				$(':input, textarea', dom_form).not('[type="hidden"]').val('');
				$('option:first', $('select', dom_form)).attr('selected', 'selected');
				
				//close modal
				ele.parents('.uk-modal.uk-open').find('.uk-modal-close').trigger('click');
				
				switch(dom_type)
				{
					case "addUsuarios":
						location.href = "?action=editUsuarios&id="+d.data.id;
					break;
				}
			}
		});
	}
	
	admin_cliente();
});