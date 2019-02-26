$(function()
{
	//slider
	$.template("sliderTMPL", '<div class="slider"><div class="iosSlider"><div class="slider-content">'+
		
		'{{if data}}{{each(i, v) data}}'+
		   //slider normal
		   '{{if v.type=="slider" || v.type=="image"}}<div class="item" data-bgfrom-img="${v.img}" data-id="${i}"></div>{{/if}}'+
		   
		   //slider action
		   '{{if v.type=="action"}}<a href="${v.url}" class="item" data-bgfrom-img="${v.img}" data-id="${i}">&nbsp;</a>{{/if}}'+
		   
			//slider video
			/*'{{if v.type=="video"}}<div class="item video" data-bgfrom-img="${v.file}" data-id="${i}">'+
				'<a href="javascript:void(0);" data-slider-action="stopvideo" class="video-close h"><svg width="35" height="35" version="1.1" baseProfile="full" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#slider-videoclose"></use></svg></a>'+
				'<div class="iframe-conteiner h"></div>'+
				'<a href="javascript:void(0);" data-slider-action="loadvideo" data-video-iframe="${v.url}" data-event="home/video/${v.id}">'+
					'<div class="video-icon"><svg width="92" height="92" version="1.1" baseProfile="full" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#slider-videoplay"></use></svg></div>'+
				'</a>'+
			'</div>{{/if}}'+
			*/
		   
			//slider media
			//'{{if v.type=="media"}}<div class="item" data-bgfrom-img><div class="media-conteiner hidden"></div><a href="javascript:void(0);" data-media-file="${v.file}" data-event="home/slider/${v.file}"><div class="video-icon"></div><img src="${v.image}" alt="${v.alt}"/></a></div></div></div>{{/if}}'+
			
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
	
	
	//lista de productos del grid
	$.template('productItemTMPL', '{{if status=="200" && data.length!=="0"}}{{each(i, v) data}}<li><div class="img-cnt" data-bgfrom-img="${v.image}"><a href="javascript:void(0);" rel="nofollow" data-type="getProductDetails" data-pid="${v.id}"><span class="h">{{if lang}}${lang.lang_details}{{else}}+{{/if}}</span></a></div></li>{{/each}}{{/if}}');
	
	//preloader del grid productos
	$.template('preloaderTMPL', '<img src="'+fn_base_script+'images/preloader-big.gif" alt="preloader spinning animation" />');
	
	//modal detalles del producto
	$.template('prodDetailsTMPL', '<div class="w-1-1 mt-3"></div><div class="g"><div class="pt-2 ws-1-1 wm-2-3 wl-2-3 wxl-2-3">'+
		//slider
		'{{if data.slider}} <div class="slider-area h" data-slider-id="slider_product"><noscript id="sliderData">{{parsejson data.slider}}</noscript></div>{{/if}}'+
		
		'</div><div class="pt-2 ws-1-1 wm-1-3 wl-1-3 wxl-1-3">'+
		
		//data content
		'<div class="w-1-1 pt-3 hl">&nbsp;</div>'+
		'{{if data.lang_data}}<div class="w-1-1 txt@c pt-3 pb-3 border@t@b title">${data.lang_data}</div>{{/if}}'+
		'{{if data.lang_content}}<div class="w-1-1 pt-3 pb-3 border@b desc">{{html data.lang_content}}</div>{{/if}}'+
		'<div class="w-1-1 pt-3 pb-3 border@b">'+
			'<div class="g">'+
				'<div class="w-1-2 price">125 €</div>'+
				'<div class="w-1-2">'+
					'<ul class="ln g gc gw-1-3 txt@c w-1-1">'+
						'<li><a href="javascript:void(0);" rel="nofollow" class="arrow l"><svg width="40" height="40" version="1.1" baseProfile="full" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" ><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#arrow-l"></use></svg></a></li>'+
						'<li class="qty">1</li>'+
						'<li><a href="javascript:void(0);" rel="nofollow" class="arrow r"><svg width="40" height="40" version="1.1" baseProfile="full" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" ><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#arrow-r"></use></svg></a></li>'+
					'</ul>'+
				'</div>'+
			'</div>'+
		'</div>'+
		'<div class="w-1-1 font@lr pt-3 pb-2"><a href="" class="button">añadir al cesta</a></div>'+
		'<div class="w-1-1 font@lr pt-2 pb-3"><a href="" class="button">comprar ahora</a></div>'+
		
	'</div></div>');
	
	//_G vars
	var _G = {
		FIRSTRUN:false,
		SCROLL_TOP:$(window).scrollTop(),
		HASH:'',
		REQ:false,
		COOKIES:false,
		GMAP:false,
		GMAPSTYLE:[{featureType:"all",stylers:[{saturation:-100},{gamma:0.50}]},{featureType:"water",elementType:"all",stylers:[{hue:"#d8d8d8"},{visibility: "simplified"}]},{featureType:"landscape",elementType:"all",stylers:[{hue:"#0077ff"},{visibility:"simplified"},{invert_lightness:"true"}]} ],
		GMAP_JSON:{},
		SLIDER:{}
	};
	
	var init = function()
	{
		trace('init running up jquery.ver['+$().jquery+']');			
		
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
			
			switch(_G.TYPE)
		    {
			    /*
			    case "product_grid":
			    break;
				*/
		    }
		});
		
		fn_init_fn();
		
		/*
		_G.COOKIES = readCookie('showPrivacy');
		
		if(_G.COOKIES !== 'false')
		{
			if(debug) console.log("[>] COOKIES");
			
			clearTimeout($.data(this, 'timertoshowCookies'));
			$.data(this, 'timertoshowCookies', setTimeout(function()
			{
				$.tmpl('smallCookie').appendTo($('body', document)).fadeIn(1200);
				$(document).on('click', '[data-cookies="accept"]', e_cond_accept);
			}, 5000));
		}
		*/
		
		//mobile click
		$(document).on('click', '[menu-handler]', fn_menu_handler);
		
		//$(document).on('click', '[data-action]', e_action_submit_handler);
		$(document).on('click', '[data-event]', e_analytics_event);
		
		$(window).on('resize', e_resize).trigger('resize');
	}
	
	//funciones que se cargan al initcio y en algunas ocasiones
	fn_init_fn = function()
	{
		if($('[data-preloadimages]').length !== 0) fn_page_preload_images();
		if($('[data-bgfrom-img]').length !== 0) fn_page_bgfromimg();
		if($('[data-box-height]').length !== 0) fn_page_boxheight();
		
		if($('noscript[id*="slider"]').length !== 0) fn_initSlider_handler();
		if($('noscript[id*="gmap"]').length !== 0) fn_gmap_handler();
		
		if($('[data-some-h]').length !== 0) some_h();
		if($('[data-source]').length !== 0) fn_content_from_source();
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
		if(typeof $.fn.fancybox == 'function')
		{
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
			
   			clearTimeout($.data(this, 'data-fancybox'));
		}else{
			clearTimeout($.data(this, 'data-fancybox'));
		    $.data(this, 'data-fancybox', setTimeout('fn_init_fancybox()', 350));
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
	
	//process gmap
	fn_gmap_handler = function()
	{
		if(!fn_gmapkey) return;
		
		trace("[R:681]");
		
		$('noscript[id*="gmap"]').each(function()
		{
			var dom_j = $('noscript#gmap').contents();
			
			if(dom_j.length == 0 && dom_j[0].data == undefined) return;
			
			_G.GMAP_JSON = $.parseJSON(dom_j[0].data);
			
			//cargamos el api de google maps
			window.loadJS(
			{
				items:[
					'//maps.googleapis.com/maps/api/js?v=3&key='+fn_gmapkey+'&callback=fn_gmapInit'
				],
				callback:function()
				{
					_G.GMAP = true;
				}
			});
		});
	}
	
	//google maps
	fn_gmapInit = function(fn_action)
	{
		var ele = $('#'+_G.GMAP_JSON.renderDomId);
		
		if(!_G.FIRSTRUN) return;
		if(fn_action == 'resize') ele.empty();
		
		trace("[R:713]");
		
		var args = {
			center: new google.maps.LatLng(41.385064, 2.173403), //barcelona
			zoom: 16,
			disableDefaultUI: false,
			draggable: true,
			scaleControl: false,
			scrollwheel: true,
			mapTypeId: google.maps.MapTypeId.ROADMAP
		};
		
		if(_G.GMAPSTYLE !== undefined && _G.GMAPSTYLE.length !== 0) args.styles = _G.GMAPSTYLE;
		
		//gmap
		var map = new google.maps.Map(document.getElementById(_G.GMAP_JSON.renderDomId), args);
		
		var infowindow = new google.maps.InfoWindow();
		var bounds = new google.maps.LatLngBounds();
		
		/*
			for (var k in j) {
				var d = j[k][0].split(", ");
				var f = new google.maps.LatLng(d[0], d[1]);
				b.extend(f);
				l = new google.maps.Marker({
					position: f,
					map: c,
					animation: (g == "resize") ? google.maps.Animation.NONE : google.maps.Animation.DROP
				});
				google.maps.event.addListener(l, "click", (function(m, p) {
					return function() {
						h.setContent('<a href="' + j[p][2] + '" style="color:#000"><strong>' + j[p][1] + "</strong></a>");
						h.open(c, m)
					}
				})(l, k))
			}
		*/
		var p = (_G.GMAP_JSON.gps.indexOf(', ') !== -1) ? _G.GMAP_JSON.gps.split(", ") : _G.GMAP_JSON.gps.split(",");
		var fn_point = new google.maps.LatLng(p[0], p[1]);
		
		bounds.extend(fn_point);
		
		var marker = new google.maps.Marker(
		{
			position: fn_point, 
			map: map,
			icon: fn_base_script+'images/map-pin.png',
			animation: (fn_action == 'resize') ? google.maps.Animation.NONE : google.maps.Animation.DROP
		});
		
		map.setCenter(bounds.getCenter());
		//map.fitBounds(bounds);
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
	
	/*
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
	*/
	
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