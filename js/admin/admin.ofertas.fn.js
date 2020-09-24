$(function() 
{
	//page edit product
	$.template('preload', '<i class="uk-icon-spin uk-icon-spinner"></i> Actualizando');
	$.template('message', '{{if status == 200}}<div class="uk-text-success">Cambios guardados <i class="uk-icon-check-circle"></i></div>{{else}}<div class="uk-text-danger">Cambios guardados <i class="uk-icon-warning"></i></div>{{/if}}');
	
	//edit oferta
	$.template('editModalOferta', '<form class="uk-form"><div class="uk-form-row"><label for="f_active"> Oferta visible? </label><br/><input type="checkbox" name="f_active" value="1" {{if active == 1}}checked="true"{{/if}} /> Visible</div><div class="uk-form-row"><label for="f_title">Titulo:</label><input type="text" name="f_title" id="f_title" value="${title}" class="uk-width-1-1"/></div><div class="uk-form-row"><label for="f_page_name">Tipo:</label><select name="f_type" class="uk-width-1-1"><option value="gen" {{if p_id=="0"}}selected{{/if}}>General</option><option value="prd" {{if p_id!=="0"}}selected{{/if}}>Producto</option></select></div><div class="uk-form-row"><label for="f_product">Producto:</label><div class="uk-width-1-1 uk-margin uk-margin-small"><div class="uk-badge uk-badge-notification">Nota:</div> Dejar en blanco si es oferta general.</div><select name="f_product" class="uk-width-1-1"><option value="">-- Seleccionar --</option>'+
			        
			        '{{if select_products}}{{each(i, o) select_products}}<option value="${o.id}" {{if o.id==p_id}}selected{{/if}}>${o.item_title}</option>{{/each}}{{/if}}'+
			        
			        '</select></div><div class="uk-form-row"><label for="f_percent">Porciento de descuento:</label><input type="number" name="f_percent" id="f_percent" value="${oferta_value}" class="uk-width-1-1"/></div><div class="uk-form-row"><label for="f_code">Código de promoción:</label><input type="text" name="f_code" id="f_code" value="${code}" class="uk-width-1-1"/></div><div class="uk-form-row"><label for="f_max">Número de veces que se puede usar el código de promoción:</label><input type="number" name="f_max" id="f_max" value="${max}" class="uk-width-1-1"/></div><div class="uk-form-row"><label for="f_desc">Descripción:</label><textarea class="uk-width-1-1" role="3" name="f_desc">${desc}</textarea></div>'+
			    	
			    	//tab
			    	'<hr/><div class="uk-form-row"><strong>Tab - home</strong><div class="uk-grid"><div class="uk-width-1-2"><input type="checkbox" name="f_tab_active" value="1" {{if tab_active == 1}}checked="true"{{/if}}/> Mostrar oferta en el tab?</div><div class="uk-width-1-2"><input type="checkbox" name="f_tab_sendemail" value="1" {{if tab_sendemail == 1}}checked="true"{{/if}}/> Enviar email con oferta al cliente?</div></div></div>'+    
			        
				'<div class="uk-form-row"><input type="hidden" name="id" value="${id}" /><a href="javascript:void(0);" data-submit="upOferta" class="uk-button uk-button-primary uk-float-right"><i class="uk-hidden uk-icon-spin uk-icon-spinner"></i> Guardar cambios</a></div></form>');
	
	var admin_ofertas = function() 
	{
		if (debug) console.log("[plugin] admin-ofertas");
		
		$(document).on('click', '[data-action]', e_action_handler);
		$(document).on('click', '[data-submit]', e_submit_handler);
	}
	
	//actions 
	e_action_handler = function()
	{
		var ele = $(this),
			dom_id = ele.parents('[data-id]').attr('data-id'),
			dom_type = ele.attr('data-action'),
			dom_ser = null;
			
		console.log("[>] e_action_handler");
		
		switch(dom_type)
		{
			case 'delOferta':
				//fn_call_ajax = function(fn_hash, fn_data, fn_before, fn_success)
				fn_call_ajax('ofertasManage', {
					type:dom_type,
					data:dom_ser,
					id:dom_id
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
							case "delOferta":
								$('table tr[data-id="'+dom_id+'"]', document).remove();
							break;
						}
					}
				});
			break;
			
			case 'editOferta':
			
				var dom_modal = $('#editModal', document),
					dom_container = dom_modal.find('[data-modal-container]'),
					modal = UIkit.modal(dom_modal);
				
				if(!modal.isActive())
				{
					modal.show();
					
					var dom_id = ele.parents('[data-id]').attr('data-id');
				
					//fn_call_ajax = function(fn_hash, fn_data, fn_before, fn_success)
					fn_call_ajax("ofertasManage", {
						id:dom_id,
						type:'getOfertaEdit',
						data:null
					}, function()
					{
						$('[data-modal-container]').html($.tmpl('modalPreloader'));
					}, function(d)
					{
						if(debug) console.log(d);
						
						if(d.status == 200)
						{ 
							$('[data-modal-container]').html($.tmpl('editModalOferta', d.data));
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
			break;
		}
	}
	
	//submits
	e_submit_handler = function()
	{
		var ele = $(this),
			dom_form = ele.parents('form'), 
			dom_type = dom_form.find('[data-submit]').attr('data-submit'), 
			dom_ser = dom_form.serialize();
			
		console.log("[>] submit form ["+dom_type+"]");
		
		//fn_call_ajax = function(fn_hash, fn_data, fn_before, fn_success)
		fn_call_ajax('ofertasManage', {
			type:dom_type,
			data:dom_ser
		}, function()
		{
			dom_form.find('i.uk-hidden').removeClass('uk-hidden');
		}, function(d)
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
					case "addOferta":
						location.reload();
					break;
					
					case "upOferta":
						var dom_modal = $('#editModal', document),
						modal = UIkit.modal(dom_modal);
						
						modal.hide();
						
						location.reload();
					break;
				}
			}
		});
	}
	
	admin_ofertas();
});