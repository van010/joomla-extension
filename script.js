/** 
 *------------------------------------------------------------------------------
 * @package       T3 Framework for Joomla!
 *------------------------------------------------------------------------------
 * @copyright     Copyright (C) 2004-2013 JoomlArt.com. All Rights Reserved.
 * @license       GNU General Public License version 2 or later; see LICENSE.txt
 * @authors       JoomlArt, JoomlaBamboo, (contribute to this project at github 
 *                & Google group to become co-author)
 * @Google group: https://groups.google.com/forum/#!forum/t3fw
 * @Link:         http://t3-framework.org 
 *------------------------------------------------------------------------------
 */


//functions
(function($){

	$(document).ready(function(){

		//back to top
		(function(){

			var btt = $('#back-to-top');
			if(btt.length){
				
				btt.click(function(){
					if($(this).hasClass('reveal')){
						$('html, body').stop(true).animate({
							scrollTop: 0
						});
					}

					return false;
				}).appendTo(document.body);	

				$(window).scroll(function(){
					$('#back-to-top').toggleClass('reveal', $(window).scrollTop() > ($('#t3-mainbody').length ? $('#t3-mainbody').offset().top : 0));
				});
			}

		})();

		//popover initialize
		(function(){
			$('[rel="popover"]').popover();	
		})();

		//equal height
		(function(){
			$('.t3-mashead .row').children().children().eqboxs();
		})();

		//fix close message on mobile
		(function(){
			if('ontouchstart' in window){
				$('a[data-dismiss]').each(function(){
					if(!$(this).attr('href')){
						$(this).attr('href', '#');
					}
				})

				//easy block
				if(typeof EasyBlog != 'undefined'){
					var clickable = true;
					$('#microblog-save-text, .ui-quickpost-tab a').on('tap', function(){
						if(clickable) {
							EasyBlog.$(this).trigger('click');

							clickable = false;
							setTimeout(function(){ clickable = true}, 100);
						}
					});
				}
			}
		})();

	});
})(jQuery);

//equal height
(function($){
	$.fn.equalboxes = function(){
		var maxheight = 0,
			rowheight = 0,
			rowstart = 0,
			height = 0,
			boxes = [],
			top = 0,
			jel = null;

		//all equalheight (item will not align like a mess)
		this.each(function(){
			jel = $(this);
			height = jel.css({'height': '', 'min-height': ''}).removeClass('eq-first').height();

			if(height > maxheight){
				maxheight = height;
			}

			jel.data('orgHeight', height);

		}).css('min-height', maxheight);

		//per row equal-height
		this.each(function() {
			jel = $(this);
			height = jel.data('orgHeight');
			top = jel.position().top;

			if (rowstart != top) {
				boxes.length && $(boxes).css('min-height', rowheight + 1).eq(0).addClass('eq-first');

				// set the variables for the new row
				boxes.length = 0;
				rowstart = jel.position().top;
				rowheight = height;
				boxes.push(this);

			} else {
				boxes.push(this);
				if(height > rowheight){
					rowheight = height;
				}
			}
		});

		boxes.length && $(boxes).css('min-height', rowheight + 1).eq(0).addClass('eq-first');

		return this;
	};

	$.fn.eqboxs = function(){
		
		//should be more than two elements
		if(this.length < 2){
			return this;
		}

		var elms = this,
			rzid = null,
			resize = function () {
				elms.equalboxes();
			};

		$(window).on('load', function() {
			//trigger one
			elms.equalboxes();

			clearTimeout(rzid);
			rzid = setTimeout(resize, 2000); //just in case something new loaded
		}).on('resize.eqb', function(){
			clearTimeout(rzid);
			rzid = setTimeout(resize, 200);
		});

		//trigger one
		elms.equalboxes();

		return this;
	};

})(jQuery);

