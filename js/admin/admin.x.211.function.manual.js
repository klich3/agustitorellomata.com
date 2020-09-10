$(function() 
{
	var _G = {
		gallerySelect:null,
		galleryDrop:null
	};
	
	$.template('message', '{{if status == 200}}<div class="uk-text-success">${message} <i class="uk-icon-check-circle"></i></div>{{else}}<div class="uk-text-danger">${message} <i class="uk-icon-warning"></i></div>{{/if}}');
	
	//modal preloader
	$.template('modalPreloader', '<div class="uk-modal-spinner"></div>');
	
	//principal dialog
	$.template('modalGalleryContent', '<div class="uk-grid uk-grid-small uk-height-viewport" data-uk-grid-margin data-gallery-upload="${gid}">'+
		'<div class="uk-width-small-1-1 uk-width-medium-7-10 uk-width-large-7-10 uk-width-xlarge-7-10">{{if action == "preloader"}}{{tmpl() "modalPreloader"}}{{else}}<form><input class="uk-hidden" type="file"><ul class="uk-grid uk-grid-small uk-grid-width-1-4 uk-sortable" data-uk-sortable="{handleClass:\'uk-sortable-handle\'}">{{if data && data.cnt}} {{each(i, v) data.cnt}} {{tmpl(v) "galleryItem"}} {{/each}} {{/if}}</ul></form>{{/if}}</div>'+
		//sidebar
		'<div class="uk-width-small-1-1 uk-width-medium-3-10 uk-width-large-3-10 uk-width-xlarge-3-10 uk-block-secondary uk-contrast">'+
			'<div class="uk-margin-top">&nbsp;</div>'+
			'{{if action == "content"}}<div class="uk-grid uk-grid-small uk-margin-right">{{tmpl() "galleryModalSidebar"}}</div>{{/if}}'+
		'</div>'+
	'</div>');
	
	//image item
	$.template('galleryReloadContent', '{{if data}}{{each(i, v) data.cnt}} {{tmpl(v) "galleryItem"}} {{/each}}{{/if}}')
	$.template('galleryItem', '<li data-id="${id}"><div class="uk-width-1-1 uk-thumbnail uk-thumbnail-mini {{if isThumb == "1" || isThumb == 1}}bg-green{{/if}}"><i class="uk-sortable-handle uk-icon uk-icon-bars uk-margin-small-right"></i><img src="{{if thumb}}'+fn_base_script+'${thumb}{{else}}'+fn_base_script+'images/blank.gif{{/if}}" alt="${alt}" title="${title}" class="uk-thumbnail-expand" data-hd-img="${img}" data-th-img="${thumb}" /><div class="uk-thumbnail-caption"><span class="uk-text-small uk-text-center">{{if title}}<div class="uk-width-1-1">${title}</div>{{/if}}<div class="uk-button-group">{{if type == "video"}}<a href="${img}" target="_blank" class="uk-button uk-button-small" data-lightbox-type="iframe" data-video-url="${img}"><i class="uk-icon-video-camera"></i></a>{{/if}}<a href="javascript:void(0);" data-gallery="editImageGallery" class="uk-button uk-button-small uk-button-success"><i class="uk-icon-pencil"></i></a><a href="javascript:void(0);" data-gallery="delImageGallery" class="uk-button uk-button-small uk-button-danger"><i class="uk-icon-trash"></i></a></div></span><input type="hidden" name="item[${id}][alt]" value="${alt}" /><input type="hidden" name="item[${id}][title]" value="${title}" /><input type="hidden" name="item[${id}][isThumb]" value="${isThumb}" /></div></div></li>');
	
	//sidebar
	$.template('select', '<option value="${id}">${title}</option>');
	$.template('galleryModalSidebar', 
		//seleccion de galeria
		'<div class="uk-width-1-1"><form class="uk-form" data-id="${pid}" data-gid="${gid}" data-sel-gallery><input type="hidden" name="pid" value="${pid}"/>{{if where == "producto"}}<input type="hidden" name="where" value="producto"/>{{/if}}<div class="uk-grid uk-grid-collapse"><div class="uk-width-1-1"><label for="f_gallery_sel">Seleccionar galería</label></div><div class="uk-width-small-1-1 uk-width-2-3"><select name="f_gallery_sel" class="uk-width-1-1 uk-form-small">{{if data && data.lst}} {{each(i, v) data.lst}} <option value="${v.id}" {{if v.id == gid}}selected{{/if}}>${v.title}</option> {{/each}} {{/if}}</select></div><div class="uk-width-small-1-1 uk-width-1-3"><div class="uk-margin-top uk-margin-small uk-button-group uk-float-right"><a href="javascript:void(0);" data-gallery="assignGalery" class="uk-button uk-button-small uk-text-success" title="Asignar esta galería al producto" data-uk-toolbar>Asign. Gal.</a><a href="javascript:void(0);" data-gallery="delGallery" class="uk-button uk-button-small uk-text-danger" title="Borrar esta galería con todo su contenido." data-uk-toolbar>Borrar</a></div></div></div></form></div>'+
		
		//creacion de galeria
		'<div class="uk-width-1-1 uk-margin uk-margin-small"><hr/></div><div class="uk-width-1-1"><form class="uk-form"><input type="hidden" name="pid" value="${pid}"/><div class="uk-grid uk-grid-collapse"><div class="uk-width-1-1"><label for="f_gallery_title">Crear una galería nueva</label></div><div class="uk-width-small-1-1 uk-width-2-3"><input type="text" name="f_gallery_title" class="uk-width-1-1 uk-form-small" /></div><div class="uk-width-small-1-1 uk-width-1-3"><a href="javascript:void(0);" data-gallery="newGallery" class="uk-margin-top uk-margin-small uk-button uk-button-small uk-button-primary uk-float-right">Crear una galería</a></div><div class="uk-width-1-1 uk-margin uk-margin-small uk-text-small uk-text-right" data-message></div></div></form></div>'+
		
		'<div class="uk-width-1-1 uk-margin uk-margin-small"><hr/></div>'+
		
		//video o item
		'<div class="uk-width-1-1"><p>Crear un item que es un video de Youtube / Vimeo</p><form class="uk-form" data-gallery-viitem><input type="hidden" name="f_gid" value="${gid}" /><div class="uk-grid uk-grid-collapse"><div class="uk-width-1-1"><label for="f_url">Url (link del video)</label></div><div class="uk-width-small-1-1 uk-width-2-3"><input type="text" name="f_url" value="" class="uk-width-1-1 uk-form-small" /></div><div class="uk-width-small-1-1 uk-width-1-3"><a href="javascript:void(0);" data-gallery="addUrlGallery" class="uk-margin-top uk-margin-small uk-button uk-button-small uk-button-primary uk-float-right">Añadir video / url</a></div></div></form></div>'+
		
		//metas
		'<div class="uk-width-1-1 uk-margin uk-margin-small"></div><div class="uk-width-1-1"><form class="uk-form uk-hidden" data-gallery-metaedit><div class="uk-width-1-1"><hr/></div><input type="hidden" name="isEdit" value="false" /><input type="hidden" name="f_id" value="" /><div class="uk-form-row"><strong>Detalles de imagen seleccionada</strong></div><div class="uk-form-row"><input type="checkbox" name="f_isThumb" value="1" disabled/> Imágen destacada?</div><div class="uk-form-row"><label for="f_title">Title</label><input type="text" name="f_title" value="" class="uk-width-1-1 uk-form-small" disabled/></div><div class="uk-form-row"><label for="f_alt">Alt. descripción</label><input type="text" name="f_alt" value="" class="uk-width-1-1 uk-form-small" disabled/></div><div class="uk-form-row"><label for="f_url">Url de la imágen</label><input type="text" name="f_url_image" value="" class="uk-width-1-1 uk-form-small" readonly /></div><div class="uk-form-row"><div class="uk-grid"><div class="uk-width-small-1-1 uk-width-1-2"><a href="javascript:void(0);" data-gallery="cpClipboard" class="uk-button uk-button-small uk-button-success uk-float-left">Copiar link</a></div><div class="uk-width-small-1-1 uk-width-1-2"><a href="javascript:void(0);" data-gallery="stImageAltTitle" class="uk-button uk-button-small uk-button-primary uk-float-right">Guardar cambios</a></div></div></div><div class="uk-form-row" data-message></div></form></div>'+
		
		//upload manual
		'<div class="uk-width-1-1 uk-margin uk-margin-small"><hr/></div><div class="uk-width-1-1 uk-text-center"><i class="uk-icon-cloud-upload uk-icon-medium uk-text-muted uk-margin-small-right"></i> <a class="uk-form-file">Subir archivos manualmente <input id="gallery-uploadselect" type="file"></a></div>'+
		
		//progress
		'<div class="uk-width-1-1 uk-margin uk-margin-small"><hr/></div><div class="uk-width-1-1"><div id="progressbar" class="uk-progress uk-hidden"><div class="uk-width-1-1 uk-text-center uk-text-warning">(<span data-upload-x>0</span> de <span data-upload-y>0</span>)</div><div class="uk-progress-bar" style="width: 0%;"></div></div></div>');
	
	var init = function() 
	{
		if (debug) console.log("[init] runnig");
		
		//text editor
		init_text_editors();
		
		$('html[data-hash]').each(function(e) 
		{
			var ele = $(this),
				hash = ele.attr('data-hash');
			
			//load default scripts
			var fn_itmToLoad = [];
			
			switch (hash) 
			{
				case "admin-options":
					fn_itmToLoad.push(fn_base_script+'js/admin/admin.options.fn.js');
				break;
				
				case "admin-paginas":
					fn_itmToLoad.push(fn_base_script+'js/admin/admin.pages.fn.js');
				break;
				
				case "admin-menus":
					fn_itmToLoad.push(fn_base_script+'js/admin/admin.menus.fn.js');
				break;
				
				case "admin-productos":
					fn_itmToLoad.push(fn_base_script+'js/admin/admin.productos.fn.js');
				break;
			}
			
			if(fn_itmToLoad.length !== 0) window.loadJS(
			{
				items: fn_itmToLoad
			});
			
			//menu selects
			$('[data-nav-menu] a[data-hash="'+hash+'"]', document).parents('li').addClass('uk-active');
		});
		
		if($('[data-colorpicker]').length !== 0)
		{
			window.loadJS(
			{
				items:[
					fn_base_script+'js/admin/jquery.colorpicker.js'
				],
				callback:fn_init_colorpicker
			});
		}
		
		clearTimeout($.data(this, 'loginLife'));
		$.data(this, 'loginLife', setTimeout('fn_loginAgain_handler()', session_over));
				
		$(document).on('click', '[data-modal-submit]', fn_submit_modal);
		
		//gallery
		$(document).on('click', '[data-gallery]', fn_gallery_handler);
		//gallery
		
		$(window).on('resize', e_stage_resize).trigger('resize');
		
		$('.uk-modal', document).on(
		{
		    'show.uk.modal': function()
		    {
				$(document).off('keyup', '[data-hash-reference]', fn_hash_parser).on('keyup', '[data-hash-reference]', fn_hash_parser);
		    }
	    });
		
		//modal control type + gallery
		$('#editModal', document).on(
		{
		    'hide.uk.modal': function()
		    {
				$('html').removeClass('uk-modal-page');
				
			    $(this).find('.uk-modal-dialog').removeClass('uk-modal-dialog-blank');
				
				$('#editModal .uk-close').removeClass('uk-close-alt');
				
				//gallery
				if($('[data-modal-container] [data-gallery-upload]').length !== 0 && $('[data-producto-container]').length !== 0)
				{
					//reload gallery
					$(document).off('change', '[data-modal-container] [name="f_gallery_sel"]', e_reload_gallery_handler);
					$(document).off('click', '[data-gallery]', fn_gallery_handler);
					
					location.reload();
				}
		    }
		});
	}
	
	//GALLERY -------------------------------------------------
	
	e_reload_gallery_handler = function()
	{
		if(debug) console.log('[>] e_reload_gallery_handler');
		
		var fn_this_value = $(this).val();
		
		//clean and disable item meta
		$('[data-modal-container] [name="f_title"]').val('').prop('disabled', true);
		$('[data-modal-container] [name="f_alt"]').val('').prop('disabled', true);
		$('[data-modal-container] [name="f_isThumb"]').prop(
		{
			'checked':false,
			'disabled':true
		});
		
		$('[data-modal-container] [name="f_id"]').val('');
		$('[data-modal-container] [name="isEdit"]').val('false');
		
		//mods
		if(fn_this_value == 0)
		{
			$('[data-modal-container] [name="f_url"]').attr('disabled', true);
		}else{
			$('[data-modal-container] [name="f_url"]').attr('disabled', false);
			$('[data-gallery-viitem] [name="f_gid"]').attr('value', fn_this_value);
			$('[data-gallery-upload]').attr('data-gallery-upload', fn_this_value);
			
			fn_upListener_handler();
		}
		
		$('<a/>', 
		{
			'data-gallery':'reloadGalleryContent', 
			'class':'uk-hidden',
			'data-gid':$('option:selected', this).val()
		}).appendTo('body').trigger('click').remove();
	}
	
	//reorder gallery
	e_reorder_gallery_hadler = function()
	{
		if(debug) console.log('[>] e_reorder_gallery_hadler');
		
		$('<a/>', 
		{
			'data-gallery':'orGallery', 
			'class':'uk-hidden',
			'data-gid':$('option:selected', this).val()
		}).appendTo('body').trigger('click').remove();
	}
	
	//up listener
	fn_upListener_handler = function()
	{
		if(debug) console.log('[>] fn_upListener_handler');
		
		$('[data-gallery-upload]').each(function(i, o)
		{
			var progressbar = $('#progressbar', o),
	        	bar = progressbar.find('.uk-progress-bar'),
	        	filesCounter = 1,
	        	filesTotal = 0,
		        settings = {
		            action: fn_base_script+'?ajax=upObjects',
		            params: {
			            gid: $('[name="f_gallery_sel"]', o).val()
		            },
		            allow: '*.*', // allow files
					single: true,
		            filelimit: false,
		            
		            before: function(settings, files)
		            {
			            //set new gid
			            settings.params.gid = $('[name="f_gallery_sel"]', o).val();
		            },
		            
		            notallowed: function()
		            {
			            $('[data-modal-container]').addClass('bg-red');
		            },
		            
		            beforeAll: function(files)
		            {
						 $('[data-modal-container]').removeClass('bg-red');
						
						//total
						filesTotal = files.length;
						progressbar.removeClass('uk-hidden');
						progressbar.find('[data-upload-x]').text(0);
						progressbar.find('[data-upload-y]').text(filesTotal);
		            },
		            
		            loadstart: function() 
		            {
		                bar.css("width", "0%").text("0%");
		                progressbar.removeClass("uk-hidden");
		            },
					
		            progress: function(percent) 
		            {
		                percent = Math.ceil(percent);
		                bar.css("width", percent+"%").text(percent+"%");
		            },
					
					complete: function(r)
					{
						//contador
						if(filesCounter > 1)
						{
							progressbar.find('[data-upload-x]').text(filesCounter);
							progressbar.find('[data-upload-y]').text(filesTotal);
						}
						
						//carga varias imagenes pone previews
						var d = $.parseJSON(r);
						
						if(d && d.status == 200 && d.data) $.tmpl('galleryItem', d.data).prependTo($('[data-uk-sortable]', o));
						
						filesCounter = filesCounter+1;
					},
					
		            allcomplete: function(r) 
		            {
		                var d = $.parseJSON(r);
		                
		                if(debug) console.log(d);
		                
		                if(d && d.status == 400) UIkit.notify(
						{
						    message : d.message,
						    status  : 'info',
						    timeout : 5000,
						    pos     : 'top-center'
						});
		                
		                if(d.status == 200)
		                {
			                bar.css("width", "100%").text("100%");
			                setTimeout(function()
			                {
			                    progressbar.addClass("uk-hidden");
			                }, 250);
			            }
		            }
		        };
			
			_G.gallerySelect = UIkit.uploadSelect($("#gallery-uploadselect"), settings);
			_G.galleryDrop = UIkit.uploadDrop($('[data-gallery-upload]'), settings);
		});
	}
	
	fn_gallery_handler = function()
	{
		//close dropdown
		$('.uk-button-dropdown.uk-open').removeClass('uk-open');
		
		var ele = $(this),
			dom_type = ele.attr('data-gallery'),
			fn_gid = ele.parents('[data-gid]').attr('data-gid');
		
		if(dom_type == 'delImageGallery') fn_gid = $('[data-modal-container] [data-gallery-upload]').attr('data-gallery-upload');
		if(dom_type == 'reloadGalleryContent') fn_gid = ele.attr('data-gid');
			
		var dom_modal = $('#editModal', document),
			modal = UIkit.modal(dom_modal, {modal: false}),
			dom_pid = ele.parents('[data-id]').attr('data-id'),
			dom_gid = fn_gid,
			dom_form = (dom_type == 'orGallery') ? $('[data-modal-container] [data-gallery-upload] form') : ele.parents('form'),
			dom_data_ser = dom_form.serialize(),
			dom_from_where = ($('html[data-hash]').attr('data-hash').indexOf('producto') !== -1) ? 'producto' : 'pagina';
		
		dom_modal.data('modal').options.modal = false;
		
		if(debug) console.log('[>] fn_gallery_handler ('+dom_type+')');
		
		if(dom_type == 'delGallery')
		{
			var fn_sel_g = $('[data-sel-gallery] [name="f_gallery_sel"]').val();
			if(fn_sel_g == '' || fn_sel_g == 0)	return;
		}
		
		if(dom_type == 'newGallery')
		{
			dom_form.find('[name="f_gallery_title"]').removeClass('uk-form-danger');
		
			if(dom_form.find('[name="f_gallery_title"]').val() == '')
			{
				dom_form.find('[name="f_gallery_title"]').addClass('uk-form-danger');
				return;
			}
		}
		
		if(dom_type == 'addUrlGallery')
		{
			dom_form.find('[name="f_url"]').removeClass('uk-form-danger');
		
			if(dom_form.find('[name="f_url"]').val() == '')
			{
				dom_form.find('[name="f_url"]').addClass('uk-form-danger');
				return;
			}
		}
		
		//se lanza solo la primera vez al abrir el modal
		if(dom_type == 'manage')
		{
			dom_data_ser = null;
			
			//gallery selector
			$(document).off('change').on('change', '[data-modal-container] [name="f_gallery_sel"]', e_reload_gallery_handler);
		
			//on order items
			$(document).off('change.uk.sortable').on('change.uk.sortable', '[data-modal-container] [data-uk-sortable]', e_reorder_gallery_hadler);
		}

		if(dom_type == 'editImageGallery')
		{
			var get_image_id = ele.parents('li').attr('data-id'),
				get_image_meta_alt = ele.parents('li').find('input[name="item['+get_image_id+'][alt]"]').val(),
				get_image_meta_title = ele.parents('li').find('input[name="item['+get_image_id+'][title]"]').val(),
				get_image_meta_isthumb = (ele.parents('li').find('input[name="item['+get_image_id+'][isThumb]"]').val() == 1) ? true : false,
				get_image_meta_url = ele.parents('li').find('img').attr('data-hd-img'),
				get_video_meta_url = ele.parents('li').find('[data-video-url]').attr('data-video-url');
			
			$('[data-gallery-metaedit]').removeClass('uk-hidden');
			
			$('[data-modal-container] [name="f_isThumb"]').prop(
			{
				'checked':get_image_meta_isthumb,
				'disabled':false
			});
			$('[data-modal-container] [name="f_title"]').val(get_image_meta_title).attr('disabled', false);
			$('[data-modal-container] [name="f_alt"]').val(get_image_meta_alt).attr('disabled', false);
			$('[data-modal-container] [name="f_url_image"]').val(get_image_meta_url); //url de la imagen
			$('[data-modal-container] [name="f_url"]').val(get_video_meta_url); //url del video
			$('[data-modal-container] [name="f_id"]').val(get_image_id);
			$('[data-modal-container] [name="isEdit"]').val(true);
			
			return;
		}
		
		if(dom_type == 'orGallery') dom_data_ser = $('[data-modal-container] [data-gallery-upload] form').serialize();
		
		//copiar al clipboard link
		if(dom_type == 'cpClipboard')
		{
			var dom_sel = $('[data-modal-container] [name="f_url_image"]').select();
			
			try {
				document.execCommand("Copy");
				
				//notify
				UIkit.notify(
				{
				    message : 'Link Copiado',
				    status  : 'info',
				    timeout : 5000,
				    pos     : 'top-center'
				});
			}catch (e)
			{
				//notify
				UIkit.notify(
				{
				    message : 'No he podido copiar ('+e+')',
				    status  : 'info',
				    timeout : 5000,
				    pos     : 'top-center'
				});
			}
			
			return;
		}
		
		if(dom_type == 'stImageAltTitle')
		{
			if(!$('[data-modal-container] [name="isEdit"]').val()) return;
			
			//$('[data-modal-container] [name="f_isThumb"]').attr('checked', false).attr('disabled', false);
			var form_title = $('[data-modal-container] [name="f_title"]').val(),
				form_alt = $('[data-modal-container] [name="f_alt"]').val(),
				form_f_isThumb = ($('[data-modal-container] [name="f_isThumb"]').is(':checked')) ? 1 : 0,
				form_f_id = $('[data-modal-container] [name="f_id"]').val(),
				dom_item = $('[data-modal-container] li[data-id="'+form_f_id+'"]');
			
			//all items set 0
			if(form_f_isThumb) $('input[name$="[isThumb]"]', '[data-modal-container] [data-gallery-upload] li').val(0);
				
			dom_item.find('input[name="item['+form_f_id+'][alt]"]').val(form_alt);
			dom_item.find('input[name="item['+form_f_id+'][title]"]').val(form_title);
			dom_item.find('input[name="item['+form_f_id+'][isThumb]"]').val(form_f_isThumb);
			
			$('[data-modal-container] [name="isEdit"]').val(false);
			dom_data_ser = $('[data-modal-container] [data-gallery-upload] form').serialize();
		}
		
		//solo la primera vez
		if(!modal.isActive())
		{
			dom_modal.find('.uk-modal-dialog').addClass('uk-modal-dialog-blank');
			modal.show();
			
			$('[data-modal-container]').html($.tmpl('modalGalleryContent', {action:'preloader'}));
		}
		
		//fn_call_ajax = function(fn_hash, fn_data, fn_before, fn_success);
		fn_call_ajax('manageGallery', 
		{
			'type':dom_type,
			'pid':dom_pid,
			'gid':dom_gid,
			data:dom_data_ser
		}, function()
		{
			
		}, function(d)
		{
			if(debug) console.log(d);
		
			if(d.status == 200)
			{
				if(dom_type == 'manage')
				{
					$('[data-modal-container]').html($.tmpl('modalGalleryContent', 
					{
						where:dom_from_where,
						action:'content',
						'gid':dom_gid,
						'pid':dom_pid,
						data:d.data
					}));
					
					//uk-close-alt
					$('#editModal .uk-close').addClass('uk-close-alt');
					
					//set gallery id
					$('[data-gallery-upload]').attr('data-gallery-upload', dom_gid);
					
					if($('[data-gallery-upload]').length == 0)
					{
						if(debug) console.log('No [data-gallery-upload]');
					}else{
						fn_upListener_handler();
					}
				}
				
				if(dom_type == 'assignGalery')
				{
					$('[data-gallery-upload]').attr('data-gallery-upload', d.data.gid);
					$('[data-gallery-viitem] [name="f_gid"]').attr('value', d.data.gid);
					
					UIkit.notify(
					{
					    message : d.message,
					    status  : 'info',
					    timeout : 5000,
					    pos     : 'top-center'
					});
				}
				
				if(dom_type == 'delGallery')
				{
					//del selector
					$('[data-sel-gallery] [name="f_gallery_sel"] option:selected').remove();
					
					//del content
					$('[data-modal-container] [data-uk-sortable] li').remove();
					
					UIkit.notify(
					{
					    message : d.message,
					    status  : 'info',
					    timeout : 5000,
					    pos     : 'top-center'
					});
				}
				
				if(dom_type == 'delImageGallery') ele.parents('li[data-id]').remove();
				
				if(dom_type == 'newGallery') 
				{
					$.tmpl('select', d.data).appendTo('[data-sel-gallery] [name="f_gallery_sel"]')
					$('[data-message]', dom_form).html($.tmpl('message', d));
					
					//marcamos selector
					$('[data-modal-container] [name="f_gallery_sel"]').addClass('uk-form-success');
				}
				
				if(dom_type == 'addUrlGallery') $.tmpl('galleryItem', d.data).prependTo($('[data-uk-sortable]', '[data-modal-container]'));
				
				if(dom_type == 'reloadGalleryContent') $('[data-modal-container] [data-uk-sortable]').html($.tmpl('galleryReloadContent', d));
				
				if(dom_type == 'stImageAltTitle')
				{
					if($('[data-modal-container] [name="f_isThumb"]').is(':checked'))
					{
						var fn_item_id = $('[data-modal-container] [name="f_id"]').val();
						$('[data-modal-container] [data-uk-sortable] li .bg-green').removeClass('bg-green');
						$('[data-modal-container] [data-uk-sortable] li[data-id="'+fn_item_id+'"]').find('.uk-thumbnail').addClass('bg-green');
					}

					//clean and disable
					$('[data-modal-container] [name="f_title"]').val('').prop('disabled', true);
					$('[data-modal-container] [name="f_alt"]').val('').prop('disabled', true);
					$('[data-modal-container] [name="f_isThumb"]').prop(
					{
						'checked':false,
						'disabled':true
					});
					
					$('[data-modal-container] [name="f_id"]').val('');
					$('[data-modal-container] [name="isEdit"]').val('false');
					
					$('[data-modal-container] form[data-gallery-metaedit] [data-message]').html($.tmpl('message', d));
				}
				
				dom_form.find('[data-message]').html($.tmpl('message', d));
			}
		
			/*UIkit.notify(
			{
			    message : d.message,
			    status  : 'info',
			    timeout : 5000,
			    pos     : 'top-center'
			});
			*/
		});
	}
	
	//text editor
	init_text_editors = function()
	{
		if(typeof wysihtml == 'object')
		{
			$('.ewrapper').each(function(idx, wrapper) 
			{
				var e = new wysihtml.Editor($(wrapper).find('.editable').get(0), 
				{
					toolbar: $(wrapper).find('.toolbar').get(0),
					parserRules: wysihtmlParserRules,
					//showToolbarAfterInit: true,
					pasteParserRulesets: wysihtmlParserPasteRulesets,
					style: true,
					//stylesheets: fn_base_script+"style.min.css",
					cleanUp: false,
					supportTouchDevices: true
				});
				
				var dom_iframe = $('.ewrapper').find('iframe.wysihtml-sandbox');
				
				//add svg images
				/*e.on('load', function()
				{
					dom_iframe.ready(function() 
					{
						var b = dom_iframe.contents().find("html"),
							svg = $('svg.h', document).clone();
						$(svg).prependTo(b);
					});
					
				}).on('change_view', function(r)
				{
					var b = dom_iframe.contents().find("html"),
							svg = $('svg.h', document).clone();
							
					if(r == 'composer') $(svg).prependTo(b);
				});
				*/
				
				editors.push(e);
			});
			
			clearTimeout($.data(this, 'dataTimer-ewrapper'));
		}else{
			clearTimeout($.data(this, 'dataTimer-ewrapper'));
		    $.data(this, 'dataTimer-ewrapper', setTimeout('init_text_editors()', 400));
		}
	}
	
	//submit modal ajax
	fn_submit_modal = function()
	{
		var ele = $(this),
			dom_action = ele.attr('data-modal-submit'),
			dom_id = ele.parents('.uk-modal.uk-open').attr('id'),
			dom_form = ele.parents('form'),
			dom_data_ser = $(dom_form).serialize(),
			page_from_dom = ele.parents('form').find('[name^="add_item"]').attr('value');
		
		fn_call_ajax(dom_action+'&hash='+$('html[data-hash]').attr('data-hash'), dom_data_ser, function()
		{
			//preloader
			ele.find('i.uk-hidden').removeClass('uk-hidden');
		}, function(data)
		{
			ele.find('i').addClass('uk-hidden');
			
			if(debug) console.log(data);
			
			//notify
			UIkit.notify(
			{
			    message : data.message,
			    status  : 'info',
			    timeout : 5000,
			    pos     : 'top-center'
			});
			
			if(data.status == 200 && dom_action == 'delImagePreview')
			{
				//acciones posteriores
				ele.parents('.uk-thumbnail').html($.tmpl('blankImage'));
			}else if(data.status == 200 && dom_action == 'delFileAttachment')
			{
				//acciones posteriores
				$('[data-fileCheck]').html($.tmpl('blankFile'));
			}else if(data.status == 200 && dom_action == 'addItems')
			{				
				location.href = fn_base+page_from_dom+'?a=edit-item&pid='+data.data.id;
			}
		});
	}
	
	//hash creator
	fn_hash_parser = function(e)
	{
		if(debug) console.log('[>] fn_hash_parser');
		
		var ele = $(this),
			ele_cnt = ele.val(),
			dom_form = ele.parents('form'),
			dom_hash_ref = dom_form.find('[data-hash-reference]').attr('data-hash-reference'),
			dom_hash = dom_form.find('[name^="'+dom_hash_ref+'"]');
		
		var fn_cr_p = ele_cnt.toLowerCase();
            fn_cr_p = fn_cr_p.replace(new RegExp(/\s/g),"-");
			fn_cr_p = fn_cr_p.replace(new RegExp(/[`~!@#$%^&*()_|+\=?;:'"\'\",.<>\{\}\[\]\\\/]/g),"");
            fn_cr_p = fn_cr_p.replace(new RegExp(/[àáâãäå]/g),"a");
            fn_cr_p = fn_cr_p.replace(new RegExp(/æ/g),"ae");
            fn_cr_p = fn_cr_p.replace(new RegExp(/ç/g),"c");
            fn_cr_p = fn_cr_p.replace(new RegExp(/[èéêë]/g),"e");
            fn_cr_p = fn_cr_p.replace(new RegExp(/[ìíîï]/g),"i");
            fn_cr_p = fn_cr_p.replace(new RegExp(/ñ/g),"n");                
            fn_cr_p = fn_cr_p.replace(new RegExp(/[òóôõö]/g),"o");
            fn_cr_p = fn_cr_p.replace(new RegExp(/œ/g),"oe");
            fn_cr_p = fn_cr_p.replace(new RegExp(/[ùúûü]/g),"u");
            fn_cr_p = fn_cr_p.replace(new RegExp(/[ýÿ]/g),"y");
		
		if(fn_cr_p.length > 0)
		{
			dom_hash.prop('value', fn_cr_p);
		}else{
			dom_hash.prop('value', '');
		}
	}
	
	//resize / menu mobile responsive
	e_stage_resize = function(e) 
	{
		if (debug) console.log("[>] e_stage_resize");
		
		var stage_w = $(window).width(),
			stage_h = $(window).height();
		
		var menu_desktop = $('.menu-cnt'),
			menu_mobile = $('.mob-menu-cnt'),
			menu_dummy = $('.menu-dummy');
			
		$('.uk-nav-sub').css(
		{
			visibility: 'visible'
		});
		
		if(stage_w < 768) 
		{
			if (menu_mobile.find('ul.menu').length == 1) return;
			
			var fn_m = (menu_dummy.find('ul.menu').length == 0) ? menu_desktop : menu_dummy;
			var fn_loc = fn_m.find('ul.menu').addClass('uk-nav-offcanvas');
			
			menu_mobile.html(fn_loc);
			menu_desktop.find('ul.menu').html('');
		} else {
			if (menu_desktop.find('ul.menu').length == 1) return;
			
			var fn_m = (menu_dummy.find('ul.menu').length == 0) ? menu_mobile : menu_dummy;
			var fn_loc = fn_m.find('ul.menu').removeClass('uk-nav-offcanvas');
			
			menu_desktop.html(fn_loc);
			menu_mobile.find('ul.menu').html('');
			UIkit.offcanvas.hide([force = false]);
		}
	}
	
	fn_loginAgain_handler = function(e) 
	{
		var f = new Date();
		if (debug) console.log("session caducada [" + f.getDate() + "/" + (f.getMonth() + 1) + "/" + f.getFullYear() + " " + f.getHours() + ":" + f.getMinutes() + " ]");
		var ele_hash = $('html').attr('data-hash');
		
		if (ele_hash !== 'login' && ele_hash !== 'error' && ele_hash !== 'logout' && ele_hash !== undefined) 
		{
			UIkit.modal("#login_modal", {modal: false}).show();
			$('[data-login-submit="login_modal"]').on('click', fn_modal_login_handler);
		}
	}
	
	fn_modal_login_handler = function(e) 
	{
		var ele = $(this),
			ele_form = ele.parents('form');
		var fn_serialized_inputs = ele_form.serialize();
		ele_form.find('.modal_message').addClass('uk-hidden');
		
		fn_call_ajax('modal_login', fn_serialized_inputs, function() 
		{
			ele.find('i').removeClass('uk-hidden');
		}, function(data) 
		{
			ele.find('i').addClass('uk-hidden');
			if (data.status == 200) {
				UIkit.modal("#login_modal").hide();
			} else {
				ele_form.find('.modal_message').removeClass('uk-hidden');
				ele_form.find('.modal_message .message').html(data.message);
			}
		});
	}
	
	//color picker
	fn_init_colorpicker = function()
	{
		if(typeof $.fn.colorPicker == 'function')
		{
			clearTimeout($.data(this, 'data-colorpicker'));
			
			$('[data-colorpicker]').each(function(i, o)
			{
				$(this).colorPicker();
			});
			
			
		}else{
			clearTimeout($.data(this, 'data-colorpicker'));
		    $.data(this, 'data-colorpicker', setTimeout('fn_init_colorpicker()', 350));
		}
	}
	
	//ajax caller
	fn_call_ajax = function(fn_hash, fn_data, fn_before, fn_success)
	{
		$.ajax(
		{
			url:fn_base_script+'index.php?ajax='+fn_hash,
			dataType:'json',
			data:fn_data,
			type:'POST',
			beforeSend:fn_before,
			success:fn_success
		});	
	}
	
	init();
});