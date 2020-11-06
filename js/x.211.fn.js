$(function()
{
	//slider
	$.template("sliderTMPL", '<div class="slider"><div class="iosSlider"><div class="slider-content">'+
		
		'{{if data}}{{each(i, v) data}}'+
		   //slider normal
		   '{{if v.type=="slider" || v.type=="image"}}<div class="item" data-bgfrom-img="${v.img}" data-id="${i}"></div>{{/if}}'+
		   
		   //slider action
		   '{{if v.type=="action"}}<a href="${v.url}" class="item" data-bgfrom-img="${v.img}" data-id="${i}">&nbsp;</a>{{/if}}'+
		   			
		'{{/each}}{{/if}}'+
		
		'</div>'+ //slider-content
	
		//arrows
		'{{if config.showArrows && config.showArrows==true}}<div class="prevContainer {{if config.arrowsAlign}}${config.arrowsAlign}{{else}}bottom{{/if}}"><div class="prev"><a href="javascript:void(0);" rel="nofollow"><svg width="40" height="40" version="1.1" baseProfile="full" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" ><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#arrow-l"></use></svg></a></div></div><div class="nextContainer {{if config.arrowsAlign}}${config.arrowsAlign}{{else}}bottom{{/if}}"><div class="next"><a href="javascript:void(0);" rel="nofollow"><svg width="40" height="40" version="1.1" baseProfile="full" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#arrow-r"></use></svg></a></div></div>{{/if}}'+
		
		//dots
		'{{if config.showDots && config.showDots==true}}<div class="dots"><ul class="p-n">{{if data.length > 1}}{{each(i, v) data}}<li><a href="javascript:void(0);" rel="nofollow" data-dot="${i}"></a></li>{{/each}}{{/if}}</ul></div>{{/if}}'+
		
	'</div></div>'+ //end slider
	
	//dual slider
	'{{if config.type=="dual"}}<div class="dual-slider"><div class="iosSlider"><div class="slider-content">'+
		'{{if data}}{{each(i, v) data}}'+
			'<div class="thumb" data-bgfrom-img="${v.thumb}"></div>'+
		'{{/each}}{{/if}}</ul></div>'+
	'</div></div></div>{{/if}}');
	
	//preloader del grid productos
	$.template('preloaderTMPL', '<img src="'+fn_base_script+'images/preloader-big.gif" alt="preloader spinning animation" />');
	
	//cookies
	$.template('smallCookie', '<div class="cookies-policy">'+
		'Aceptas nuestra <a href="'+fn_base_script+'politica-de-privacidad" target="_self">Política de Privacidad</a> y <a href="'+fn_base_script+'politica-de-cookies" target="_self">Política de Cookies</a>?<br/>Utilizamos ‘cookies’ propias y de terceros para mejorar nuestros servicios, así como para obtener datos estadísticos de navegación de los usuarios.'+
		'<div class="w-1-1 pt-2"></div>'+
		'<a href="javascript:void(0);" data-cookies="accept">Aceptar</a>'+
	'</div>');
	
	//cart
	$.template('cart', '<div class="cart-container">\
			<div class="w-1-1">\
			<a href="javascript:void(0);" data-action="closeCart"><img src="'+fn_base_script+'images/icon-close.svg" width="27px" height="27px" alt="close button"/></a>\
			</div>\
			<div class="w-1-1 pt-2"></div>\
			<div class="container">\
				{{if data.status == 400}}\
					{{if data.message}}<div class="w-1-1 txt@c">${data.message}</div>{{/if}}\
				{{else}}'+
					'{{if data.data && data.data.checkout && data.data.checkout.cart_count == 0}}\
						<div class="txt@c">${data.lang.cart_empty}</div>\
					{{else}}'+

						//items
						'{{if data.data && data.data.cart}}{{each(i, v) data.data.cart}}\
							{{tmpl({data:v, lang:data.lang}) "cart_item"}}\
						{{/each}}{{/if}}'+
						
						//total
						'{{if data.data && data.data.checkout}}<div class="w-1-1"></div>\
						<div class="g gc">\
							<div class="w-1-2"></div>\
							<div class="w-1-2">\
								<div class="g gc">\
									<div class="w-1-2">\
										<strong class="label txt@u">${data.lang.lang_iva}(${data.data.checkout.cart_iva_percent}%)</strong>\
									</div>\
									<div class="w-1-2 txt@r">\
										<strong class="label txt@u txt-org">${data.data.checkout.cart_iva}&euro;</strong>\
									</div>\
									<div class="w-1-2">\
										<strong class="label txt@u">SUBTOTAL</strong>\
									</div>\
									<div class="w-1-2 txt@r">\
										<strong class="label txt@u txt-org">${data.data.checkout.cart_subtotal}&euro;</strong>\
									</div>\
								</div>\
							</div>\
						</div>\
						<div class="w-1-1 pb-5"></div>{{/if}}'+
						
						//botones final					
						'{{if data.data && data.data.cart}}{{tmpl() "cart_botones_final"}}{{/if}}'+
						
					'{{/if}}'+
				'{{/if}}\
				{{if data == "preload"}}<div class="w-1-1 tc txt@c">{{tmpl() "preloaderTMPL"}}</div>{{/if}}\
			</div>\
		</div>\
	');
	
	$.template('cart_item', '<div class="item" data-cid="${data.cat_id}" data-pid="${data.p_id}"><div class="g gc">\
		<div class="w-1-2">\
			<img src="${data.thumb}" class="e" />\
		</div>\
		<div class="w-1-2">\
			<div class="w-1-1 txt@u txt-org">\
				{{if data.title}}<div class="w-1-1"><strong class="label">{{html data.title}}</strong></div>{{/if}}\
				{{if data.subtitle}}<div class="w-1-1">{{html data.subtitle}}</div>{{/if}}\
			</div>\
			<div class="w-1-1 sep"></div>\
			{{if data.pax_multimplier > "1"}}\
			<div class="w-1-1"><strong>${lang.lang_box} ${data.pax_multimplier} ${lang.lang_bot}</strong></div>\
			<div class="w-1-1 txt-org"><strong>${data.price_caja}&euro;</strong></div>\
			{{if data.price_unit != "0"}}<div class="w-1-1"><strong>${data.price_unit}&euro; ${lang.lang_ud}</strong></div>{{/if}}\
			<div class="w-1-1 sep"></div>'+
//cajas			
'{{if data.by_box && data.by_box == "1"}}<div class="w-1-1">\
	<div class="g gc" data-group="control">\
	  <div class="w-1-1 pb-2">\
		<strong>${lang.cart_cant_cajas}</strong>\
	  </div>\
	  <div class="w-6-10">\
		<div class="w-1-1">\
		  <input type="number" name="multimplier" min="1" max="${data.stock_count / data.multimplier}" value="${data.multimplier}" data-multimplier class="w-1-1 txt@c"/>\
		</div>\
	  </div>\
	  <div class="w-4-10">\
		<a href="javascript:void(0);" class="fr w-1-1 txt@c" data-action="cartDelItem-side">\
		  <img src="'+fn_base_script+'images/icon-trash.svg" width="22px" height="27px" />\
		</a>\
	  </div>\
	  <div class="w-1-1 pt-4"></div>\
	  <div class="w-6-10 flx@fd-r@jc-sb@ai-c@ac-c">\
		<a href="javascript:void(0);"  data-action="cartDownItem-side">\
		  <img src="'+fn_base_script+'images/icon-minus.svg" width="25px" height="25px" />\
		</a>\
		<a href="javascript:void(0);" data-action="cartUpItem-side">\
		  <img src="'+fn_base_script+'images/icon-plus.svg" width="25px" height="25px" />\
		</a>\
	  </div>\
	  <div class="w-4-10"></div>\
	  <div class="w-1-1 txt-org txt@r pt-9">\
		<strong>${data.price_multimplier}&euro;</strong>\
	  </div>\
  </div>\
</div>{{/if}}'+			
			
			'{{if data.by_box && data.by_box == "1" && data.by_pax && data.by_pax == "1"}}<div class="w-1-1 sep"></div>{{/if}}\
			{{/if}}'+
		
//botones botellas
'{{if data.by_pax && data.by_pax == "1"}}<div class="w-1-1">\
	<div class="g gc" data-group="control">\
	  <div class="w-1-1 pb-2">\
		<strong>${lang.cart_cant_bot}</strong>\
	  </div>\
	  <div class="w-6-10">\
		<div class="w-1-1">\
			<input type="number" name="pax" min="1" max="${data.stock_count}" value="${data.pax}" data-pax class="w-1-1 txt@c">\
		</div>\
	  </div>\
	  <div class="w-4-10">\
		<a href="javascript:void(0);" class="fr w-1-1 txt@c" data-action="cartDelItem-side">\
		  <img src="'+fn_base_script+'images/icon-trash.svg" width="22px" height="27px" />\
		</a>\
	  </div>\
	  <div class="w-1-1 pt-4"></div>\
	  <div class="w-6-10 flx@fd-r@jc-sb@ai-c@ac-c">\
		<a href="javascript:void(0);"  data-action="cartDownItem-side">\
		  <img src="'+fn_base_script+'images/icon-minus.svg" width="25px" height="25px" />\
		</a>\
		<a href="javascript:void(0);" data-action="cartUpItem-side">\
		  <img src="'+fn_base_script+'images/icon-plus.svg" width="25px" height="25px" />\
		</a>\
	  </div>\
	  <div class="w-4-10"></div>\
	  <div class="w-1-1 txt-org txt@r pt-9">\
		<strong>${data.price_unit_total}&euro;</strong>\
	  </div>\
  </div>\
</div>{{/if}}'+	
		
		'</div> \
		<div class="w-1-1 pt-1"></div><div class="w-1-1 sep"></div><div class="w-1-1 pt-1"></div> \
	</div></div>');
	
	$.template('cart_botones_final', '<div class="w-1-1 pb-4"> \
		<div class="w-1-1 pb-2"><a href="'+fn_base_script+'atm-club-shop" class="w-1-1 secundary button" data-action="closeCart">${data.lang.cart_seguircomprando_but}</a></div> \
		<div class="w-1-1 pb-2"><a href="'+fn_base_script+'cart" class="w-1-1 secundary button">${data.lang.cart_vercarrito_but}</a></div> \
		<div class="w-1-1"><a href="'+fn_base_script+'checkout" class="w-1-1 button">${data.lang.cart_checkout_but}</a></div> \
	</div>');
	
	//_G vars
	var _G = {
		FIRSTRUN:false,
		SCROLL_TOP:$(window).scrollTop(),
		HASH:'',
		USERLANG: userLang,
		REQ:false,
		COOKIES:false,
		EDAD:false,
		EDAD_OPEN:false,
		GMAP:false,
		GMAPSTYLE:[{featureType:"all",stylers:[{saturation:-100},{gamma:0.50}]},{featureType:"water",elementType:"all",stylers:[{hue:"#d8d8d8"},{visibility: "simplified"}]},{featureType:"landscape",elementType:"all",stylers:[{hue:"#0077ff"},{visibility:"simplified"},{invert_lightness:"true"}]} ],
		GMAP_JSON:{},
		SLIDER:{},
		VIDEOFS:{},
		CLICKED: ""
	};
	
	var init = function()
	{
		trace('init running up jquery.ver['+$().jquery+']');			
		
		_G.COOKIES = readCookie('showPrivacy');
		_G.EDAD = readCookie('showEdad');
		
		//miramos el hash y cargamos los scripts correspondientes
		$('html[data-hash]').each(function(e)
		{
			var fn_hash = $(this).attr('data-hash'),
				fn_lang = $(this).attr('lang'),
				fn_type = $(this).attr('data-type');
			
			_G.HASH = fn_hash;
			_G.TYPE = fn_type;
			_G.LANG = fn_lang;
			
			//mark menu
			$('header nav a.active').removeClass('active');
			$('header nav a[data-hash*="'+_G.HASH+'"]').addClass('active');
			
			//lang
		    $('.lang [data-l="'+_G.USERLANG+'"]').parents('li').addClass('active');
		    $('.current-lang').html(_G.USERLANG);
			
			switch(_G.TYPE)
		    {
				case "home":
					if(!_G.EDAD) window.loadJS(
						{
							items:[
								fn_base_script+'js/jquery.fancybox.min.js',
								fn_base_script+'js/jquery.fancybox.min.js'
							],
							callback: fn_home_edad()		
						});
				break;
				
			    case "checkout":
					$('html[data-hash="checkout"] :input').on("change", e_checkoutValidator_handler);
			    break;
		    	
				case "cart":
					$('[data-multiplier] input').on("change", e_updateCart);
				break;
			}
		});
		
		fn_init_fn();
		
		
		if(!_G.COOKIES)
		{
			if(debug) console.log("[>] COOKIES");
			
			clearTimeout($.data(this, 'timertoshowCookies'));
			$.data(this, 'timertoshowCookies', setTimeout(function()
			{
				$.tmpl('smallCookie').appendTo($('body', document)).fadeIn(1200);
				$(document).on('click', '[data-cookies="accept"]', e_cond_accept);
			}, 5000));
		}
		
		//scroll to 
		$(document).on('click', '[href^="#"]', e_scrolltoid);
		
		//mobile click
		$(document).on('click', '[menu-handler]', fn_menu_handler);
		
		$(document).on('click', '[data-action]', e_action_handler);
		$(document).on('click', '[data-event]', e_analytics_event);
		
		$(document).on('click', '[data-submenu="1"]', fn_tab_handler);
		
		$(document).on('click', '.cart-close-outside', e_closeCartOutDom);
		$(document).on('click', '[data-show-pass]', e_showPass);
		$(document).on('click', '[data-edad]', fn_home_edad_buttons_handler);
				
		$(window).on('resize', e_resize).trigger('resize');
		
		//load script
		if($('[data-fancybox]').length !== 0) window.loadJS(
		{
			items:[
				fn_base_script+'js/jquery.fancybox.min.js'
			],
			callback: fn_init_fancybox()		
		});
	}
	
	fn_home_edad_buttons_handler = function()
	{
		var ele = $(this).attr('data-edad');
		
		if(ele == "si") createCookie('showEdad', 'true', 2);
		$.fancybox.close(true);
	}
	
	fn_home_edad = function()
	{
		if(_G.EDAD_OPEN) return;
		
		_G.EDAD_OPEN = true;
		
		clearTimeout($.data(this, 'timertoshowEdad'));
		$.data(this, 'timertoshowEdad', setTimeout(function()
		{
			$.fancybox.open({
				src  : '#modal_edad',
				type : 'inline',
				toolbar  : false,
				smallBtn : true,
				infobar : false,
				arrows : false,
				closeExisting : true,
				buttons:[],
			});
		}, 5000));
	}
	
	e_showPass = function(e)
	{
		var ele = $(e.currentTarget).parents('span').find(':input');
		
		if(ele.attr('type') == "password")
		{
			ele.attr('type', 'text');
		}else{
			ele.attr('type', 'password');
		}
	}
	
	e_closeCartOutDom = function(e){
		e.stopPropagation();
		e.preventDefault();
		e_action_handler(null, "closeCart")
		return;
	}
	
	e_updateCart = function(e) 
	{
		e_action_handler(e, 'upCart');
		return;
	}
	
	//validador de checkout
	e_checkoutValidator_handler = function()
	{
		var ele = $(this),
			form = ele.parents("form"),
			doms = form.find(":input[required]"),
			emptyFields = 0;
		
		if(doms) for(var e in doms)
		{
			if(!$.isNumeric(e)) continue;
			if($(doms[e]).val() == "") emptyFields++;
		}
		
		if(!$('[name="p_pay_type"]').is(":checked")) emptyFields++;
		
		if(!emptyFields)
		{
			var dom_p = $('input[name="p_agree_privacy"]').is(":checked") ? true : false,
				dom_y = $('input[name="p_agree_payment"]').is(":checked") ? true : false;
			
			if(dom_p && dom_y && emptyFields == 0)
			{
				$('[data-action="checkoutPayment"]').attr('disabled', false);
			}else{
				$('[data-action="checkoutPayment"]').attr('disabled', true);
			}
		}
	}
	
	e_action_handler = function(e, fn) {
		var ele = $(this),
		dom_type = (fn) ? fn : ele.attr('data-action'),
		dom_data_ser = (ele.parents('form').length !== 0) ? ele.parents('form').serialize() : null;
		
		//cart buttons
		if(/cartUpItem|cartUpItem-side/gim.test(dom_type))
		{
			var itm = ele.parents('[data-group="control"]').find(':input');
			if(itm.val() == undefined || itm.val() == "")
			{
				itm.val(1);
			}else{
				itm.val(parseFloat(itm.val()) + 1);
			}
			
			e_action_handler(e, 'upCart');
			return;
		}
		
		if(/cartDownItem|cartDownItem-side/gim.test(dom_type))
		{
			var itm = ele.parents('[data-group="control"]').find(':input');
			itm.val(parseFloat((itm.val() == 0) ? 0 : itm.val() - 1));
			e_action_handler(e, 'upCart');
			return;
		}
		
		if(/cartDelItem|carDelItem-side/gim.test(dom_type))
		{
			var itm = ele.parents('[data-group="control"]').find(':input');
			itm.val(0);
			
			var itms = ele.parents('.item').find('[data-group="control"]'),
				isDel = false;
			
			if(itms)
			{
				var totalItms = itms.length;
				var countItms = 0;
				
				for(var i in itms)
				{
					if(!$.isNumeric(i)) continue;
					
					if($(":input", itms[i]).val() == 0) countItms++;
				}
				
				if(totalItms == countItms)
				{
					ele.parents('.item').remove();
					e_action_handler(e, 'delCart');
				}else{
					e_action_handler(e, 'upCart');
				}
			}
			
			return;
		}
		
		if(/(rec|Pass|client|clientUpInvoiceDir|clientUpShippingDir)/gim.test(dom_type)) ele.parents('form').find("input").removeClass("error");
		
		if(/(newClient)/gim.test(dom_type))
		{
			var accept = ele.parents('form').find("input[name='accept']").is(":checked");
			if(!accept)
			{
				ele.parents('form').find("input[name='accept']").addClass("error");
				return;
			}
		}
		
		if(/closeCart/gim.test(dom_type))
		{
			$('html').removeClass('menu-open').removeClass('openCart');
			$('[data-cart-container]').empty();
			
			//recargamos la pagina en checkout
			if($('html').attr('data-hash') == 'checkout') location.reload();
			
			return;
		}
		
		if(/(repeat|add|open)Cart/gim.test(dom_type))
		{
			//quitamos el mob menu
			$('html').removeClass('menu-open').addClass('openCart');
			$('.cart-wrap [data-cart-container]').html($.tmpl('cart', {data:"preload"}));
		}
		
		if(/repeatCart/gim.test(dom_type)) dom_data_ser = ele.attr('data-id');
		
		if(/addCart/gim.test(dom_type))
		{
			var dom_data_ser = JSON.stringify({
				"p_id": ele.data('id'),
				"cat_id": ele.data('cat')
			});
		}
		
		//update cart items
		if(/(del|up)Cart/gim.test(dom_type))
		{
			var dom_data_ser = JSON.stringify({
				"p_id": $(e.currentTarget).parents('.item').data('pid'),
				"cat_id": $(e.currentTarget).parents('.item').data('cid'),
				"pax": $(e.currentTarget).parents('.item').find('[data-pax]').val(),
				"multimplier" : $(e.currentTarget).parents('.item').find('[data-multimplier]').val(),
			});
		}
		
		if(/checkoutPayment/gim.test(dom_type))
		{
			e.preventDefault();
			e.stopImmediatePropagation();
			e.stopPropagation();
			
			ele.attr("disabled", true);
			
			ele.parents('[data-cc-container]').find('[data-message]').show().html('<img src="'+fn_base_script+'images/preloader-gray-small.gif"/>');
		}
				
		fn_call_ajax(dom_type, 
		{
			data:dom_data_ser
		}, null, function(d)
		{
			if(debug) console.log(dom_type, d);
			
			if(/cart(Del|Down|Up)Item-side/gim.test(dom_type))
			{
				$('.cart-wrap [data-cart-container]').html($.tmpl('cart', {data:d}));
				return;
			}
				
			if(/(add|open|repeat)Cart/gim.test(dom_type) || (/(up|del)Cart/gim.test(dom_type) && $('html').hasClass('openCart'))) $('.cart-wrap [data-cart-container]').html($.tmpl('cart', {data:d}));
			
			
			if(d.status == 200)
			{
				//update header cart counter
				if(/(up|add|open|repeat|del)Cart/gim.test(dom_type))
				{
					if(!d.data.cart || !d.data.cart.length)
					{
						$('.cart-not').addClass('h');
						e_action_handler(null, "closeCart");
					}else{
						$('.cart-not').text(d.data.cart.length).removeClass('h');
					}
				}
				
				//reload add cart / del
				if(/(up|del)Cart/gim.test(dom_type) && !$('html').hasClass('openCart')) window.location.reload();
					
				if(dom_type == 'newClient') window.location.href = "/atm-club-bienvenido";
				if(dom_type == 'recPass') window.location.href = "/recuperacion-de-contrasena-enviado";
				if(dom_type == 'recUpPass') window.location.href = "/login";
				if(/(reclamacionSend|clientUpInvoiceDir|clientUpShippingDir|clientPersonalData|clientUpPass)/gim.test(dom_type)) ele.parents('form').find('[data-message]').show();
				
				if(dom_type == "checkoutPayment") ele.parents('[data-cc-container]').find('[data-message]').show();
				
				//clean form
				if(/(reclamacionSend)/gim.test(dom_type))
				{
					ele.parents('form').find('select').val(0);
					ele.parents('form').find(':input').val("");
				}
				
				if(/checkoutPayment/gim.test(dom_type))
				{
					ele.attr("disabled", false);
					
					//si hay algun fallo redireccionamos a la pagina de tienda o home
					if(d.data && d.data.redirect) window.location.href = d.data.redirect;
					
					if(d.data && d.data.pay_html)
					{
						var dom_container = $('<div/>', {
							'class':'h',
							'id':'paymentform'
						});
						
						$('body').append(dom_container).find('#paymentform').html(d.data.pay_html);
						$('#paymentform form').submit();
					}
				}
				
			}else{
				if(dom_type == 'recPass') ele.parents('form').find("input").addClass("error");
				
				if(/checkoutPayment/gim.test(dom_type))
				{
					ele.attr("disabled", false);
					$('[data-message]').html(d.message);
				}
			}
			
			if(/checkoutPayment/gim.test(dom_type)) ele.find('span').addClass('h');
			
			if(d.dom && d.dom.length !== 0) for(var i in d.dom)
			{
				$('[name="'+d.dom[i]+'"]').addClass("error");
			}
		});
	}
	
	//tab open close
	fn_tab_handler = function(e)
	{
		if($(window).width() > 768) return;
		
		var ele = $(this),
			eleHref = ele.attr('href');
		
		if(ele.parents('li').find('.submenu').length !== 0 && _G.CLICKED !== eleHref)
		{
			e.preventDefault();
			e.stopPropagation();
		}
		
		_G.CLICKED = eleHref;		
		
		//close all 
		ele.parents('[data-content-from="menu"]').find('ul .submenu').stop().slideUp(450);
		ele.parents('[data-content-from="menu"]').find('.open').removeClass('open');
				
		//open
		var isOpen = ele.parents('li').innerHeight();
		
		ele.addClass('open');
		ele.parents('li').find('.submenu').stop().slideToggle(450, function()
		{
			if(ele.hasClass('open') && isOpen > ele.parents('li').innerHeight()) ele.removeClass('open');
		});
	}
	
	//funciones que se cargan al initcio y en algunas ocasiones
	fn_init_fn = function()
	{
		if($('[data-preloadimages]').length !== 0) fn_page_preload_images();
		if($('[data-bgfrom-img]').length !== 0) fn_page_bgfromimg();
		if($('[data-box-height]').length !== 0) fn_page_boxheight();
		
		if($('noscript[id*="slider"]').length !== 0) fn_initSlider_handler();
		if($('noscript[id*="videofs"]').length !== 0) fn_videofs_handler();
		
		if($('[data-some-h]').length !== 0) some_h();
		if($('[data-source]').length !== 0) fn_content_from_source();
	}
	
	//scrollto element
	e_scrolltoid = function(e)
	{
		var ele = $(e.currentTarget).attr('href') || false;
		
		e.preventDefault();
		e.stopPropagation();
		
		if(!ele || ele == undefined) return;
		
		$('html,body').animate(
		{
			scrollTop: $(ele).offset().top
		}, 'slow');
	}
	
	//movemos contenido desde source al content form
	fn_content_from_source = function()
	{
		$('[data-content-from]').each(function(i, o)
		{
			var src = $(o).attr('data-content-from');
			
			$('[data-source="'+src+'"]').clone(true, true).appendTo('[data-content-from="'+src+'"]');
			
			if($('[data-some-h]').length !== 0) some_h();
		});
	}
	
	some_h = function()
	{
		var doms = $('[data-some-h]'),
			max_h = {};
		
		if(doms.length == 0) return;
		
		//deteccion de max height
		for(var i in doms)
		{
			if(!$.isNumeric(i)) continue;
			
			var doms_val = $(doms[i]).data('some-h');
			
			if(max_h[doms_val] == undefined) max_h[doms_val] = 0;
			
			var dom_h = parseFloat($(doms[i]).height());
			
			if(max_h[doms_val] < dom_h) max_h[doms_val] = dom_h;
		}
		
		//segunda ronda colocacion de height en ese grupo de elementos
		for(var i in max_h)
		{
			$('[data-some-h="'+i+'"]').height(max_h[i]);
		}
	}
		
	//mobil menu
	fn_menu_handler = function()
	{
		if($('html').hasClass('menu-open'))
		{
			$('html').removeClass('menu-open');
		}else{
			$('html').addClass('menu-open');
		}
	}
	
	//init fancybox
	fn_init_fancybox = function()
	{
		if(typeof $.fancybox == 'function')
		{
			clearTimeout($.data(this, 'data-fancybox'));
			
			var dom_gid = $.parseJSON($('body[data-popup]').attr('data-popup'));
		
			if(dom_gid) fn_call_ajax('homeGalleryContent', dom_gid, null, function(d)
			{
				if(debug) console.log(d);
				
				if(d.status == 200) $.fancybox.open(d.data, 
				{
					loop : true,
					youtube : {
						controls : 0,
						showinfo : 0
					},
					vimeo : {
						color : 'f00'
					}
				});
			});
		}else{
			clearTimeout($.data(this, 'data-fancybox'));
			$.data(this, 'data-fancybox', setTimeout('fn_init_fancybox()', 350));
		}
	}
	
	//cookies conditions
	e_cond_accept = function()
	{
		createCookie('showPrivacy', 'true', 2);
		
		$('.cookies-policy', document).fadeOut(1200, function()
		{
			$(this).remove();
		});
	}
	
	//process videofs
	fn_videofs_handler = function()
	{
		trace("[R:300]");
		
		//rellenamos contenidos
		$('noscript[id*="videofs"]').each(function(i, o)
		{
			var ele = $(o),
				ele_c = ele.contents();
			
			if(ele_c.length == 0 && ele_c[0].data == undefined) return;
			
			var data = $.parseJSON(ele_c[0].data);
			
			var items = {};
			var dom_v_sources = "";
				
			if(data && data.data.length !== 0) for(var i in data.data)
			{
				if(/mp4/gim.test(data.data[i].img))
				{
					items.mp4 = data.data[i].img;
					dom_v_sources += '<source src="'+items.mp4+'" type="video/mp4">';
				}
				
				//if(/webmsd/gim.test(data.data[i].img)) items.webm = data.data[i].img;
				if(/webmhd/gim.test(data.data[i].img))
				{
					items.webm = data.data[i].img;
					dom_v_sources += '<source src="'+items.webm+'" type="video/webm">';
				}
				
				if(/ogv|ogg/gim.test(data.data[i].img))
				{
					items.ogv = data.data[i].img;
					dom_v_sources += '<source src="'+items.ogv+'" type="video/ogv">';
				}
			}
			
			data.data = items;
			
			_G.VIDEOFS[i] = {
				id: $(ele).parent().attr('data-slider-id'),
				data: data
			};
			
			var dom_v = $('[data-slider-id="'+_G.VIDEOFS[i].id+'"] video');
			//var fn_src = (!!document.createElement('video').canPlayType('video/mp4; codecs=avc1.42E01E,mp4a.40.2')) ? items.mp4 : items.webm;
			
			
			dom_v.append(dom_v_sources);
			dom_v.attr('muted', true);
			dom_v.on('oncanplay, loadedmetadata', e_play_video);
		});
	}
	
	e_play_video = function(e)
	{
		var dom = $(e.currentTarget).get(0);
		var status;
		
		try{
			status = dom.play();
			dom.attr('muted', false);
		}catch(error)
		{
			status == undefined;
		}
		
		if(status !== undefined)
		{
			clearTimeout($.data(this, 'data-playVideo'));
			$.data(this, 'data-playVideo', setTimeout(function()
			{
				e_play_video(e);
			}, 350));
		}else{
			clearTimeout($.data(this, 'data-playVideo'));
		}
	}
	
	//process slider
	fn_initSlider_handler = function()
	{
		trace("[R:388]");
		
		//rellenamos contenidos
		$('noscript[id*="slider"]').each(function(i, o)
		{
			var ele = $(o),
				ele_c = ele.contents();
			
			if(ele_c.length == 0 && ele_c[0].data == undefined) return;
			
			_G.SLIDER[i] = $.parseJSON(ele_c[0].data);
			if(ele.attr('data-slider-container') && ele.attr('data-slider-container').length !== 0) _G.SLIDER[i].config.dom = ele.attr('data-slider-container');
		});
		
		//load script
		window.loadJS(
		{
			items:[
				fn_base_script+'js/jquery.iosslider.min.js'
			],
			callback:fn_initSlider_dom()
		});
	}
	
	//slider init
	fn_initSlider_dom = function()
	{
		if(typeof $.fn.iosSlider == 'function')
		{
			if(_G.SLIDER !== null) 
			{
				trace("[R:409]");
				
				if(_G.SLIDER.length !== 0) for(var i in _G.SLIDER)
				{
					if(!$.isNumeric(i)) continue;
					
					if(!_G.SLIDER[i].config.dom) return;
					
					trace(_G.SLIDER[i]);
					
					var ele = $('.slider-area[data-slider-id="'+_G.SLIDER[i].config.dom+'"]'),
						args = {
							snapToChildren: true,
							desktopClickDrag: true,
							autoSlide: true,
							autoSlideTimer: 8000,
							onSliderLoaded: fn_slideChange,
							onSliderUpdate: fn_slideChange,
							onSlideChange: fn_slideChange
						};
					
					//añadimos slider containers
					ele.removeClass('h').html($.tmpl('sliderTMPL', _G.SLIDER[i]));
					
					if($('[data-bgfrom-img]').length !== 0) fn_page_bgfromimg();
					if($('[data-box-height]').length !== 0) fn_page_boxheight();
					
					if(_G.SLIDER[i].config.type == 'normal')
					{
						//puntos opcionales
						if(_G.SLIDER[i].config.showDots) args.navSlideSelector = ele.find('.dots li a');
						
						//flechas opcionales
						if(_G.SLIDER[i].config.showArrows)
						{
							args.navNextSelector = ele.find('.slider .next a');
							args.navPrevSelector = ele.find('.slider .prev a');
						}
						
						ele.find('.slider .iosSlider').iosSlider(args);
					}else{
						//doble con imagen abajo
						args.onSliderLoaded = function(e)
						{
							doubleSlider2Load(e, ele);
						},
						args.onSlideChange = function(e)
						{
							doubleSlider2Load(e, ele);
						}
						
						ele.find('.slider .iosSlider').iosSlider(args);
						ele.find('.dual-slider .iosSlider').iosSlider(
						{
							desktopClickDrag: true,
							snapToChildren: true,
							snapSlideCenter: false,
							infiniteSlider: false
						});
						
						//go to init
						ele.find('.slider .iosSlider').iosSlider("goToSlide", 1);
						ele.find('.dual-slider .iosSlider').iosSlider("goToSlide", 1);
						
						//bing click
						ele.find('.dual-slider .iosSlider').find('.thumb').each(function(d) 
						{
							$(this).bind("click", function() 
							{
								ele.find('.slider .iosSlider').iosSlider("goToSlide", d + 1);
							})
						})
					}
				}
			}
			
			clearTimeout($.data(this, 'data-init_iosslider'));
		}else{
			clearTimeout($.data(this, 'data-init_iosslider'));
		    $.data(this, 'data-init_iosslider', setTimeout('fn_initSlider_dom()', 350));
		}
	}
	
	/*
	fn_slider_nav = function(b)
	{
		var c = b.parent().children().find(".selectors .item");
		return (c.length !== 0) ? ".selectors .item" : ""
	}
	*/
	
	//slider listener event actions
	fn_slideChange = function(e)
	{
		$(e.sliderContainerObject[0]).find('.dots li').removeClass('active');
		$(e.sliderContainerObject[0]).find('.dots li:eq(' + (e.currentSlideNumber - 1) + ')').addClass('active');
	}
	
	//slider dual change slider
	doubleSlider2Load = function(c, ele) 
	{
		ele.find('.dual-slider .iosSlider').iosSlider("goToSlide", c.currentSlideNumber);
		ele.find('.dual-slider .iosSlider .thumb').removeClass("selected");
		ele.find('.dual-slider .iosSlider .thumb:eq(' + (c.currentSlideNumber - 1) + ')').addClass("selected");
	}
	
	//preload images
	fn_page_preload_images = function()
	{
		var ele = $('[data-preloadimages]');
		
		//preloader de images	
		ele.each(function(e)
		{
			var img = $(this),
				src = img.attr('data-image');
			
			if(!src) return;
			
			$("<img>", {
				'src':src
			}).imagesLoaded(function(d, h, b) 
			{
				if(b.length !== 0) return;
				
				img.hide().attr('src', src).fadeIn();
			});
		});
	}
	
	fn_preloadImage = function(fn_img_dom, fn_img_url, fn_callback)
	{
		if(debug) console.log("[>] fn_preloadImage");
		fn_img_dom.each(function(e)
		{
			var img = $(this);
			
			if(!fn_img_url) return;
			
			$("<img>", {
				'src':fn_img_url
			}).imagesLoaded(function(d, h, b) 
			{
				if(b.length !== 0) return;
				
				if(fn_callback)
				{
					fn_callback.call(this);
				}else{
					img.hide().attr('src', fn_img_url).fadeIn();
				}
			});
		});
	}
	
	//load image to bg
	/*
	 *	ejemplo:
		
		<div data-bgfrom-img="content/image.jpg"></div>
	 
	 * --------------------------------------------------------
	 
	 	<div data-bgfrom-img>
	 		<img src="content/image.jpg" alt="" />
	 	</div>
	*/
	fn_page_bgfromimg = function()
	{
		$('[data-bgfrom-img]').each(function(e)
		{
			var ele = $(this),
				dom_w = ele.width(),
				dom_data_img = ele.attr('data-bgfrom-img');
				
			var dom_img = (dom_data_img) ? dom_data_img : ele.find('img').attr('src');
			
			if(dom_img !== undefined && dom_img.indexOf('blank.gif') == -1)
			{
				fn_preloadImage(ele, dom_data_img, function()
				{
					ele.css(
					{
						backgroundImage:'url('+dom_img+')'
					});			
				});
			}else{
				ele.css(
				{
					backgroundColor:'transparent'
				});
			}
		});
	}
	
	//width to height resize
	/*
	 *	ejemplo
	 	
	 	<div class="w-1-1 height-s" data-box-height data-height-fix="height-s" style="height:20px">&nbsp;</div>
	 	<div class="w-1-1 height-s" data-box-height data-height-fix="height-s">&nbsp;</div>
	*/
	fn_page_boxheight = function()
	{
		$('[data-box-height]').each(function(e)
		{
			var ele = $(this),
				dom_w = ele.width();
				
			ele.css(
			{
				height:dom_w+'px'
			});
			
			$('[data-height-fix]').each(function()
			{
				var ele = $(this),
					dom_reference = ele.attr('data-height-fix'),
					t_height = $('.'+dom_reference).height();
					
				ele.css(
				{
					height:t_height+'px'
				});
			});
		});
	}
	
	//evento de analytics
	e_analytics_event = function()
	{
		var ele = $(this),
			dom_event = ele.attr('data-event');
			
		if(debug) console.log("[>] Analytics - Event");
		if(fn_analytics && $.isFunction(ga)) ga('send', 'event', dom_event, $('html[data-hash]').attr('data-hash'));
	}
	
	//resize event
	e_resize = function()
	{
		if($('[data-box-height]').length !== 0) fn_page_boxheight();
		
		_G.FIRSTRUN = true;
	}
	
	//cookies read write
	createCookie = function(name, value, days) 
	{
		if(days)
		{
			var date = new Date();
			date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
			var expires = "; expires=" + date.toGMTString();
		}else var expires = "";
			name = name;
			document.cookie = name + "=" + value + expires + "; path=/";
	}
	
	readCookie = function(name) 
	{
		var nameEQ = name + "=";
		var ca = document.cookie.split(';');
		
		for (var i = 0; i < ca.length; i++) 
		{
			var c = ca[i];
			while (c.charAt(0) == ' ') c = c.substring(1, c.length);
			if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
		}
		
		return null;
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
		
	/*
	*
	* trace
	* function for debugging, controlled ny variable debug (boolean)
	*
	*/
	trace = function(loc)
	{
		if(debug) console.log(loc);
	}

	init();
});

if(fn_analytics)
{
	(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
	(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
	m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
	})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');
	
	ga('create', fn_analytics, 'auto');
	ga('send', 'pageview');
	
	//debounce 10 sec activity
	setTimeout(function()
	{
		if(debug) console.log("[>] NoBounce 10 sec Analytics");
		ga('send', 'event', 'NoBounce', '10 sec');
	}, 10000);
}