// extend slideshow
(function($){
	
	JASliderSupport = {
		inited: false,
		sid: null,
		refresh: false,
		
		initialize: function(){
			if(window.jasliderInst && window.jasliderInst.length && !JASliderSupport.inited){
				window.jasliderInst[0].lastRZWidth = false;
				window.jasliderInst[0]._ooptions = Object.assign({}, window.jasliderInst[0].options);
				
				window.addEventListener('resize', () => {
					clearTimeout(JASliderSupport.sid);
					JASliderSupport.sid = setTimeout(JASliderSupport.resize, 100);
				});
				window.addEventListener('orientationchange', () => {
					window.fireEvent('resize');
				});
				
				JASliderSupport.resize();
				
				JASliderSupport.inited = true;
			}
		},
		resize: function(){
			const instance = window.jasliderInst[0];
			var ooptions = instance._ooptions,
			options = instance.options,
			vars = instance.vars,
			ratio = vars.slider.css('width', '').width() / ooptions.mainWidth,
			nwidth = Math.floor(ooptions.mainWidth * ratio),
			nheight = Math.floor(ooptions.mainHeight * ratio),
			ntwidth = Math.floor(ooptions.thumbWidth * ratio),
			ntheight = Math.floor(ooptions.thumbHeight * ratio);

			if(instance.lastRZWidth != nwidth){
				JASliderSupport.rzTimeout = (/iphone|ipod|ipad|android|ie|blackberry|fennec/).test(navigator.userAgent.toLowerCase()) ? 300 : 100;
				instance.lastRZWidth = nwidth;

				options.mainWidth = nwidth;
				options.mainHeight = nheight;
				options.mainWidth = nwidth;
				options.thumbWidth = ntwidth;
				options.thumbHeight = ntheight;
				options.maskWidth = Math.floor(ooptions.maskWidth * ratio);
				options.maskHeigth = Math.floor(ooptions.maskHeigth * ratio);

				vars.mainWrap.css({
					'width': nwidth,
					'height': nheight
				});

				vars.mainFrame.css({
					'width': nwidth,
					'height': nheight
				});

				if(options.animation == 'move'){
					vars.mainFrame.find('.ja-slide-item').css({
						'width': nwidth,
						'height': nheight
					});

					const mainItem = $(vars.mainItems[0]);
					const mainItemSpace = options.maskStyle ? 10 : 0,
						isHorz = (options.direction == 'horizontal'),
						mainItemSize = isHorz ? (mainItem.width() + parseInt(mainItem.css('margin-left')) + parseInt(mainItem.css('margin-right'))) 
						: (mainItem.height() + parseInt(mainItem.css('margin-top')) + parseInt(mainItem.css('margin-bottom'))),
						rearSize = Math.ceil(((isHorz ? vars.mainWrap.width() : vars.mainWrap.height()) - mainItemSize) / 2);

					vars.size = mainItemSize;
					vars.offset = (options.maskStyle ? (rearSize - mainItemSize + mainItemSpace / 2) : 0) - (options.rtl ? mainItemSpace : 0);

					vars.mainFrame.css(vars.modes[1], vars.size * (vars.total + 2));
					vars.fx.attr(vars.modes[0], -vars.curIdx * vars.size + vars.offset);
					
				} else {
					
					vars.mainItems.css({
						'width': nwidth,
						'height': nheight
					});
				}

				instance.initMasker();
				instance.initThumbAction();
				instance.initMainCtrlButton();
				instance.initProgressBar();
			} else {
				JASliderSupport.rzTimeout *= 2;
			}

			clearTimeout(JASliderSupport.sid);
			JASliderSupport.sid = setTimeout(JASliderSupport.resize, JASliderSupport.rzTimeout);
		}
	};

	$(window).on('load', function() {
		JASliderSupport.initialize();
	});
	
	$(document).ready(function(){
      //Fix bug tabs
      if($('.nav.nav-tabs').length > 0){
        $('.nav.nav-tabs a').click(function (e) {
                        e.preventDefault();
                        $(this).tab('show');
                    })
      }
      //Check div message show
      (function(){
            if($("#system-message").children().length){
                $("#system-message-container").show();
                $("#system-message a.close").click(function(){
                    setTimeout(function(){
                        if(!$("#system-message").children().length) $("#system-message-container").hide();

                        if($('#t3-content').length >0 && $('#t3-content').html().trim().length == 0){
                            $('#t3-content').hide();
                        }else if($('#t3-content').find('.blog-featured').length>0 && $('#t3-content').find('.blog-featured').html().trim().length == 0 && $("#system-message").children().length == 0){
                            $('#t3-content').hide();
                        }
                    }, 100);
                });
            } else {
                $("#system-message-container").hide();
            }
      })();
    });

})(jQuery);