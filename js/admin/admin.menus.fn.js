$(function() 
{
	$.template('msg', '<div class="uk-text-{{if status == 200}}success{{else}}danger{{/if}}">{{html message}} {{if status == 200}}<i class="uk-icon-check-circle"></i>{{else}}<i class="uk-icon-times-circle"></i>{{/if}}</div>');
	
	//menu item
	$.template('menuItemTMPL', '{{if data}}<li data-item-id="${data.id}" data-item-order="${data.or}" data-item-url="${data.p_url}" data-page-id="${data.pid}" data-item-title="${data.p_title}" class="uk-nestable-item parent">'+
	'<div class="uk-nestable-panel {{if data.active=="0"}}bg-grey{{/if}}">'+
		'<div class="uk-nestable-handle uk-float-left"><i class="uk-nestable-handle uk-icon-bars uk-margin-small-right"></i></div>'+
		
		'<div class="uk-grid uk-grid-small">'+
			//titulos
			'<div class="uk-width-2-3"><div class="noselect">{{if data.title!==""}}${data.title}{{else}}${data.p_title}{{/if}}<br/><i class="uk-text-small">{{if data.url!==""}}${data.url}{{else}}${data.p_url}{{/if}}</i></div></div>'+
			
			//butones
			'<div class="uk-width-1-3"><div class="uk-button-group uk-float-right"><a href="javascript:void(0);" class="uk-button uk-button-small uk-button-primary" data-action="editItem"><i class="uk-icon-pencil"></i></a><a class="uk-button uk-button-danger uk-button-small" href="javascript:void(0);" data-action="delItem"><i class="uk-icon-trash"></i></a></div></div></div>'+
	'</div></li>{{/if}}');
	
	var admin_menus = function() 
	{
		if (debug) console.log("[plugin] admin-menus");
		
		$(document).on('change.uk.nestable', '.uk-nestable[data-type]', e_action_handler);
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
			dom_rid = null,
			args = {
				type:dom_type,
				id:dom_id,
				rid:dom_rid,
				data:dom_data_ser
			};
			
		if(debug) console.log("[>] e_action_handler");
		
		//menu order parent id
		if(ele.hasClass('uk-nestable'))
		{
			var dom_order_parent = [],
				dom_items = $(this).find('li');
			
			if(dom_items.length > 0) for(var i in dom_items)
			{
				if (!$.isNumeric(i)) continue;
				
				var dom_parent = $(dom_items[i]).parents('ul li').data('item-id');
				
				dom_order_parent.push(
				{
					id:$(dom_items[i]).data('item-id'),
					or:$(dom_items[i]).index(),
					pid:(dom_parent !== undefined) ? dom_parent : 0
				});
			}
			
			dom_type = 'parentItem';
			
			args = {
				type:dom_type,
				data:dom_order_parent
			};
		}
		
		if(dom_type == 'cleanForm')
		{
			var dom_form = $('form[data-type="edit"]');
			
			$(':input, textarea', dom_form).not('[type="hidden"]').val('');
			$('option:first', $('select', dom_form)).attr('selected', 'selected');
			$(':input:checked').prop('checked', false);
			$(':input[name="id"]').val('');
			
			return;
		}
		
		if(dom_type == 'delItem')
		{
			var dom_id = ele.parents('li[data-item-id]').attr('data-item-id');
			
			args = {
				type:dom_type,
				id:dom_id
			};
		}
		
		//recoge para actualizar
		if(dom_type == 'editItem')
		{
			var dom_form = $('form[data-type="edit"]'),
				dom_pid = ele.parents('li').attr('data-page-id'),
				dom_active = (ele.parents('li').attr('data-item-active') == 1) ? true : false;
			
			dom_form.find('[name="id"]').val(ele.parents('li').attr('data-item-id'));
			dom_form.find('[name="m_pid"]').val(dom_pid);
			dom_form.find('[name="m_title"]').val(ele.parents('li').attr('data-item-title'));
			dom_form.find('[name="m_url"]').val(ele.parents('li').attr('data-item-url'));
			dom_form.find('[name="m_active"]').prop('checked', dom_active);
			
			return;
		}
		
		//fn_call_ajax = function(fn_hash, fn_data, fn_before, fn_success)
		fn_call_ajax('manageMenus', args, null, function(d)
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
				if(dom_type == 'delMenus') ele.parents('[data-id]').remove();
				if(dom_type == 'parentItem') $('[data-status]').removeClass('uk-hidden').html($.tmpl('msg', d));
				if(dom_type == 'delItem')
				{
					if($('[data-type="container"] li[data-item-id="'+dom_id+'"]').find('ul li').length == 0)
					{
						$('[data-type="container"] li[data-item-id="'+dom_id+'"]').remove();
					}else{
						location.reload();
					}
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
		
		//fn_call_ajax = function(fn_hash, fn_data, fn_before, fn_success)
		fn_call_ajax('manageMenus', {
			type:dom_type,
			data:dom_ser
		}, function()
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
				if(dom_type == 'addMenus')
				{
					//clear modal
					$(':input, textarea', dom_form).not('[type="hidden"]').val('');
					$('option:first', $('select', dom_form)).attr('selected', 'selected');
					
					//close modal
					ele.parents('.uk-modal.uk-open').find('.uk-modal-close').trigger('click');
				
					location.href = "?action=editMenus&id="+d.data.id;
				}
				
				if(dom_type == 'addUpMenuItem')
				{
					$(document).off('change.uk.nestable', '.uk-nestable[data-type]').on('change.uk.nestable', '.uk-nestable[data-type]', e_action_handler);
					
					if($('form[data-type="edit"] :input[name="id"]').val() !== '')
					{
						location.reload();
						return;
					}
					
					$($.tmpl('menuItemTMPL', d)).appendTo('[data-type="container"]');
					
					//clear modal
					$(':input, textarea', dom_form).not('[type="hidden"]').val('');
					$('option:first', $('select', dom_form)).attr('selected', 'selected');
					$(':input:checked').prop('checked', false);
					
					return;
				}
			}
		});
	}
	
	admin_menus();
});