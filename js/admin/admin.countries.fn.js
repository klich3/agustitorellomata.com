$(function() 
{
	//page edit product
	$.template('preload', '<i class="uk-icon-spin uk-icon-spinner"></i> Actualizando');
	$.template('message', '{{if status == 200}}<div class="uk-text-success">Cambios guardados <i class="uk-icon-check-circle"></i></div>{{else}}<div class="uk-text-danger">Cambios guardados <i class="uk-icon-warning"></i></div>{{/if}}');
	
	var admin_countries = function() 
	{
		if (debug) console.log("[plugin] admin-countries");
		
		$(document).on('click', '[data-action]', e_action_handler);
	}
	
	e_action_handler = function()
	{
		var ele = $(this),
			dom_type = ele.attr('data-action'),
			dom_form = ele.parents('form'),
			dom_ser = dom_form.serialize();
		
		//clean message
		dom_form.find('[data-status-message]').html('');
				
		fn_call_ajax(dom_type, {
			data:dom_ser
		}, function()
		{
			dom_form.find('[data-status-message]').html($.tmpl('preload'));
		}, function(d)
		{
			if(debug) console.log(d);
			dom_form.find('[data-status-message]').html($.tmpl('message', d));
		});
	}
		
	admin_countries();
});