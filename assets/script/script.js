/**
 * $JA#COPYRIGHT$
 */


var JASlider = function (element, options) {
	// element: id = #ja-slide-133
	var defaultOptions = {
		effects: [
			'slice-down-right',			//animate height and opacity
			'slice-down-left',
			'slice-up-right',
			'slice-up-left',

			'slice-down-right-offset', 			//have an offset for top or bottom, no animate height
			'slice-down-left-offset',
			'slice-up-right-offset',
			'slice-up-left-offset',

			'slice-updown-right',				//slide up alternate column
			'slice-updown-left',

			'slice-down-center-offset',
			'slice-up-center-offset',

			'slice-down-right-inv',				//look like above, slide from an offset, but use the current image instead of the new image
			'slice-down-left-inv',
			'slice-down-center-inv',
			'slice-up-right-inv',
			'slice-up-left-inv',
			'slice-up-center-inv',

			'slice-down-random',			//slide and offset fade
			'slice-up-random',

			'slice-down-left-wider', 		//slice, wider === fold
			'slice-down-right-wider',
			'slice-down-center-wider',

			'slide-in-left',
			'slide-in-right',
			'slide-in-up',
			'slide-in-down',
			'slide-in-left-inv',
			'slide-in-right-inv',
			'slide-in-up-inv',
			'slide-in-down-inv',

			'fade',
			'fade-four', //create 4 clone and set to offset of 100px from default possiton, animate to defaalt position and fadein

			'box-sort-random', //box, offset from random other position, and animate fa~de to it position, fadein
			'box-random',
			'box-rain-normal',
			'box-rain-reverse',
			'box-rain-normal-grow',
			'box-rain-reverse-grow',
			'box-rain-normal-jelly',
			'box-rain-reverse-jelly',

			'circle-out',
			'circle-in'//,
			//'circle-rotate'
		],

		slices: 10,
		boxCols: 8,
		boxRows: 4,

		animation: 'move', 							//[move, fade, random], move and fade for old compactible
		fbanim: 'fade',
		direction: 'horizontal', 					//[horizontal, vertical] - slide direction of main item for move animation

		interval: 5000,
		duration: 500,
		// transition: Fx.Transitions.Quad.easeOut,
		transition: 'linear',

		repeat: true,								//animation repeat or not
		autoPlay: false,							//auto play

		mainWidth: 800,								//width of main item
		mainHeight: 400,

		rtl: window.isRTL,							//rtl

		startItem: 0,								//start item will be show

		thumbItems: 4,								//number of thumb item will be show
		thumbType: false, 							//false - no thumb, other [number, thumb], thumb will animate
		thumbWidth: 160,
		thumbHeight: 160,
		thumbSpaces: [0, 0],
		thumbOpacity: 0.8,
		thumbTrigger: 'click',
		thumbOrientation: 'horizontal',	//thumb orientation

		maskStyle: 1,							//0 - fix to main image, 1 - full size
		maskWidth: 360,							//mask - a div over the the main item - used to hold descriptions
		maskHeigth: 50,
		maskOpacity: 0.8,						//mask opacity
		maskAlign: 'bottom',					//mask align
		maskTransitionStyle: 'opacity',			//mask transition style
		maskTransition: 'linear',	//mask transition easing

		showDesc: false,						//show description or not
		descTrigger: 'always',					//[always, mouseover, load]

		showControl: false,						//show navigation controller [next, prev, play, playback]

		showNavBtn: false,	// show next prev, on main image image
		navBtnOpacity: 0.4,
		navBtnTrigger: 'click',

		showProgress: true,

		urls: false, // [] array of url of main items
		targets: false // [] same as urls, an array of target value such as, '_blank', 'parent', '' - default
	};
	this.blank = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==';
	this.element = element;
	this.options = options;
	this.slide_id = $(`#${element}`);

	this.initialize = function (element) {
		var slider = $('#' + element);
		if (!slider) {
			return false;
		}

		if (this.slide_id.length == 0){
			return;
		}
		this.slide_id.css({visibility: 'visible'});
		// $($('div.ja-slide-item')[0]).animate({
		// 	width: '300px',
		// 	height: '200px',
		// }, 1000, 'easeInQuad');

		this.options = $.extend({}, defaultOptions, this.options);

		var options = this.options;
		var mainWrap = slider.find('.ja-slide-main-wrap');
		var mainFrame = slider.find('.ja-slide-main');
		var mainItems = slider.find('.ja-slide-item');
		var iframeItems = mainItems.find('iframe');

		if (!mainItems.length) {
			return false;
		}

		var imgItems = mainItems.find('img').filter(function (el) {
			return el !== null && el !== undefined;
		})

		if (mainItems.length !== imgItems.length && options.animation === 'slice') {
			options.animation = options.fbanim;
		}

		if (options.animation !== 'move') {
			options.maskStyle = 0;
		}

		options.rtl = (typeof options.rtl == 'string') ? (typeof options.rtl == 'rtl') : !!(options.rtl);

		mainWrap.css({
			'width': options.maskStyle ? '100%' : options.mainWidth,
			'height': options.mainHeight
		})

		mainItems.css({
			'width': options.mainWidth,
			'height': options.mainHeight
		});

		var mainItemSpace = 0,
			isHorz = (options.direction === 'horizontal');

		if (options.maskStyle) {	//full size
			mainItemSpace = 10;
			mainItems.css(isHorz ? 'margin-right' : 'margin-bottom', mainItemSpace);
		}

		var mainItem = $(mainItems[0]);
		var mainItemSize = isHorz
			? (mainItem.width() + parseInt(mainItem.css('margin-left')) + parseInt(mainItem.css('margin-right')))
			: (mainItem.height() + parseInt(mainItem.css('margin-top')) + parseInt(mainItem.css('margin-bottom')));
		var rearSize = Math.ceil(((isHorz ? mainWrap.width() : mainWrap.height()) - mainItemSize) / 2);

		var vars = {
			slider: slider,
			mainWrap: mainWrap,
			mainFrame: mainFrame,
			mainItems: mainItems,
			iframeItems: iframeItems,
			size: mainItemSize,
			rearSize: rearSize,
			offset: (options.maskStyle ? (rearSize - mainItemSize + mainItemSpace / 2) : 0) - (options.rtl ? mainItemSpace : 0),
			mainItemSpace: mainItemSpace,

			total: mainItems.length,
			curIdx: Math.min(options.startItem, mainItems.length - 1),
			nextIdx: -1,
			curImg: '',

			running: 0,
			stop: 0,
			timer: 0,

			sliceTime: Math.round(Math.max(70, options.duration / options.slices)),
			boxTime: Math.round(Math.max(50, options.duration / Math.max(options.boxCols, options.boxRows))),

			modes: (isHorz ? (options.rtl == 'rtl' ? ['right', 'width'] : ['left', 'width']) : ['top', 'height']),
			fxop: {
				duration: options.duration,
				transition: options.transition,
				link: 'cancel'
			}
		};

		this.vars = vars;

		//Description
		this.initMasker();

		/* // Get initial images
		if (options.animation === 'slice') {
			mainItems.css('display', 'none');
			vars.mainItems = imgItems;
			vars.curImg = vars.mainItems[vars.curIdx];
			vars.sliceImg = new Element('img', {
				'src': vars.curImg.src
			}).inject(new Element('div', {
				'class': 'ja-slide-sliceimg'
			}).inject(vars.mainFrame, 'top'));

			var ofsParent = mainFrame.getOffsetParent() || mainWrap,
				opCoord = ofsParent.getCoordinates();

			//Set first background
			mainFrame.css({
				position: 'relative',
				left: (opCoord.width - options.mainWidth) / 2 - parseInt(ofsParent.css('padding-left')) - parseInt(ofsParent.css('border-left-width')),
				top: (opCoord.height - options.mainHeight) / 2 - parseInt(ofsParent.css('padding-top')) - parseInt(ofsParent.css('border-top-width')),
				overflow: 'hidden',
				display: 'block',
				width: options.mainWidth,
				height: options.mainHeight
			});
		} */

		if (options.animation == 'move') {
			vars.offset -= parseInt(mainFrame.css('margin-left'));
			if (isNaN(vars.offset)) {
				vars.offset = 0;
			}

			if (options.maskStyle) {
				mainItems[0].clone().inject(mainFrame);
				mainItems[vars.total - 1].clone().inject(mainFrame, 'top');
			}
			
			mainFrame.css(vars.modes[1], vars.size * (vars.total + 2));

			vars.fx = $(mainFrame).animate(Object.assign({}, vars.fxop, {
				complete: this.animFinished()
			})).css(vars.modes[0], -vars.curIdx * vars.size + vars.offset);

			// vars.fx = new $.Tween(mainFrame, $.extend({}, vars.fxop, {
			// 	// complete: this.animFinished.bind(this)
			// }));
			// mainFrame.animate({[vars.modes[0]]: -vars.curIdx * vars.size + vars.offset});
		}

		/* if (options.animation === 'fade') {
			var fadeop = Object.assign({ ...vars.fxop },
				{
					property: 'opacity',
					onComplete: function (item) {
						if (item.css('opacity') === '1') {
							this.animFinished();
						}
					}.bind(this)
				});
			// .store('fx', new Fx.Tween(item, fadeop));
			$($.each(mainItems, function (idx, item) {
				$(item).css({
					position: 'absolute',
					top: 0,
					opacity: 0,
					zIndex: 1,
					visibility: 'visible'
				}).data('fx', $(item).animate(fadeop));
			})[vars.curIdx]).css({
				opacity: 2,
				zIndex: 5
			});
		} */

		// this.initMainItemAction();
		// this.initMainCtrlButton();
		// this.initThumbAction();
		this.initControlAction();
		// this.initHoverBehavior();
		// this.initProgressBar();
		// this.initLoader();

		// vars.direct = 'next';
		// slider.css('visibility', 'visible');

		// this.prepare(false, vars.curIdx);
		// this.animFinished();
	};

	this.initMasker = function () {
		const slider = this.vars.slider;
		const maskDesc = slider.find('.maskDesc');

		if (!maskDesc) {
			return;
		}

		if (this.options.showDesc) {
			maskDesc.css({
				'display': 'block',
				'position': 'absolute',
				'width': this.options.maskWidth,
				'height': this.options.maskHeigth,
				'opacity': this.options.maskOpacity
			});

			if (this.options.animation === 'move' && this.options.maskStyle) {
				//options.maskAlign = 'left';
				this.options.maskTransitionStyle = 'opacity';
			}

			maskDesc.css(this.options.maskAlign, Math.max(0, this.vars.rearSize, this.options.edgemargin));

			var descs = this.vars.desciptions || slider.find('.ja-slide-desc');
			var property = this.options.maskTransitionStyle === 'opacity' ? 'opacity' : this.options.maskAlign;
			var valueOn = property === 'opacity' ? this.options.maskOpacity : 1 + this.options.edgemargin;
			var valueOff = property === 'opacity' ? 0.001 : (this.options.maskAlign === 'top' || this.options.maskAlign === 'bottom' 
				? -this.options.maskHeigth : -this.options.maskWidth);


			var maskDescFx = maskDesc.animate({
				'opacity': this.options.maskOpacity
			}, 400, this.options.maskTransition);

			if (this.options.descTrigger === 'mouseover') {
				$(maskDesc, this.vars.mainFrame).mouseenter(() => {
					// this.showDescription.bind(this);
					this.showDescription();
				}).mouseleave(() => {
					// this.hideDescription.bind(this);
					this.hideDescription();
				});

				maskDescFx.attr(valueOff);
			} else {
				maskDesc.css('opacity', this.options.maskOpacity);
			}

			Object.assign(this.vars, {
				maskValueOn: valueOn,
				maskValueOff: valueOff,
				maskDescFx: maskDescFx,
				maskDesc: maskDesc,
				desciptions: descs
			});

		} else {
			maskDesc.css('display', 'none');
		}
	};

	this.stop = function () {
		clearInterval(this.vars.timer);
		this.vars.stop = 1;

		if (this.options.showProgress) {			//stop the progress bar
			this.vars.progressFx.stop().set(0);
		}
	};

	this.prev = function (force) {
		var vars = this.vars;
		if (vars.running && !force) {
			return false;
		}

		const curr_iframe = vars.iframeItems[vars.curIdx];
		if (typeof curr_iframe !== 'undefined') {
			var datasrc = curr_iframe.attr('data-src');
			curr_iframe.prop({
				'src': datasrc,
				'alt': datasrc
			});
		}
		this.prepare(force, vars.curIdx - 1);
	};

	this.next = function (force) {
		var vars = this.vars;
		if (vars.running && !force) {
			return false;
		}
		// if (vars.iframeItems[vars.curIdx])
		if (typeof vars.iframeItems[vars.curIdx] != 'null' && typeof vars.iframeItems[vars.curIdx] != 'undefined') {
			var datasrc = $(vars.iframeItems[vars.curIdx]).prop('data-src');
			$(vars.iframeItems[vars.curIdx]).prop({
				'src': datasrc,
				'alt': datasrc
			});
		}
		this.prepare(force, vars.curIdx + 1);
	};

	this.playback = function (force) {
		this.vars.direct = 'prev';
		this.vars.stop = 0;
		this.prev(force);
	};

	this.play = function (force) {
		this.vars.direct = 'next';
		this.vars.stop = 0;
		this.next(force);
	};

	this.start = function () {
		clearTimeout(this.vars.timer);
		this.vars.timer = setTimeout(this[this.vars.direct].bind(this), this.options.interval)
	};

	this.imgload = function (img_, idx) {
		// img_ type of jQuery
		const img = img_[0];
		if (img.complete && img.naturalWidth !== undefined) {
			this.load(idx);
			return;
		}

		const blank = this.blank;
		const src = img.src;

		const callback = function () {
			this.load(idx);
		}.bind(this);

		const onload = function () {
			if (this.src === blank) {
				return;
			}
			setTimeout(callback);
		};

		img_.on('load', onload).on('error', onload);

		if (img.readyState || img.complete) {
			img.src = blank;
			img.src = src;
		}
	};

	this.load = function (idx) {
		const vars = this.vars;
		const next_item = $(vars.mainItems[idx]);

		next_item.data('loaded', 1);

		if (vars.nextIdx == idx) {
			if (vars.loaderFx) {
				vars.loaderFx.start(0);
			}

			this.run(false, idx);
		} else if (vars.nextIdx == -1 && vars.loaderFx) {
			vars.loaderFx.start(0);
		}
	};

	this.prepare = function (force, idx) {
		var vars = this.vars,
			options = this.options;

		if (options.animation === 'slice' && vars.running) {
			return false;
		}

		if (idx >= vars.total) {
			idx = 0;
		}

		if (idx < 0) {
			idx = vars.total - 1;
		}

		var curImg = vars.mainItems[idx];

		if (curImg.localName.toLowerCase() != 'img') {
			curImg = $(curImg).find('img');
		}

		if (!curImg) {
			return this.run(force, idx);
		}

		vars.nextIdx = idx;
		if (curImg.data('loaded')) {
			if (idx == vars.curIdx) {
				return false;
			}

			this.run(force, idx);
		} else {
			if (vars.loaderFx) {
				vars.loader.css('display', 'block');
				$(this.vars.loaderFx).animate({opacity: .3}, 0);
			}

			this.imgload(curImg, idx);
		}
		this.animFinished();
	};

	this.run = function (force, idx) {

		var vars = this.vars,
			options = this.options;

		if (vars.curIdx == idx) {
			return false;
		}

		if (typeof vars.iframeItems[vars.curIdx] !== 'undefined') {
			var datasrc = $(vars.iframeItems[vars.curIdx]).data('src');
			$(vars.iframeItems[vars.curIdx]).prop({
				'src': datasrc,
				'alt': datasrc
			});
		}
		if (this[options.animation]) {
			this[options.animation](force, idx);
		} else {
			this.fade(force, idx);
		}

		if (vars.thumbMaskFx) {
			if (idx <= vars.thumbStartIdx || idx >= vars.thumbStartIdx + options.thumbItems - 1) {

				var thumb_box_anim_properties = {};
				vars.thumbStartIdx = Math.max(0, Math.min(idx - options.thumbItems + 2, vars.total - options.thumbItems));
				const thumb_box_duration = vars.thumbBoxFx.options.duration;
				const thumb_box_transition = vars.thumbBoxFx.options.transition;

				thumb_box_anim_properties[vars.thumbBoxFx.options.property] = -vars.thumbStartIdx * vars.thumbStep;
				vars.thumbBoxFx.elem.animate(thumb_box_anim_properties, thumb_box_duration, thumb_box_transition);

				if (vars.handleBoxFx.length > 0) {
					vars.handleBoxFx.animate(thumb_box_anim_properties, thumb_box_duration, thumb_box_transition);
				}
			}
			
			vars.thumbMaskFx.elem.animate({
				[vars.thumbMaskFx.options.property]: (idx - vars.thumbStartIdx) * vars.thumbStep - 2000
			}, vars.thumbMaskFx.options.duration, vars.thumbMaskFx.options.transition);

			// vars.thumbMaskFx.start((idx - vars.thumbStartIdx) * vars.thumbStep - 2000);
			$(vars.thumbItems).removeClass('active').eq(idx).addClass('active');
			if ($(vars.handleItems).length > 0) {
				$(vars.handleItems).removeClass('active').eq(idx).addClass('active');
			}
		}

		if (options.descTrigger === 'load' && options.showDesc) {
			this.hideDescription();
		}

		if (options.showProgress) {
			vars.progressFx.stop().set(0);
		}

	};

	this.move = function (force, idx) {
		var vars = this.vars;
		vars.curIdx = idx;
		vars.mainFrame.css(vars.modes[1], vars.size * (vars.total + 2));
		// vars.fx.start(vars.modes[0], -idx * vars.size + vars.offset);
		$(vars.fx).animate({
			[vars.modes[0]]: -idx * vars.size + vars.offset
		});
	};

	this.fade = function (force, idx) {
		var options = this.options,
			vars = this.vars;

		if (idx != vars.curIdx) {
			var itemOff = vars.mainItems[vars.curIdx],
				itemOn = vars.mainItems[idx];

			itemOff.setStyle('zIndex', 1).retrieve('fx').start(0);
			itemOn.setStyle('zIndex', 10).retrieve('fx').start(1);
		}

		vars.curIdx = idx;
	};

	this.slice = function (force, idx) {

		var options = this.options,
			vars = this.vars,
			container = vars.mainFrame,
			oldImg = vars.curImg;

		//Set vars.curImg
		vars.curIdx = idx;
		vars.curImg = vars.mainItems[vars.curIdx];

		// Remove any slices & boxs from last transition
		container.getChildren('.ja-slice').destroy();
		container.getChildren('.ja-box').destroy();

		//Generate random effect
		var effect = options.effects[Math.floor(Math.random() * (options.effects.length))];
		if (effect == undefined) {
			effect = 'fade';
		}

		//Run effects
		var effects = effect.split('-'),
			callfun = 'anim' + effects[0].capitalize();

		if (this[callfun]) {

			vars.running = true;
			this[callfun](effects, oldImg, vars.curImg);
		}
	};

	this.animFinished = function () {
		var options = this.options,
			vars = this.vars;

		vars.running = false;

		//Trigger the afterChange callback
		if (options.showDesc) {
			this.swapDescription();

			if (options.descTrigger === 'load') {
				this.showDescription();
			}
		}

		if (options.urls) {
			vars.mainFrame.css('cursor', options.urls[vars.curIdx] ? 'pointer' : '');
		}

		if (!vars.stop && (options.autoPlay && (vars.curIdx < vars.total - 1 || options.repeat == 'true'))) {
			this.start();

			if (options.showProgress) {
				vars.progressFx.start(vars.progressWidth);
				$(this.vars.progressFx).css({
					width: this.vars.progressWidth
				}).animate({width: this.vars.progressWidth}, 'fast');
			}
		}
	};

	this.createSlice = function (img) {
		var options = this.options,
			vars = this.vars,
			container = vars.mainFrame;

		return new Element('div', {
			'class': 'ja-slice',
			'styles': {
				display: 'block',
				position: 'absolute',
				left: 0,
				width: options.mainWidth,
				height: options.mainHeight,
				opacity: 0,
				zIndex: 10
			}
		}).adopt(new Element('img', {
			'src': img.src,
			'styles': {
				width: options.mainWidth,
				height: options.mainHeight
			}
		})).inject(container);
	};

	this.createSlices = function (img, height, opacity) {
		var options = this.options,
			vars = this.vars,
			container = vars.mainFrame,
			width = Math.round(options.mainWidth / options.slices),
			slices = [];

		for (var i = 0; i < options.slices; i++) {
			var sliceWidth = i == options.slices - 1 ? (options.mainWidth - width * i) : width;

			slices.push(new Element('div', {
				'class': 'ja-slice',
				'styles': {
					position: 'absolute',
					left: i * width,
					width: sliceWidth,
					height: height,
					opacity: opacity,
					zIndex: 10
				}
			}).adopt(new Element('img', {
				'src': img.src,
				'styles': {
					left: -(i * width),
					width: options.mainWidth,
					height: options.mainHeight
				}
			})));
		}

		container.adopt(slices);

		return slices;
	};

	this.createBoxes = function (img, opacity) {
		var options = this.options,
			vars = this.vars,
			container = vars.mainFrame,
			width = Math.round(options.mainWidth / options.boxCols),
			height = Math.round(options.mainHeight / options.boxRows),
			bwidth,
			bheight,
			boxes = [];

		for (var rows = 0; rows < options.boxRows; rows++) {
			bheight = rows == options.boxRows - 1 ? options.mainHeight - height * rows : height;

			for (var cols = 0; cols < options.boxCols; cols++) {
				bwidth = cols == options.boxCols - 1 ? options.mainWidth - width * cols : width;

				boxes.push(new Element('div', {
					'class': 'ja-box',
					'styles': {
						position: 'absolute',
						opacity: opacity,
						left: width * cols,
						top: height * rows,
						width: bwidth,
						height: bheight,
						zIndex: 10
					}
				}).adopt(new Element('img', {
					'src': img.src,
					'styles': {
						left: -(width * cols),
						top: -(height * rows),
						width: options.mainWidth,
						height: options.mainHeight
					}
				})));
			}
		}

		container.adopt(boxes);

		return boxes;
	};

	this.createCircles = function (img, opacity) {
		var options = this.options,
			vars = this.vars,
			container = vars.mainFrame,
			size = 100,
			radius = Math.ceil(Math.sqrt(Math.pow((options.mainWidth), 2) + Math.pow((options.mainHeight), 2))),
			total = Math.ceil(radius / 100),
			left, top, elm,
			circles = [];

		for (var i = 0; i < total; i++) {
			left = Math.round((options.mainWidth - size) / 2);
			top = Math.round((options.mainHeight - size) / 2);

			elm = new Element('div', {
				'class': 'ja-box',
				'styles': {
					position: 'absolute',
					opacity: opacity,
					left: left,
					top: top,
					width: size,
					height: size,
					zIndex: 10
				}
			}).adopt(new Element('img', {
				'src': img.src,
				'styles': {
					left: -left,
					top: -top,
					width: options.mainWidth,
					height: options.mainHeight
				}
			}));

			this.css3(elm, {
				'border-radius': radius + 'px'
			});

			circles.push(elm);

			size += 100;
		}

		container.adopt(circles);

		return circles;
	};

	this.animSlice = function (effects, oldImg, curImg) {
		var options = this.options,
			vars = this.vars,
			img = curImg,
			height = 0,
			opacity = 0;

		if (effects[3] == 'inv') {
			img = oldImg;
			height = options.mainHeight;
			opacity = 1;
		}

		//set the background
		vars.sliceImg.set('src', effects[3] == 'inv' ? vars.curImg.src : oldImg.src);

		var slices = this.createSlices(img, height, opacity),
			styleOn = { height: options.mainHeight - height, opacity: 1 - opacity / 2 },
			last = slices.length - 1,
			timeBuff = 100;

		// by default, animate is sequence from left to right
		if (effects[2] == 'left') {		// reverse the direction, so animation is sequence from right to left
			slices = slices.reverse();
		} else if (effects[2] == 'random') {	// so randomly
			this.shuffle(slices);
		}

		if (effects[3] == 'offset') {										//have offset style - we will not animate height, so set it to fullheight, we animate 'top' or 'bottom' property
			var property = effects[1] == 'up' ? 'top' : 'bottom';

			delete styleOn.height;
			styleOn[property] = 0;

			$$(slices).setStyle(property, '250px').setStyle('height', options.mainHeight);
		} else if (effects[1] == 'updown') {
			for (var k = 0, kl = slices.length; k < kl; k++) {
				$(slices[k]).setStyle((k & 1) == 0 ? 'top' : 'bottom', '0px');
			}
		} else if (effects[1] == 'down') {
			$$(slices).setStyle('top', '0px');
		} else if (effects[1] == 'up') {
			$$(slices).setStyle('bottom', '0px');
		}

		if (effects[3] == 'wider') {
			slices.each(function (slice, i) {
				var fxop = vars.fxop,
					orgWidth = slice.getWidth();

				slice.setStyles({
					'width': 0,
					'height': options.mainHeight
				});

				if (i == last) {
					fxop = Object.clone(vars.fxop);
					fxop.onComplete = this.animFinished.bind(this);
				}

				setTimeout(function () {
					new Fx.Morph(slice, fxop).start({
						width: orgWidth,
						opacity: 1
					});
				}, timeBuff);

				timeBuff += vars.sliceTime;
			}, this);
		} else if (effects[2] == 'center') {
			var center = (last) / 2;
			slices.each(function (slice, i) {
				var fxop = vars.fxop,
					delay = Math.abs(center - i) * 100;

				if (i == last) {
					fxop = Object.clone(vars.fxop);
					fxop.onComplete = this.animFinished.bind(this);
				}

				setTimeout(function () {
					new Fx.Morph(slice, fxop).start(styleOn);
				}, delay);

			}, this);
		} else {
			slices.each(function (slice, i) {
				var fxop = vars.fxop;
				if (i == last) {
					fxop = Object.clone(vars.fxop);
					fxop.onComplete = this.animFinished.bind(this);
				}

				setTimeout(function () {
					new Fx.Morph(slice, fxop).start(styleOn);
				}, timeBuff);

				timeBuff += vars.sliceTime;
			}, this);
		}
	};

	this.animBox = function (effects, oldImg, curImg) {
		var options = this.options,
			vars = this.vars,
			img = vars.curImg,
			height = 0,
			opacity = 0;

		if (effects[3] == 'jelly') {
			img = oldImg;
			opacity = 1;
		}

		vars.sliceImg.set('src', effects[3] == 'jelly' ? curImg.src : oldImg.src);

		var boxes = this.createBoxes(img, opacity),
			last = options.boxCols * options.boxRows - 1,
			boxTime = vars.boxTime,
			i = 0,
			timeBuff = 100;

		if (effects[1] == 'sort') {
			var width = Math.round(options.mainWidth / options.boxCols),
				height = Math.round(options.mainHeight / options.boxRows),
				boxTime = boxTime / 3;

			this.shuffle(boxes).each(function (box) {
				var fxop = vars.fxop,
					styleOn = box.getStyles('top', 'left');

				if (i == last) {
					fxop = Object.clone(vars.fxop);
					fxop.onComplete = this.animFinished.bind(this);
				}

				box.setStyles({
					top: Math.round(Math.random() * options.boxRows / 2) * height,
					left: Math.round(Math.random() * options.boxCols / 2) * width
				});

				styleOn['opacity'] = 1;

				setTimeout(function () {
					new Fx.Morph(box, fxop).start(styleOn);
				}, timeBuff);

				timeBuff += boxTime;
				i++;
			}, this);
		} else if (effects[1] == 'random') {
			boxTime = boxTime / 3;

			this.shuffle(boxes).each(function (box) {
				var fxop = vars.fxop;
				if (i == last) {
					fxop = Object.clone(vars.fxop);
					fxop.onComplete = this.animFinished.bind(this);
				}

				setTimeout(function () {
					new Fx.Morph(box, fxop).start({ opacity: 1 });
				}, timeBuff);

				timeBuff += boxTime;
				i++;
			}, this);
		} else if (effects[1] == 'rain') {
			var rowIndex = 0,
				colIndex = 0,
				arr2d = [];

			// Split boxes into 2D array
			arr2d[rowIndex] = [];

			if (effects[2] == 'reverse') {
				boxes = boxes.reverse();
			}

			boxes.each(function (box) {
				arr2d[rowIndex][colIndex] = box;
				colIndex++;
				if (colIndex == options.boxCols) {
					rowIndex++;
					colIndex = 0;
					arr2d[rowIndex] = [];
				}
			});

			// Run animation
			var slider = this;
			for (var cols = 0; cols < (options.boxCols * 2); cols++) {
				var prevCol = cols;
				for (var rows = 0; rows < options.boxRows; rows++) {
					if (prevCol >= 0 && prevCol < options.boxCols) {

						(function (row, col, time, i) {
							var box = $(arr2d[row][col]),
								w = box.getWidth(),
								h = box.getHeight(),
								fxop = vars.fxop;

							if (i == last) {
								fxop = Object.clone(vars.fxop);
								fxop.onComplete = slider.animFinished.bind(slider);
							}

							if (effects[3] == 'grow') {
								box.setStyles({
									width: 0,
									height: 0
								});
							} else if (effects[3] == 'jelly') {
								w = 0;
								h = 0;
							}

							setTimeout(function () {
								new Fx.Morph(box, fxop).start({ opacity: 1 - opacity, width: w, height: h });
							}, time);

						})(rows, prevCol, timeBuff, i);
						i++;
					}
					prevCol--;
				}
				timeBuff += boxTime;
			}
		}
	};

	this.animSlide = function (effects, oldImg, curImg) {

		var options = this.options,
			vars = this.vars,
			img = curImg;

		if (effects[3] == 'inv') {
			img = oldImg;
		}

		vars.sliceImg.set('src', effects[3] == 'inv' ? curImg.src : oldImg.src);

		var slice = this.createSlice(img),
			fxop = Object.clone(vars.fxop),
			mapOn = { left: 'left', right: 'right', up: 'top', down: 'bottom' },
			mapOff = { left: 'right', right: 'left', up: 'bottom', down: 'top' },
			value = ['left', 'right'].contains(effects[2]) ? options.mainWidth : options.mainHeight,
			styleOn = { opacity: 1 },
			styleOff = { opacity: 0.5 };

		styleOff[mapOn[effects[2]]] = -value;
		styleOff[mapOff[effects[2]]] = '';

		styleOn[mapOn[effects[2]]] = 0;

		if (effects[3] == 'inv') {
			styleOn.opacity = 0.5;
			styleOn[mapOn[effects[2]]] = -value;

			styleOff.opacity = 1;
			styleOff[mapOn[effects[2]]] = 0;
			styleOff[mapOff[effects[2]]] = '';
		}

		slice.setStyles(styleOff);

		fxop.onComplete = this.animFinished.bind(this);

		new Fx.Morph(slice, fxop).start(styleOn);
	};

	this.shuffle = function (arr) {
		for (var j, x, i = arr.length; i; j = parseInt(Math.random() * i), x = arr[--i], arr[i] = arr[j], arr[j] = x);
		return arr;
	};

	this.css3 = function (elms, props) {
		var css = {},
			prefixes = ['moz', 'ms', 'o', 'webkit'];

		for (var prop in props) {
			// Add the vendor specific versions
			for (var i = 0; i < prefixes.length; i++) {
				css['-' + prefixes[i] + '-' + prop] = props[prop];
			}

			// Add the actual version
			css[prop] = props[prop];
		}

		elms.setStyles(css);

		return elms;
	};

	this.showDescription = function () {
		var vars = this.vars;

		vars.maskDescFx.start(vars.maskValueOn);
	};

	this.hideDescription = function () {
		var vars = this.vars;

		vars.maskDescFx.start(vars.maskValueOff);
	};

	this.swapDescription = function () {
		var vars = this.vars;

		vars.maskDesc.find('.ja-slide-desc').remove();
		const curr_desc = vars.desciptions[vars.curIdx];
		if (curr_desc.length === 0) return;
		if (curr_desc.nodeType === 1) {
			$(vars.maskDesc).append(curr_desc);
		}
	};

	this.initThumbAction = function () {
		console.log('init thumb');
		var options = this.options,
			vars = this.vars;

		var thumbWrap = vars.slider.find('.ja-slide-thumbs-wrap');
		if (!thumbWrap) {
			return false;
		}

		if (options.thumbType) {
			var thumbMask = thumbWrap.find('.ja-slide-thumbs-mask'),
				thumbBox = thumbWrap.find('.ja-slide-thumbs'),
				thumbItems = vars.thumbItems || thumbBox.find('.ja-slide-thumb'),
				handleBox = thumbWrap.find('.ja-slide-thumbs-handles'),
				handleItems = vars.handleItems || handleBox.children(),

				isHorz = (options.thumbOrientation === 'horizontal'),
				thumbAnimStyle = isHorz ? 'left' : 'top',
				thumbStep = isHorz ? options.thumbWidth + options.thumbSpaces[0] : options.thumbHeight + options.thumbSpaces[1],
				thumbStartIdx = typeof vars.thumbStartIdx != 'undefined' ? vars.thumbStartIdx : Math.max(0, Math.min(vars.curIdx - options.thumbItems + 2, vars.total - options.thumbItems));
			var fxoptions = Object.assign({}, vars.fxop);

			fxoptions.property = thumbAnimStyle;

			// var	thumbMaskFx = new Fx.Tween(thumbMask, fxoptions);
			// var thumbBoxFx = new Fx.Tween(thumbBox, fxoptions).set(-thumbStartIdx * thumbStep);

			/* var thumbMaskFx = thumbMask.animate(
				{ left: 40 }, fxoptions.duration, fxoptions.transition
			); */
			// var thumbBoxFx = thumbBox.animate(fxoptions).attr(-thumbStartIdx * thumbStep);
			/* var thumbBoxFx = thumbBox.animate(
				{ left: 40 }, fxoptions.duration, fxoptions.transition
			); */
			const thumbMaskFx = new $.Tween(thumbMask, fxoptions);
			const thumbBoxFx = new $.Tween(thumbBox, fxoptions);

			const property = `offset${this.upper_first_letter(fxoptions.property)}`;
			// property top, offsetTop = -thumbStartIdx * thumbStep
			var animationProperties = {};
			animationProperties[fxoptions.property] = -thumbStartIdx * thumbStep;
			thumbBox.animate(animationProperties, fxoptions.duration, fxoptions.transition);

			/* thumbMask.animate(
				{ left: 40 }, fxoptions.duration, fxoptions.transition
			); */

			var handleBoxFx = null;

			if (handleItems.length) {
				handleBoxFx = handleBox.animate(
					{ left: -thumbStartIdx * thumbStep }, fxoptions.duration, fxoptions.transition
				);
			}

			$([thumbBox, handleBox]).css('left', 0);
			$([handleItems, thumbItems]).each(function (key, items) {
				if (items.length) {
					$(items.css({
						'width': options.thumbWidth,
						'height': options.thumbHeight,
						'margin-right': options.thumbSpaces[0],
						'margin-bottom': options.thumbSpaces[1]
					}).removeClass('active')[vars.curIdx]).addClass('active');

					items.last().css({
						'margin-right': '',
						'margin-bottom': ''
					});
				}
			});

			if (vars.slider.hasClass('ja-articles')) {
				handleItems.css({
					'opacity': '0.001',
					'background': '#FFF'
				});
			}

			thumbMask.css(isHorz ? 'width' : 'height', 5000).css(thumbAnimStyle, thumbStartIdx * thumbStep - 2000),
				thumbWrap.css(isHorz
					? {
						'width': thumbStep * options.thumbItems - options.thumbSpaces[0],
						'height': options.thumbHeight
					}
					: {
						'width': options.thumbWidth,
						'height': thumbStep * options.thumbItems - options.thumbSpaces[1]
					});

			$([thumbWrap.find('.ja-slide-thumbs-mask-left'), thumbWrap.find('.ja-slide-thumbs-mask-right')]).css({
				'width': isHorz ? 2000 : options.thumbWidth,
				'height': isHorz ? options.thumbHeight : 2000,
				'opacity': options.thumbOpacity
			});

			thumbWrap.find('.ja-slide-thumbs-mask-center').css({
				'width': options.thumbWidth,
				'height': options.thumbHeight,
				'opacity': options.thumbOpacity
			});

			const self = this;
			handleItems.each(function (idx, item) {
				$(item).on(options.thumbTrigger, function() {
					self.prepare(true, idx);
				});
			});

			handleBox.bind('mousewheel DOMMouseScroll', function (event) {		
				if (event.originalEvent.wheelDelta > 0 || event.originalEvent.detail < 0) {
					event.preventDefault();
					self.next(true);
				}
				else {
					event.preventDefault();
					self.prev(true);
				}
			});

			Object.assign(vars, {
				thumbStartIdx: thumbStartIdx,
				thumbStep: thumbStep,
				thumbMaskFx: thumbMaskFx,
				thumbBoxFx: thumbBoxFx,
				handleBoxFx: handleBoxFx,
				thumbItems: thumbItems,
				handleItems: handleItems
			});
		} else {
			thumbWrap.css('display', 'none');
		}
	};

	this.initControlAction = function () {
		const slider = this.vars.slider;
		const controls = ['prev', 'play', 'stop', 'playback', 'next'];

		for (var j = 0, jl = controls.length; j < jl; j++) {
			if (this[controls[j]]) {
				var btnarr = slider.find('.ja-slide-' + controls[j]);
				if (btnarr.length === 0) return ;

				btnarr.on(this.options.navBtnTrigger, this[controls[j]].bind(this, true))
				.on(this.options.navBtnTrigger, function () {
					this.blur();
				});
			}
		}
	};

	this.initMainCtrlButton = function () {
		const options = this.options,
			vars = this.vars;
		const parent_wrap = this.vars.slider;
		const mainCtrlBtns = $([parent_wrap.find('.ja-slide-prev'), parent_wrap.find('.ja-slide-next')]);

		if (options.showNavBtn) {
			$.each(mainCtrlBtns, (idx, el_) => {
				const btn_control = $(el_);
				/* btn_control.css({
					'opacity': options.navBtnOpacity,
					'width': options.direction === 'horizontal'
						? Math.max(vars.rearSize - vars.mainItemSpace / 2, 0) : options.mainWidth,
					'height': options.direction === 'horizontal'
						? options.mainHeight : Math.max(0, vars.rearSize - vars.mainItemSpace / 2)
				}); */
				btn_control.off('mouseenter').off('mouseleave').on({
					mouseenter: function () {
						$(this).css('opacity', options.navBtnOpacity / 2);
					},
					mouseleave: function () {
						$(this).css('opacity', options.navBtnOpacity);
					}
				});
			});

		} else {
			mainCtrlBtns.each((idx, el_) => {
				const btn_control = $(el_);
				btn_control.css('display', 'none');
			})
		}
	};

	this.initMainItemAction = function () {
		var options = this.options;

		if (options.urls) {
			var vars = this.vars;
			var anchor = function (from, limit) {
				if (!limit) {
					limit = vars.slider;
				}
				while (from && from != limit) {
					if (typeof from.localName !== "undefined") {
						if (from.localName.toLowerCase() === 'a') {
							return from;
						}
						from = $(from).parent();
					}
				}
				return null;
			};

			var handle = function (e) {
				var index = vars.mainItems.index($(this));

				if (index == -1) {
					index = vars.curIdx;
				}

				var url = options.urls[index];
				var target = options.targets[index];
				// var link = anchor(e.target);
				var link = null;

				if (link && link.length) {
					return true;
				}

				if (url) {
					$(e).stop();
					url = url.replace(/&amp;/g, '&');
					if (target.index('_blank') !== -1) {
						window.open('https://rada.network', 'JAWindow');
					} else {
						window.location.href = url;
					}
				}

				return false;
			};
			Object.assign(vars.mainItems, [vars.mainFrame[0], vars.maskDesc[0]]).on('click', handle);
		}
	};

	this.initHoverBehavior = function () {
		var vars = this.vars,
			slider = vars.slider,
			buttons = [],
			controls = ['prev', 'play', 'stop', 'playback', 'next'];

		for (var j = 0, jl = controls.length; j < jl; j++) {
			Object.assign(buttons, new Array(slider.find('.ja-slide-' + controls[j])));
		}

		Object.assign(buttons, new Array(vars.handleItems))

		$(buttons).on({
			'mouseenter': function () {
				$(this).addClass('hover');
			},
			'mouseleave': function () {
				$(this).removeClass('hover');
			}
		});
	};

	this.initProgressBar = function () {
		var options = this.options,
			progress = this.vars.slider.find('.ja-slide-progress');

		if (!progress) {
			options.showProgress = false;

			return false;
		}

		if (options.showProgress) {
			Object.assign(this.vars, {
				progressWidth: options.mainWidth,
				progressFx: progress.animate( // new Fx.Tween
					{ width: 20 }, (options.interval - options.duration), 'linear'
				),
			});
		} else {
			progress.css('display', 'none');
		}
	};

	this.initLoader = function () {
		var vars = this.vars,
			loader = vars.slider.find('.ja-slide-loader');

		if (!loader) {
			return false;
		}

		// loaderFx: new Fx.Tween(loader, {
		Object.assign(vars, {
			loader: loader,
			loaderFx: loader.animate({  // new Fx.Tween
				opacity: .7
			}, 250)
		});
	};

	this.upper_first_letter = function (str){
		return str.charAt(0).toUpperCase() + str.slice(1);
	}

	this.initialize(this.element);
}
