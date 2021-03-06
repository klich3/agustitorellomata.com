$(function() 
{
	//page edit product
	$.template('preload', '<i class="uk-icon-spin uk-icon-spinner"></i> Actualizando');
	$.template('message', '{{if status == 200}}<div class="uk-text-success">Cambios guardados <i class="uk-icon-check-circle"></i></div>{{else}}<div class="uk-text-danger">Cambios guardados <i class="uk-icon-warning"></i></div>{{/if}}');
	
	$.template('modalCartContent', '<div class="uk-width-1-1">&nbsp;</div>{{if status == 200}}<div class="uk-text-small">'+
	
		'{{if data.checkout && data.checkout.checkout_id}}<div class="uk-width-1-1"><strong>Numero pedido:</strong>{{if data.checkout.checkout_id}} ${data.checkout.checkout_id} {{/if}}</div>{{/if}}'+
		'<div class="uk-width-1-2"><strong>Fecha del pago:</strong>{{if data.checkout.checkout_date}} ${data.checkout.checkout_date} {{/if}}</div>'+
		
		//user register data
		'<div class="uk-panel uk-panel-box uk-panel-box-secundary uk-margin-top uk-padding-remove"><div class="uk-panel-body"><div class="uk-grid uk-grid-small"><div class="uk-width-1-1">'+
			'<div class="uk-panel-badge uk-badge uk-badge-notification uk-text-small">Registro</div>'+
			'<strong>Datos del usuario (al realizar el registo)</strong><br/>'+
			'{{if data.user_order.u_id}}<strong>ID:</strong> (<a href="'+fn_base_script+'admin-clientes?action=editCliente&id=${data.user_order.u_id}">${data.user_order.u_id}</a>)<br/>{{/if}}'+
			'{{if data.user_order}}<strong>Nombres:</strong> {{if data.user_order.u_name}} ${data.user_order.u_name} {{/if}} {{if data.user_order.u_surname}} ${data.user_order.u_surname} {{/if}}<br/>'+
			'{{if data.user_order.u_idd}}<strong>Nif / Cif:</strong> ${data.user_order.u_cif} <br/>{{/if}}'+
			'{{if data.user_order.u_tel}}<strong>Teléfono:</strong> ${data.user_order.u_tel} <br/>{{/if}}'+
		'</div></div></div></div>'+
		
		'<div class="uk-panel uk-panel-box uk-panel-box-primary uk-margin-top uk-padding-remove"><div class="uk-panel-body"><div class="uk-grid uk-grid-small">'+
			'<div class="uk-panel-badge uk-badge uk-badge-notification uk-text-small">Envio</div>'+
			
			//datos del user
			'<div class="uk-width-1-2"><strong>Datos de la persona:</strong><br/>'+
				'{{if data.user_order && data.user_order.u_name}}<strong>Nombres:</strong> {{if data.user_order && data.user_order.u_name}} ${data.user_order.u_name} {{/if}} {{if data.user_order && data.user_order.u_surname}} ${data.user_order.u_surname} {{/if}}<br/>{{/if}}'+
				'{{if data.user_order && data.user_order.u_cif}}<strong>Nif / Cif:</strong> ${data.user_order.u_cif} <br/>{{/if}}'+
				'{{if data.user_order && data.user_order.u_tel}}<strong>Teléfono:</strong> ${data.user_order.u_tel} <br/>{{/if}}'+
				'<strong>Email:</strong> <a href="mailto:${data.user_order.u_email}" target="_blank">${data.user_order.u_email}</a><br/>'+
				'{{else}}No te puedo mostrar estos datos ya que hubo problema en el proceso de guardada.{{/if}}</div>'+
			//direccion
		    '<div class="uk-width-1-2"><strong>Dirección:</strong><br/>{{if data.user_order}}<strong>Dir. primaria:</strong> {{if data.user_order.dir_primary}}${data.user_order.dir_primary}{{/if}}<br/><strong>Ciudad:</strong> {{if data.user_order.dir_city}}${data.user_order.dir_city}{{/if}}<br/><strong>Region:</strong> {{if data.user_order.dir_region}}${data.user_order.dir_region}{{/if}}<br/><strong>Cod. Postal:</strong> {{if data.user_order.dir_post}}${data.user_order.dir_post}{{/if}}<br/><strong>País:</strong> {{if data.user_order.dir_country}}${data.user_order.dir_country}{{/if}} {{else}}La dirección no esta asignada porfavor contacte con el usuario.{{/if}}</div>'+	
			    
		'</div></div></div>'+
		
		//-->
		'{{if data.user_facturacion}}<div class="uk-panel uk-panel-box uk-panel-box-primary uk-margin-top uk-padding-remove"><div class="uk-panel-body"><div class="uk-grid uk-grid-small">'+
		'<div class="uk-panel-badge uk-badge uk-badge-notification uk-text-small">Facturación</div>'+
		
		//datos del user
		'<div class="uk-width-1-2">\
			<strong>Datos de facturación:</strong><br/>\
			{{if data.user_facturacion && data.user_facturacion.u_name}}<strong>Nombres:</strong> \
				{{if data.user_facturacion && data.user_facturacion.u_name}} ${data.user_facturacion.u_name} {{/if}}\
				{{if data.user_order && data.user_order.u_surname}} ${data.user_order.u_surname} {{/if}}<br/>\
			{{/if}}\
			{{if data.user_facturacion && data.user_facturacion.u_cif}}<strong>Nif / Cif:</strong> ${data.user_facturacion.u_cif} <br/>{{/if}}\
			{{if data.user_facturacion && data.user_facturacion.u_tel}}<strong>Teléfono:</strong> ${data.user_facturacion.u_tel} <br/>{{/if}}\
				<strong>Email:</strong> <a href="mailto:${data.user_facturacion.u_email}" target="_blank">${data.user_facturacion.u_email}</a><br/>\
			</div>'+
		//direccion
		'<div class="uk-width-1-2"><strong>Dirección:</strong><br/>{{if data.user_facturacion}}<strong>Dir. primaria:</strong> {{if data.user_facturacion.dir_primary}}${data.user_facturacion.dir_primary}{{/if}}<br/><strong>Ciudad:</strong> {{if data.user_facturacion.dir_city}}${data.user_facturacion.dir_city}{{/if}}<br/><strong>Region:</strong> {{if data.user_facturacion.dir_region}}${data.user_facturacion.dir_region}{{/if}}<br/><strong>Cod. Postal:</strong> {{if data.user_facturacion.dir_post}}${data.user_facturacion.dir_post}{{/if}}<br/><strong>País:</strong> {{if data.user_facturacion.dir_country}}${data.user_facturacion.dir_country}{{/if}} {{else}}La dirección no esta asignada porfavor contacte con el usuario.{{/if}}</div>'+	
			
	'</div></div></div>{{/if}}'+
		//-->
	
		//carrito
		'<div class="uk-width-1-1 uk-margin-top uk-margin-bottom"><table class="uk-table uk-table-condensed uk-table-striped"><thead><tr><th class="uk-text-center">Articulo</th><th class="uk-text-center">Cajas</th><th class="uk-text-center">Botellas</th><th class="uk-text-center">Precio</th></tr></thead><tbody>'+
	        
	    '{{each(i, v) data.cart}}<tr> \
			<td class="uk-text-center">{{html v.title}} {{if v.subtitle}}{{html v.subtitle}}{{/if}}</td> \
			<td class="uk-text-center">{{if v.multimplier}}${v.multimplier}{{else}}0{{/if}}</td> \
			<td class="uk-text-center">${v.pax}</td> \
			<td class="uk-text-center">${v.price_total}</td> \
			</tr>{{/each}}'+
	        
	    '</tbody></table></div><div class="uk-grid uk-grid-small">'+
	    
		//totales
		'<div class="uk-width-1-1 uk-text-right">'+
		'<strong>IVA (${data.checkout.cart_iva_percent}%):</strong> ${data.checkout.cart_iva}&euro;<br/>'+
		'<strong>Subtotal:</strong> ${data.checkout.cart_subtotal}&euro;<br/>'+
		'<strong>Total:</strong> ${data.checkout.cart_total}&euro;<br/></div>'+
		
		'</div>{{/if}}');
	
	$.template('modalSegContent', '{{if data && data.id}}<div class="uk-width-1-1">&nbsp;</div><form data-segform class="uk-form"><div class="uk-form-row"><select name="f_sel_shipping" class="uk-width-1-1"><option value="0" {{if data.entrega_status == 0}}selected{{/if}}>En proceso de envío</option><option value="1" {{if data.entrega_status == 1}}selected{{/if}}>Enviado</option><option value="2" {{if data.entrega_status == 2}}selected{{/if}}>Entregado</option></select></div><div class="uk-form-row"><select name="f_sel_payment" class="uk-width-1-1"><option value="0" {{if data.payment_status == 0}}selected{{/if}}>Sin pagar</option><option value="1" {{if data.payment_status == 1}}selected{{/if}}>Pagado</option><option value="2" {{if data.payment_status == 2}}selected{{/if}}>Devuelto</option></select></div><div class="uk-form-row"><input type="hidden" name="id" value="${data.id}" /><a href="javascript:void(0);" data-action="adminStPedidoStatus" class="uk-button uk-button-primary uk-float-right"><i class="uk-hidden uk-icon-spin uk-icon-spinner"></i> Guardar</a></div></form>{{else}}<div class="uk-width-1-1">&nbsp;</div>No te puedo mostrar estos datos hay algo que esta mal. Intenta refrescar la página y vuelve a intentarlo. Disculpa las molestias.{{/if}}');
	
	var admin_pedidos = function() 
	{
		if (debug) console.log("[plugin] admin-pedidos");
		
		//modal reload on close
		$('#editModal', document).on(
		{
		    'hide.uk.modal': function()
		    {
				if($('#editModal form[data-segform]').length !== 0) location.reload();
		    }
		});
		
		$(document).on('click', '[data-action]', e_action_handler);
		$(document).on('click', '[data-modal]', e_editModal_handler);
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
			modal = UIkit.modal(dom_modal);
		
		if(!modal.isActive())
		{
			modal.show();
			
			switch(dom_type)
			{
				case "addSegNum":
				case "adminShowOr":
					var dom_id = ele.parents('[data-id]').attr('data-id'),
						call_by_dom = (dom_type == 'adminShowOr') ? 'getPedidoDetails' : 'getPedidoEstado';
			
					//fn_call_ajax = function(fn_hash, fn_data, fn_before, fn_success)
					fn_call_ajax(call_by_dom, {
						'id':dom_id,
					}, function()
					{
						$('[data-modal-container]').html($.tmpl('modalPreloader'));
					}, function(d)
					{
						if(debug) console.log(d);
						
						if(d.status == 200)
						{ 
							var fn_tmpl = (dom_type == 'adminShowOr') ? 'modalCartContent' : 'modalSegContent';
							$('[data-modal-container]').html($.tmpl(fn_tmpl, d));
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
	
	e_action_handler = function()
	{
		var ele = $(this),
			dom_type = ele.attr('data-action'),
			dom_form = ele.parents('form'),
			dom_ser = dom_form.serialize();
			
		fn_call_ajax(dom_type, {
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
		});
	}
		
	admin_pedidos();
});