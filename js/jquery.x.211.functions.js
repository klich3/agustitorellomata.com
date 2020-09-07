$(function()
{
	var fn_hash,
		fn_debug = false,
		fn_lat = '41.395568',
		fn_lng = '2.160118',
		is_IOS = (navigator.userAgent.match(new RegExp(/iP(hone|od|ad)/i)) != null) ? true:false,
		is_Mobile = (navigator.platform.match(new RegExp(/Mobile/i))) ? true:false,
		is_LandScape = (window.orientation === 90 || window.orientation === -90) ? true:false,
		fn_nav = ( $(window).width() < 768 && is_IOS ) ? false : true,
		dom_v_call = true,
		f_init = false,
		_G = {
			GMAP_JSON:{},
			GMAP:false,
			FIRSTRUN: false,
			GMAPSTYLE:[{
  			"featureType": "landscape",
  			"stylers": [{
  				"saturation": -100
  			}, {
  				"lightness": 65
  			}, {
  				"visibility": "on"
  			}]
  			}, {"featureType": "landscape",
	  			"stylers": [{
	  				"saturation": -100
	  			}, {
	  				"lightness": 65
	  			}, {
	  				"visibility": "on"
	  			}]
	  		}, {
	  			"featureType": "poi",
	  			"stylers": [{
	  				"saturation": -100
	  			}, {
	  				"lightness": 51
	  			}, {
	  				"visibility": "off"
	  			}]
	  		}, {
	  			"featureType": "road.highway",
	  			"stylers": [{
	  				"saturation": -100
	  			}, {
	  				"visibility": "simplified"
	  			}]
	  		}, {
	  			"featureType": "road.arterial",
	  			"stylers": [{
	  				"saturation": -100
	  			}, {
	  				"lightness": 30
	  			}, {
	  				"visibility": "on"
	  			}]
	  		}, {
	  			"featureType": "road.local",
	  			"stylers": [{
	  				"saturation": -100
	  			}, {
	  				"lightness": 40
	  			}, {
	  				"visibility": "on"
	  			}]
	  		}, {
	  			"featureType": "transit",
	  			"stylers": [{
	  				"saturation": -100
	  			}, {
	  				"visibility": "simplified"
	  			}]
	  		}, {
	  			"featureType": "administrative.province",
	  			"stylers": [{
	  				"visibility": "off"
	  			}]
	  		}, {
	  			"featureType": "administrative.locality",
	  			"stylers": [{
	  				"visibility": "off"
	  			}]
	  		}, {
	  			"featureType": "administrative.neighborhood",
	  			"stylers": [{
	  				"visibility": "on"
	  			}]
	  		}, {
	  			"featureType": "water",
	  			"elementType": "labels",
	  			"stylers": [{
	  				"visibility": "on"
	  			}, {
	  				"lightness": -25
	  			}, {
	  				"saturation": -100
	  			}]
	  		}, {
	  			"featureType": "water",
	  			"elementType": "geometry",
	  			"stylers": [{
	  				"hue": "#ffff00"
	  			}, {
	  				"lightness": -25
	  			}, {
	  				"saturation": -97
	  			}]
	  		}]
		};
		
	var init = function()
	{
		if(fn_debug) console.log("[init] runnig");
		//resize listener call on run
		$(window).on('resize', e_resize_handler).trigger('resize');
		
		//menu control listeners
		$(document).on('click', '.nav_open_handler', e_menu_toggle_click_hander);
		$(document).on('mouseenter', '.nav_wrap', e_menu_mouseenter_hander);
		$(document).on('click', 'a[data-type^="login"]', e_login_handler);
		$(document).on('click', 'a[data-type^="contact"]', e_contact_handler);
		
		$('.nav_wrap').swipe(
		{	
			swipe:function(event, direction, distance, duration, fingerCount, fingerData) 
			{
				if(direction == 'up')
				{
					//close
					e_menu_out_click_hander();
				}else if(direction == 'down')
				{
					//open
					e_menu_mouseenter_hander();
				}
	        },
			threshold:0
		});
		
		clearTimeout($.data(this, 'hidesidemenuTimer'));
		$.data(this, 'hidesidemenuTimer', setTimeout(e_firstinit_hander, 1840));
				
		setTimeout(function() 
		{
			if(gtag) gtag("event", "NoBounce", 
			{
				'event_category': "NoBounce",
				'event_label': "Over 20 sectonds",
				'value': 1
			});
		},20*1000);
		
		if($('noscript[id*="gmap"]').length !== 0) fn_gmap_handler(); //cargamos gmap
		
		$(window).on('popstate', function(e)
	    {
	    	returnLocation = history.location || document.location;
		  	fn_hash = returnLocation.hash;
		  	
		  	//change hash on enter
			if(fn_hash == '')
			{
				fn_hash = '#home';
				history.pushState(null, null, fn_hash);
			}
			
			//google analytics status
			if(gtag) gtag("event", "Pages",
			{
				'event_category': "Pages",
				'event_label': fn_hash,
				'value': 1
			});
			
		  	if(fn_hash.indexOf('#') !== -1) fn_hash = fn_hash.replace('#', '');
		  	
		  	//update html hash
		  	$('html').attr('data-hash', fn_hash);
		  	
		  	fn_change_page(fn_hash);
	    }).trigger('popstate');
	}
	
	fn_gmap_handler = function()
	{
		$('noscript[id*="gmap"]').each(function()
		{
			var dom_j = $('noscript#gmap').contents();
			
			if(dom_j.length == 0 && dom_j[0].data == undefined) return;
			
			_G.GMAP_JSON = $.parseJSON(dom_j[0].data);
			
			//cargamos el api de google maps
			window.loadJS(
			{
				items:[
					'//maps.googleapis.com/maps/api/js?v=3&key=AIzaSyB-PiqlqCHKm0NtQm1CcZmj79nRb1HN9PI&callback=fn_gmapInit'
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
		if(!_G.FIRSTRUN || !_G.GMAP_JSON || _G.GMAP_JSON.length == 0) return;
	
		var ele = $('#'+_G.GMAP_JSON.options.renderDomId);
		if(fn_action == 'resize') ele.empty();
		
		//gmap
		var map = new google.maps.Map(document.getElementById(_G.GMAP_JSON.options.renderDomId), 
		{
			center: new google.maps.LatLng(41.385064, 2.173403), //barcelona
			zoom: 17,
			disableDefaultUI: false,
			draggable: true,
			scaleControl: false,
			scrollwheel: true,
			mapTypeId: google.maps.MapTypeId.ROADMAP,
			styles: _G.GMAPSTYLE
		});
		
		var infowindow = new google.maps.InfoWindow();
		var marker;
		var bounds = new google.maps.LatLngBounds();
		
		var fn_gps = _G.GMAP_JSON.maps;
		
		/*for(var i in fn_gps)
		{
			if(!$.isNumeric(i)) continue;
			var p = (fn_gps[i].gps.indexOf(', ') !== -1) ? fn_gps[i].gps.split(", ") : fn_gps[i].gps.split(",");
			var fn_point = new google.maps.LatLng(p[0], p[1]);
			
			bounds.extend(fn_point);
			
			marker = new google.maps.Marker(
			{
				position: fn_point, 
				map: map,
				animation: (fn_action == 'resize') ? google.maps.Animation.NONE : google.maps.Animation.DROP,
				icon: fn_gps[i].gmapIcon
			});
		}*/
		
		var p = (_G.GMAP_JSON.maps[0].gps.indexOf(', ') !== -1) ? _G.GMAP_JSON.maps[0].gps.split(", ") : _G.GMAP_JSON.maps[0].gps.split(",");
		var fn_point = new google.maps.LatLng(p[0], p[1]);
		
		bounds.extend(fn_point);
		
		var marker = new google.maps.Marker(
		{
			position: fn_point, 
			map: map,
			icon: _G.GMAP_JSON.maps[0].gmapIcon,
			animation: (fn_action == 'resize') ? google.maps.Animation.NONE : google.maps.Animation.DROP
		});
		
		map.setCenter(bounds.getCenter());
		//map.fitBounds(bounds);
	}
	
	e_firstinit_hander = function()
	{
		e_menu_out_click_hander();
		$(document).on('mouseenter', '.main', e_menu_out_click_hander);
		$(document).on('touchstart', '.main', e_menu_out_click_hander);
		
		f_init = true; //first init
	}
	e_contact_handler = function(e)
	{
		var fn_doms_serialize = $(e.target).parents('.contact.form').find(':input').serialize();
		//clean up fields
		$('.contact.form :input').removeClass('error');
		$.ajax(
		{
			url:'?dmjax=contact',
			data:fn_doms_serialize,
			dataType:'json',
			type:'POST',
			success:function(data)
			{
				if(data.status == 200)
				{
					$('.contact.form :input').val('');
					$('#wrap .main .side-content .contact.form').addClass('ok');
				}else if(data.status == 400)
				{
					$('#wrap .main .side-content .contact.form').addClass('error');
				}
				if(data.dom.length !== 0)
				{
					for(var i = 0; i < data.dom.length; i++)
					{
						$('[name^="'+data.dom[i]+'"]').addClass('error');
					}
				}
				//timer for remove class
				clearTimeout($.data(this, 'hidestatuscontact'));
				$.data(this, 'hidestatuscontact', setTimeout(function()
				{
					$('#wrap .main .side-content .contact.form').removeClass('ok');
					$('#wrap .main .side-content .contact.form').removeClass('error');
				}, 8000));
			}
		});
	}
	e_login_handler = function(e)
	{
		var fn_doms_serialize = $(e.target).parents('.login.form').find(':input').serialize();
		//clean up fields
		$('.login.form :input').removeClass('error');
		$.ajax(
		{
			url:'?dmjax=login',
			data:fn_doms_serialize,
			dataType:'json',
			type:'POST',
			success:function(data)
			{
				//if(data.status == 200){}
				if(data.dom.length !== 0)
				{
					for(var i = 0; i < data.dom.length; i++)
					{
						$('[name^="'+data.dom[i]+'"]').addClass('error');
					}
				}
			}
		});
	}
	fn_change_page = function(fn_page_hash)
	{
		//video
		if(fn_page_hash == 'home')
		{
			f_create_videobg();
		}else{
			f_stop_videobg();
		}
		
		//load on change the page
		if(fn_page_hash == 'contact') fn_gmap_handler(); 
		$('.main .section').not('.hidden').addClass('hidden');
		$('.main .section.'+fn_page_hash).removeClass('hidden');
		
		if(f_init) e_menu_out_click_hander();
	}
	e_menu_toggle_click_hander = function(e)
	{
		$('#wrap').toggleClass('menu-open');
	}
	e_menu_mouseenter_hander = function(e)
	{
		$('#wrap').addClass('menu-open');
	}
	e_menu_out_click_hander = function(e)
	{
		$('#wrap').removeClass('menu-open');
	}
	e_resize_handler = function(e)
	{
		var w = $(e.target).width(),
			h = $(e.target).height();
		//var fn_gmap_h = (is_IOS && is_LandScape) ? 195:h;
		if(w > 768) $('#gcanvas').css(
		{
			height:h+'px'
		});
		
		
		$('#wrap .main .content').css(
		{
			height:h+'px'
		});
		
		//set default video size on init
		$('#wrap .main .content .home .bg_video').css(
		{
			height:h+'px'
		});
		
		//resize video bg
		f_resizeVideo();
		fn_nav = ( w < 768 && is_IOS ) ? false : true;
		
		//gmap correction
		if($('#gcanvas').length !== 0) fn_gmapInit('resize');
		
		_G.FIRSTRUN = true;
	}
	
	f_stop_videobg = function()
	{
		$('.home .bg_video video').remove();
		$('.home .bg_video .full-bg').remove();
		dom_v_call = true;
	}
	
	f_create_videobg = function()
	{
		if(!dom_v_call) return;
		dom_v_call = false;
				
		//cleaning up
		$('.home .bg_video video').remove();
		
		if(is_IOS || is_Mobile)
		{
			var dom_i = $('<div class="full-bg" style="background-image:url(images/ios_snap.jpg);" />');
			$(dom_i).appendTo('.home .bg_video');
		}else{
			var u_video = Modernizr.video;
			var dom_v = $('<video muted x-webkit-airplay="allow" />');
			dom_v.attr('autoplay', 'true');
			dom_v.attr('loop', 'true');
			dom_v.attr('webkit-playsinline','true');
			dom_v.attr('preload', 'auto');
			var fn_src;
			u_video.webm && (fn_src = 'videos/timelapse.webm'),
			u_video.h264 && (fn_src = 'videos/timelapse.mp4');
			dom_v.attr('src', fn_src);
			$(dom_v).on('loadedmetadata', function(e)
			{
				if($('.bg_video video').length == 0) $(e.target).appendTo('.home .bg_video');
				f_resizeVideo();
				$(e.target).animate(
				{
					opacity:1
				}, 2000, 'easeOutExpo');
				$(e.target)[0].play();
			});
		}
	}
	f_resizeVideo = function()
	{
		if($('.bg_video video').length == 0) return;
		var w = $(window).width(),
			h = $(window).height();
		var v_w = 1280,
			v_h = 720,
			fn_video_w = w / v_w,
			fn_video_h = h / v_h,
			fn_video_s = fn_video_h > fn_video_w ? fn_video_h : fn_video_w,
			fn_video_f,
			fn_video_l;
			if((fn_video_s * v_w) < 320) fn_video_s = 320 / v_w;
			fn_video_f = fn_video_s * v_w;
			fn_video_l = fn_video_s * v_h;
			$('video').css(
			{
				width:fn_video_f+'px',
				height:fn_video_l+'px'
			});
			$('.bg_video').css(
			{
				marginLeft:-((fn_video_f - w) / 2)+'px'
				//marginTop:-((fn_video_l - h) / 2)+'px'
			});
			$('.bg_video .pattern').css(
			{
				left:(fn_video_f - w) / 2+'px'
			});
	}
	init();
});