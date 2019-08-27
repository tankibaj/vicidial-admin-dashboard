(function($){
    jQuery.fn.linerPlayer = function(options){
        options = $.extend({
            shuffle:false,
            autoplay:true,
            accentColor:"#008DDE",
            firstPlaying: 0,
            supplied: "mp3, m4a", //"mp3, oga, m4a"
            preload: "auto", //"metadata"
            loop: true,
            volume: 1,
            veryThin: false,
            roundedCorners: false,
            slideAlbumsName: true,
            nowplaying2title: false,
            pluginPath: "",
            keyEnabled: false,
            continuous: false,
            playlist: [{
                title: "Recording Player",
                artist: false,
                album: "Not Audio Added Yet",
                mp3: false,
                oga: false,
                m4a: false,
                cover: false
            }]
        }, options);


        var linerPlayer = function(){
            /*if (options.continuous){
             var mixedPlaylist = $.parseJSON( getCookie('linerPlaylist') );
             }else{
             var mixedPlaylist = options.playlist;
             }*/ // Fuch :)
            var mixedPlaylist = options.playlist;

            mixedPlaylist.forEach(function(el, idx, a) {
                el['mp3'] = el['file'];
                el['m4a'] = el['file'];
            });

            // ÐšÐ¾Ð½Ð²ÐµÑ€Ñ‚Ð°Ñ†Ð¸Ñ Ñ†Ð²ÐµÑ‚Ð° Ð² rgba Ð´Ð»Ñ Ð¿Ñ€Ð¾Ð·Ñ€Ð°Ñ‡Ð½Ð¾ÑÑ‚Ð¸
            function hexToRgbA (hex, alpha){
                var c;
                if(/^#([A-Fa-f0-9]{3}){1,2}$/.test(hex)){
                    c= hex.substring(1).split('');
                    if(c.length== 3){
                        c= [c[0], c[0], c[1], c[1], c[2], c[2]];
                    }
                    c= '0x'+c.join('');
                    return 'rgba('+[(c>>16)&255, (c>>8)&255, c&255].join(',')+','+alpha+')';
                }
                throw new Error('Bad color in options. Check # character.');
            }

            var template = '\
			<div id="strm_liner" class="custom">\
				<div class="strm_center">\
					<div class="strm_player">\
						<div class="blPlayer">\
							<div class="playControls">\
								<button class="new-jp-previous"><i class="previous"></i></button>\
								<button class="new-jp-play"><i class="pause"></i></button>\
								<button class="new-jp-pause" style="display:none;"><i class="play"></i></button>\
								<button class="new-jp-next"><i class="next"></i></button>\
							</div>\
							<div class="modeControls">\
								<button class="shuffle jp-shuffle on"></button>\
								<button class="shuffle active jp-shuffle-off off"></button>\
								<button class="repeat jp-repeat"></button>\
								<button class="repeat active jp-repeat-off"></button>\
								<button class="openPlaylist icon-list-1"></button>\
							</div>\
							<div class="volumeControl">\
								<button class="vol vol2 jp-mute"></button>\
								<button class="vol0 jp-unmute" style="display:none;"></button>\
								<div class="ruler jp-volume-bar">\
									<div class="trailer jp-volume-slider" style="width: 100%;"></div>\
								</div>\
							</div>\
							<div class="nowPlaying">\
								<time class="jp-time-holder">\
									<span class="time jp-current-time">0:00</span>\
									<span>/</span>\
									<span class="duration jp-duration"><small>loadingâ€¦</small></span>\
								</time>\
								<h5 class="track">\
									<div class="track">\
										<span class="title"><a class="jp-title"><small>titleâ€¦</small></a></span>&nbsp;\
										<span class="band"><a class="new-jp-artist"><small>loadingâ€¦</small></a></span>\
									</div>\
									<div class="album">\
										<span class="cd"><a class="new-jp-cd"><small>albumâ€¦</small></a></span>\
									</div>\
								</h5>\
								<div class="ruler">\
									<div class="jp-play-slider">\
										<div class="buffer jp-seek-bar"></div>\
									</div>\
									<div class="allbits" style="width:0%"></div>\
								</div>\
							</div>\
						</div>\
					</div>\
					<div class="playlist">\
						<div>\
							<section id="queue">\
								<div class="horizontal jp-playlist">\
									<ul class="queue list">	\
									</ul>\
								</div>\
							</section>\
						</div>\
					</div>\
				</div>\
			</div>\
			<div id="jplayerLiner" class="jp-jplayer"></div>\
		';

            var accentCSS = '\
				<style>\
					#strm_liner.custom .nowPlaying h5 a:hover,\
					#strm_liner.custom .list li .info h6 a:hover,\
					#strm_liner.custom .list li.jp-playlist-current h6 a{color:'+options.accentColor+';}\
					#strm_liner.custom .ruler>.allbits>.bit,\
					#strm_liner.custom .ruler .ui-slider-range,\
					#strm_liner.custom .volumeControl .ui-slider-range,\
					#strm_liner.custom .list li .playBtn:hover {background-color:'+options.accentColor+';}\
					#strm_liner.custom .list li.jp-playlist-current .controls {border: 3px solid '+options.accentColor+';}\
				</style>\
				';

            var ignore_timeupdate = false;
            var myjPlayer = false;
            var JPready = false;
            var	fixFlash_mp4_id = false;
            var	fixFlash_mp4 = false;
            var	rebuild = false;

            var shuffleOn = options.shuffle;
            var colour = options.accentColor;
            var selected = options.firstPlaying;

            var defTitle = $('title').text();

            $(this).append(template);

            if (!options.roundedCorners) { $('#strm_liner').addClass('sharpen'); }
            if (options.veryThin) { $('#strm_liner').addClass('veryThin'); }
            $('#strm_liner').before(accentCSS);
            $('#strm_liner').find(".nowPlaying .ruler").css('background-color',hexToRgbA(options.accentColor, 0.5));


            var JPlaylist = new jPlayerPlaylist({
                    jPlayer: "#jplayerLiner",
                    cssSelectorAncestor: "#strm_liner",
                },
                mixedPlaylist,
                {
                    playlistOptions: {
                        enableRemoveControls: true
                        //,shuffleTime: 1 //todo Ñ€Ð°ÑÐºÐ¾Ð¼ÐµÐ½Ñ‚Ð¸Ñ‚ÑŒ Ð² Ñ€ÐµÐ»Ð¸Ð·Ðµ
                    },

                    swfPath: options.pluginPath+'js/',
                    supplied: options.supplied,
                    preload: options.preload,
                    loop: options.loop,
                    keyEnabled: options.keyEnabled,

                    volume: options.volume,
                    smoothPlayBar: true,
                    fullScreen: true,
                    audioFullScreen: true,

                    ready: function (event) {

                        myjPlayer = event.jPlayer;

                        if (options.continuous){
                            setCookie('linerPlaylist', JSON.stringify(JPlaylist.playlist));
                        }

                        //Playlist managing
                        $('.plManager').click(function(e){
                            e.preventDefault();

                            if ($(this).data('action') == 'clear'){
                                $(JPlaylist.cssSelector.jPlayer).jPlayer('stop');
                                JPlaylist.remove();
                                $(event.jPlayer.options.cssSelectorAncestor + ' .nowPlaying .new-jp-cd').text(' ');
                                $(event.jPlayer.options.cssSelectorAncestor + ' .nowPlaying .new-jp-artist').text(' ');
                                $(event.jPlayer.options.cssSelectorAncestor + ' .nowPlaying .jp-title').text('Playlist is empty. Add new songs!');

                                makebits();

                                if(!event.jPlayer.status.noVolume){
                                    myScroll.refresh();
                                }
                            }
                            if ($(this).data('action') == 'add'){
                                JPlaylist.add({
                                    title:$(this).data('title'),
                                    artist:$(this).data('artist'),
                                    album:$(this).data('album'),
                                    mp3:$(this).data('mp3'),
                                    m4a:$(this).data('mp3'),
                                    cover: $(this).data('cover')
                                });
                                rebuildPl(event);

                                JPlaylist.play(-1);

                                if(!event.jPlayer.status.noVolume){
                                    myScroll.refresh();
                                }
                            }
                            if ($(this).data('action') == 'add-no-play'){
                                JPlaylist.add({
                                    title:$(this).data('title'),
                                    artist:$(this).data('artist'),
                                    album:$(this).data('album'),
                                    mp3:$(this).data('mp3'),
                                    m4a:$(this).data('mp3'),
                                    cover: $(this).data('cover')
                                });
                                rebuildPl(event);

                                if(!event.jPlayer.status.noVolume){
                                    myScroll.refresh();
                                }
                            }
                            if ($(this).data('action') == 'play'){
                                JPlaylist.play($(this).data('id'));
                                $(this).addClass('clickedPlay');
                                $(this).removeClass('clickedPause');
                                $(this).removeClass('clickedStop');
                            }
                            if ($(this).data('action') == 'pause'){
                                $(JPlaylist.cssSelector.jPlayer).jPlayer('pause');
                                $(this).addClass('clickedPause');
                                $(this).removeClass('clickedPlay');
                                $(this).removeClass('clickedStop');
                            }
                            if ($(this).data('action') == 'stop'){
                                $(JPlaylist.cssSelector.jPlayer).jPlayer('stop');
                                $(this).addClass('clickedStop');
                                $(this).removeClass('clickedPause');
                                $(this).removeClass('clickedPlay');
                            }
                        });

                        //Ð¾Ñ‚ÑÑ‚ÑƒÐ¿Ñ‹ Ð´Ð»Ñ body
                        if (screen.width <= 480){
                            $('body').css('margin-bottom',$('body').css('margin-bottom').split('px')[0].split('em')[0]*1+88);
                        }else{
                            $('body').css('margin-bottom',$('body').css('margin-bottom').split('px')[0].split('em')[0]*1+58);
                        }

                        // Ð‘Ð¸Ð½Ð´Ð¸Ð½Ð³ Ð·Ð°Ñ†Ð¸ÐºÐ»Ð¸Ð²Ð°Ð½Ð¸Ñ
                        if(myjPlayer.options.loop) {
                            $(JPlaylist.cssSelector.jPlayer).unbind($.jPlayer.event.ended).bind($.jPlayer.event.ended, looper);
                        }else{
                            $(JPlaylist.cssSelector.jPlayer).unbind($.jPlayer.event.ended).bind($.jPlayer.event.ended, looperOff);
                        }

                        //Ð’Ð¾Ð·Ñ€Ð°Ñ‰ÐµÐ½Ð¸Ðµ Ð½Ð¾Ñ€Ð¼Ð°Ð»ÑŒÐ½Ð¾Ð³Ð¾ Ñ‚Ð°Ð¹Ñ‚Ð»Ð° Ð½Ð° Ð¼ÐµÑÑ‚Ð¾
                        if (options.nowplaying2title){
                            if(defTitle){
                                $(JPlaylist.cssSelector.jPlayer).bind($.jPlayer.event.pause, function(){
                                    $('title').text(defTitle);
                                });
                            }
                            $(JPlaylist.cssSelector.jPlayer).bind($.jPlayer.event.play, function(){
                                $('title').text($(event.jPlayer.options.cssSelectorAncestor + ' .nowPlaying .jp-title').text()+' - '+$(event.jPlayer.options.cssSelectorAncestor + ' .nowPlaying .new-jp-artist').text());
                            });
                        }


                        if(shuffleOn && !options.continuous) {
                            $(JPlaylist.cssSelector.cssSelectorAncestor + " .jp-shuffle").hide();
                            $(JPlaylist.cssSelector.cssSelectorAncestor + " .jp-shuffle-off").show();
                            shuffler();
                        }

                        // Reduild playlist DOM
                        rebuildPl(event);


                        if(event.jPlayer.status.noVolume){
                            $(event.jPlayer.options.cssSelectorAncestor + ' .strm_player').addClass('volHidden');
                        }else{
                            //Add smooth scrolling
                            myScroll = new IScroll(event.jPlayer.options.cssSelectorAncestor + ' .horizontal', {
                                //preventDefault: false, //fixed 532,391 lines at iScroll v5.1.2
                                scrollX: true,
                                scrollY: false,
                                scrollbars: true,
                                mouseWheel: true,
                                interactiveScrollbars: true,
                                scrollbars: 'custom',
                                shrinkScrollbars: 'scale',
                                fadeScrollbars: true
                            });
                        }

                        JPlaylist.select(selected);
                        setSlides(event);
                        JPready = true;

                        if (options.continuous){

                            //console.log("Ñ€Ð°Ð±Ð¾Ñ‚Ð°ÑÐºÑƒÐºÐ¸ Ð’ÐºÐ»ÑŽÑ‡ÐµÐ½Ð¸Ðµ Ð¿Ñ€Ð°Ð²Ð¸Ð»ÑŒÐ½Ð¾Ð³Ð¾ Ñ‚Ñ€ÐµÐºÐ°");
                            JPlaylist.select(getCookie('linerSelected')*1);

                            if (getCookie('linerPlay')*1 == 1){
                                $(JPlaylist.cssSelector.cssSelectorAncestor + " .playControls .new-jp-play").addClass('playing').hide();
                                $(JPlaylist.cssSelector.cssSelectorAncestor + " .playControls .new-jp-pause").show().addClass('playing');

                                $(JPlaylist.cssSelector.jPlayer).jPlayer('play', getCookie('linerTime')*1);
                            }else{
                                $(JPlaylist.cssSelector.jPlayer).jPlayer('pause', getCookie('linerTime')*1);
                            }

                            //console.log("Ñ€Ð°Ð±Ð¾Ñ‚Ð°ÑÐºÑƒÐºÐ¸ Ð“Ñ€Ð¾Ð¼ÐºÐ¾ÑÑ‚ÑŒ");
                            var cookieVolume = getCookie('linerVolume') ? getCookie('linerVolume')*1 : options.volume;
                            $(JPlaylist.cssSelector.jPlayer).jPlayer("option", "volume", cookieVolume);
                            myControl.volume.slider("value", cookieVolume);
                        }

                        if (options.autoplay && !event.jPlayer.status.noVolume && !options.continuous){
                            // Ð¤Ð¸ÐºÑ Ð·Ð°Ð´ÐµÑ€Ð¶ÐºÐ¸ Ð¿ÐµÑ€Ð²Ð¾Ð³Ð¾ Ð¿ÐµÑ€ÐµÐºÐ»ÑŽÑ‡ÐµÐ½Ð¸Ñ ÐºÐ½Ð¾Ð¿ÐºÐ¸ Ð¿Ð»ÐµÐ¹-Ð¿Ð°ÑƒÐ·Ð°
                            $(JPlaylist.cssSelector.cssSelectorAncestor + " .playControls .new-jp-play").addClass('playing').hide();
                            $(JPlaylist.cssSelector.cssSelectorAncestor + " .playControls .new-jp-pause").show().addClass('playing');

                            JPlaylist.play();
                        }

                        // ÐŸÐ¾ÑÐ²Ð»ÐµÐ½Ð¸Ðµ ÑÐºÑ€Ñ‹Ñ‚Ð¸Ðµ Ð½Ð°Ð·Ð²Ð°Ð½Ð¸Ñ / Ð°Ð»ÑŒÐ±Ð¾Ð¼Ð°
                        if(options.slideAlbumsName){
                            setTimeout(function(){
                                $(event.jPlayer.options.cssSelectorAncestor + ' .nowPlaying h5').removeClass('track').addClass('album');
                            },2500);
                            setTimeout(function(){
                                $(event.jPlayer.options.cssSelectorAncestor + ' .nowPlaying h5').removeClass('album').addClass('track');
                            },6500);
                            setInterval(function(){
                                $(event.jPlayer.options.cssSelectorAncestor + ' .nowPlaying h5').removeClass('track').addClass('album');
                                setTimeout(function(){
                                    $(event.jPlayer.options.cssSelectorAncestor + ' .nowPlaying h5').removeClass('album').addClass('track');
                                },4500);
                            }, 10000);
                        }

                        $(event.jPlayer.options.cssSelectorAncestor + ' .strm_player').addClass('show');
                    },
                    volumechange: function (event) {
                        myjPlayer = event.jPlayer;

                        // Ð¡Ð»Ð°Ð¹Ð´ÐµÑ€ Ð·Ð²ÑƒÐºÐ°
                        if(event.jPlayer.options.muted) {
                            myControl.volume.slider("value", 0);
                        } else {
                            myControl.volume.slider("value", event.jPlayer.options.volume);
                        }

                        setCookie('linerVolume', $(JPlaylist.cssSelector.jPlayer).jPlayer("option", "volume"));

                        // Ð¡Ð¼ÐµÐ½Ð° Ð¸ÐºÐ¾Ð½ÐºÐ¸ Ð·Ð²ÑƒÐºÐ°
                        if ($(JPlaylist.cssSelector.jPlayer).jPlayer("option", "volume") == 0){
                            $(event.jPlayer.options.cssSelectorAncestor + ' .vol').removeClass('volmute').removeClass('vol0').removeClass('vol1').removeClass('vol2').addClass('volmute');
                            return;
                        }
                        if ($(JPlaylist.cssSelector.jPlayer).jPlayer("option", "volume") < 0.2){
                            $(event.jPlayer.options.cssSelectorAncestor + ' .vol').removeClass('volmute').removeClass('vol0').removeClass('vol1').removeClass('vol2').addClass('vol0');
                            return;
                        }
                        if ($(JPlaylist.cssSelector.jPlayer).jPlayer("option", "volume") < 0.7){
                            $(event.jPlayer.options.cssSelectorAncestor + ' .vol').removeClass('volmute').removeClass('vol0').removeClass('vol1').removeClass('vol2').addClass('vol1');
                            return;
                        }
                        if ($(JPlaylist.cssSelector.jPlayer).jPlayer("option", "volume") <= 1){
                            $(event.jPlayer.options.cssSelectorAncestor + ' .vol').removeClass('volmute').removeClass('vol0').removeClass('vol1').removeClass('vol2').addClass('vol2');

                        }
                    },
                    setmedia: function (event) {
                        // Ð’Ð¾Ð·Ð²Ñ€Ð°Ñ‰Ð°ÐµÐ¼ Ð½Ð° Ð¼ÐµÑÑ‚Ð¾ Ð½Ð°Ð·Ð²Ð°Ð½Ð¸Ðµ
                        $(event.jPlayer.options.cssSelectorAncestor + ' .nowPlaying h5').removeClass('album').addClass('track');

                        // ÐšÐ¾ÑÑ‚Ñ‹Ð»ÑŒ Ð´Ð»Ñ Ð¿Ñ€ÐµÐ¾Ð±Ñ€Ð°Ð·Ð¾Ð²Ð°Ð½Ð¸Ñ Ð¿Ð»ÐµÐ¹Ð»Ð¸ÑÑ‚Ð° (Ñƒ jplayer Ð½ÐµÑ‚ Ð½Ð¾Ñ€Ð¼Ð°Ð»ÑŒÐ½Ð¾Ð³Ð¾ ÑÐ¾Ð±Ñ‹Ñ‚Ð¸Ñ shuffle)
                        if (rebuild){
                            rebuildPl(event);
                            rebuild = false;
                        }


                        $(event.jPlayer.options.cssSelectorAncestor + ' .nowPlaying .new-jp-artist').text($(event.jPlayer.options.cssSelectorAncestor + ' .playlist ul li:nth-child('+(JPlaylist.current+1)+') .new-jp-artist').text());
                        $(event.jPlayer.options.cssSelectorAncestor + ' .nowPlaying .jp-title').text($(event.jPlayer.options.cssSelectorAncestor + ' .playlist ul li:nth-child('+(JPlaylist.current+1)+') .new-jp-title').text());
                        $(event.jPlayer.options.cssSelectorAncestor + ' .nowPlaying .new-jp-cd').text($(event.jPlayer.options.cssSelectorAncestor + ' .playlist ul li:nth-child('+(JPlaylist.current+1)+') .new-jp-artist').data('cd'));


                        makebits();
                    },
                    keyBindings: { //Functional Buttons clicks (Win)
                        play: {
                            key: 179,
                            fn: function(f) {
                                if(f.status.paused) {
                                    f.play();
                                } else {
                                    f.pause();
                                }
                            }
                        },
                        stop: {
                            key: 178,
                            fn: function(f) {
                                f.stop();
                            }
                        },
                        next: {
                            key: 176, // RIGHT
                            fn: function(f) {
                                JPlaylist.next();
                            }
                        },
                        previous: {
                            key: 177, // LEFT
                            fn: function(f) {
                                JPlaylist.previous();
                            }
                        },
                        volumeUp: {
                            key: 38, // UP
                            fn: function(f) {
                                return false;
                            }
                        },
                        volumeDown: {
                            key: 40, // DOWN
                            fn: function(f) {
                                return false;
                            }
                        }
                    },
                    timeupdate: function(event) {
                        myjPlayer = event.jPlayer;
                        // ÐŸÑ€Ð¾Ñ€Ð¸ÑÐ¾Ð²ÐºÐ° Ñ‚ÐµÐºÑƒÑ‰ÐµÐ¹ Ð¿Ð¾Ð·Ð¸Ñ†Ð¸Ð¸ Ñ‚Ñ€ÐµÐºÐ°
                        if(!ignore_timeupdate && JPready) {
                            myControl.progress.slider("value", event.jPlayer.status.currentPercentAbsolute);
                            $(event.jPlayer.options.cssSelectorAncestor + ' .allbits').stop().animate({'width': event.jPlayer.status.currentPercentAbsolute+'%'}, 'fast');
                        }

                        if (myjPlayer.status.currentTime != 0){
                            setCookie('linerTime', myjPlayer.status.currentTime);
                            setCookie('linerSelected', JPlaylist.current);
                        }
                    }
                });

            // ÐžÐ¿Ð¸ÑÑ‹Ð²Ð°Ð½Ð¸Ðµ ÑÐ»Ð°Ð¹Ð´ÐµÑ€Ð¾Ð²
            var	myControl = {
                progress: $(this).find(".jp-play-slider"),
                volume: $(this).find(".jp-volume-slider")
            };
            function setSlides(event){
                myControl.progress.slider({
                    animate: 'fast',
                    max: 100,
                    range: "min",
                    step: 0.1,
                    value : 0,
                    slide: function(e, ui) {
                        var sp = myjPlayer.status.seekPercent;
                        var value = ui.value;
                        if(sp > 0) {
                            // Apply a fix to mp4 formats when the Flash is used.
                            if(fixFlash_mp4) {
                                ignore_timeupdate = true;
                                clearTimeout(fixFlash_mp4_id);
                                fixFlash_mp4_id = setTimeout(function() {
                                    ignore_timeupdate = false;
                                },1000);
                            }
                            // Move the play-head to the value and factor in the seek percent.
                            $(JPlaylist.cssSelector.jPlayer).jPlayer("playHead", value * (100 / sp));
                        } else {
                            // Create a timeout to reset this slider to zero.
                            setTimeout(function() {
                                myControl.progress.slider("value", 0);
                            }, 0);
                        }
                    }
                });

                // Create the volume slider control
                myControl.volume.slider({
                    animate: true,
                    max: 1,
                    range: "min",
                    step: 0.01,
                    value : myjPlayer.options.volume,
                    slide: function(e, ui) {
                        var value = ui.value;
                        $(JPlaylist.cssSelector.jPlayer).jPlayer("option", "muted", false);
                        $(JPlaylist.cssSelector.jPlayer).jPlayer("option", "volume", value);
                    }
                });
            }

            // function for make playlist user-friendly
            function rebuildPl(event){
                var i = -1;
                var tpl = '';

                $(JPlaylist.cssSelector.cssSelectorAncestor + " .jp-playlist li").each(function(){
                    tpl = '\
					<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyJpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuMy1jMDExIDY2LjE0NTY2MSwgMjAxMi8wMi8wNi0xNDo1NjoyNyAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENTNiAoV2luZG93cykiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6MDhGOTczMkUyNUY4MTFFNEJDNDdFMTBDNjc0MDA0NDciIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6MDhGOTczMkYyNUY4MTFFNEJDNDdFMTBDNjc0MDA0NDciPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDowOEY5NzMyQzI1RjgxMUU0QkM0N0UxMEM2NzQwMDQ0NyIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDowOEY5NzMyRDI1RjgxMUU0QkM0N0UxMEM2NzQwMDQ0NyIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/Pq6fqRoAAAAQSURBVHjaYvj//z8DQIABAAj8Av7bok0WAAAAAElFTkSuQmCC" class="new-jp-cover">\
					<div class="info">\
							<h6><a class="new-jp-title">No Title</a></h6>\
							<a class="new-jp-artist" data-cd="">No Band</a>\
					</div>\
					<div class="clr"></div>\
					<div class="controls">\
							<a class="playBtn"></a>\
					</div>\
				';


                    i++;
                    $(this).html(tpl);
                    $(this).data('i',i);
                    setID3orTags(event, i);

                });

                $(JPlaylist.cssSelector.cssSelectorAncestor + " .jp-playlist li, "+JPlaylist.cssSelector.cssSelectorAncestor+" .playControls .new-jp-play, "+JPlaylist.cssSelector.cssSelectorAncestor+" .playControls .new-jp-pause").unbind('click',goPlaying).bind('click',goPlaying);//.bind('touchend',goPlaying);

                // Ð¡Ñ‚Ð°Ð²Ð¸Ð¼ Ð² now playing Ñ‚ÐµÐºÑƒÑ‰ÑƒÑŽ Ð¿ÐµÑÐ½ÑŽ
                $(event.jPlayer.options.cssSelectorAncestor + ' .nowPlaying .new-jp-artist').text($(event.jPlayer.options.cssSelectorAncestor + ' .playlist ul li:nth-child('+(selected+1)+') .new-jp-artist').text());
                $(event.jPlayer.options.cssSelectorAncestor + ' .nowPlaying .jp-title').text($(event.jPlayer.options.cssSelectorAncestor + ' .playlist ul li:nth-child('+(selected+1)+') .new-jp-title').text());
                $(event.jPlayer.options.cssSelectorAncestor + ' .nowPlaying .new-jp-cd').text($(event.jPlayer.options.cssSelectorAncestor + ' .playlist ul li:nth-child('+(selected+1)+') .new-jp-artist').data('cd'));


            }
            function setID3orTags(event, i){
                var tags = false;

                var thisLi = $(JPlaylist.cssSelector.cssSelectorAncestor + " .jp-playlist li:nth-child("+(i+1)+")");

                if (JPlaylist.playlist[i].cover){
                    thisLi.find('.new-jp-cover').attr('src',JPlaylist.playlist[i].cover);
                }else{
                    thisLi.find('.new-jp-cover').addClass('noCover');
                }

                if (JPlaylist.playlist[i].artist){
                    thisLi.find('.new-jp-artist').text(JPlaylist.playlist[i].artist);
                }

                if (JPlaylist.playlist[i].title){
                    thisLi.find('.new-jp-title').text(JPlaylist.playlist[i].title);
                }

                if(JPlaylist.playlist[i].album){
                    thisLi.find('.new-jp-artist').data('cd',JPlaylist.playlist[i].album);
                }else{
                    thisLi.find('.new-jp-artist').data('cd','Unknown album');
                }

                if (! (JPlaylist.playlist[i].title && JPlaylist.playlist[i].artist && JPlaylist.playlist[i].cover)){
                    // Ð—ÐÐ¿Ð¾Ð»Ð½ÑÐµÐ¼ ID3 Ñ‚ÐµÐ³Ð°Ð¼Ð¸
                    ID3.loadTags(JPlaylist.playlist[i].mp3, function() {
                        tags = ID3.getAllTags(JPlaylist.playlist[i].mp3);

                        if (!JPlaylist.playlist[i].artist && tags.artist){
                            thisLi.find('.new-jp-artist').text(tags.artist);
                        }

                        if (!JPlaylist.playlist[i].title && tags.title){
                            thisLi.find('.new-jp-title').text(tags.title);
                        }

                        if(JPlaylist.playlist[i].album){
                            thisLi.find('.new-jp-artist').data('cd',JPlaylist.playlist[i].album);
                        }else{
                            if (tags.album){
                                thisLi.find('.new-jp-artist').data('cd',tags.album);
                            }else{
                                thisLi.find('.new-jp-artist').data('cd','Unknown album');
                            }
                        }

                        if (!JPlaylist.playlist[i].cover){
                            var image = tags.picture;
                            if (image) {
                                var base64String = "";
                                for (var j = 0; j < image.data.length; j++) {
                                    base64String += String.fromCharCode(image.data[j]);
                                }
                                var base64 = "data:" + image.format + ";base64," + window.btoa(base64String);
                                thisLi.find('.new-jp-cover').attr('src',base64);
                                thisLi.find('.new-jp-cover').removeClass('noCover');
                            } else {
                                thisLi.find('.new-jp-cover').addClass('noCover');
                            }
                        }
                    }, {
                        tags: ["title","artist","album","picture"]
                    });
                }

            }

            // ÐžÐ±Ñ€Ð°Ð±Ð¾Ñ‚Ñ‡Ð¸ÐºÐ¸ ÐºÐ½Ð¾Ð¿Ð¾Ðº Ð²Ð¿ÐµÑ€ÐµÐ´ Ð¸ Ð½Ð°Ð·Ð°Ð´
            $(JPlaylist.cssSelector.cssSelectorAncestor + " .new-jp-next").click(function() {
                JPlaylist.next();
            });
            $(JPlaylist.cssSelector.cssSelectorAncestor + " .new-jp-previous").click(function() {
                JPlaylist.previous();
            });


            // Ð¤ÑƒÐ½ÐºÑ†Ð¸Ð¸ Ð¾Ð±Ñ€Ð°Ð±Ð¾Ñ‚ÐºÐ¸ Ð·Ð°Ñ†Ð¸ÐºÐ»Ð¸Ð²Ð°Ð½Ð¸Ñ
            function looper(){
                JPlaylist.play();
            }
            function looperOff(){
                JPlaylist.next();
            }
            $(JPlaylist.cssSelector.cssSelectorAncestor + " .jp-repeat").click(function() {
                $(JPlaylist.cssSelector.jPlayer).unbind($.jPlayer.event.ended, looper).unbind($.jPlayer.event.ended, looperOff).bind($.jPlayer.event.ended, looper);
                $(JPlaylist.cssSelector.jPlayer).jPlayer("option","loop",true);
            });
            $(JPlaylist.cssSelector.cssSelectorAncestor + " .jp-repeat-off").click(function() {
                $(JPlaylist.cssSelector.jPlayer).unbind($.jPlayer.event.ended, looper).unbind($.jPlayer.event.ended, looperOff).bind($.jPlayer.event.ended, looperOff);
                $(JPlaylist.cssSelector.jPlayer).jPlayer("option","loop",false);
            });

            // Ð˜Ð·Ð¼ÐµÐ½Ð¸Ñ‚ÑŒ ÐºÐ½Ð¾Ð¿ÐºÑƒ Ð´Ð»Ñ Ñ‚ÐµÐºÑƒÑ‰ÐµÐ³Ð¾ Ñ‚Ñ€ÐµÐºÐ° Ð² Ð¿Ð»ÐµÐ¹Ð»Ð¸ÑÑ‚Ðµ
            $(JPlaylist.cssSelector.jPlayer).bind($.jPlayer.event.play, function(){
                setCookie('linerPlay',1);
                $(JPlaylist.cssSelector.cssSelectorAncestor + " .playlist li").removeClass('playing');
                $(JPlaylist.cssSelector.cssSelectorAncestor + " .jp-playlist li:nth-child("+(JPlaylist.current+1)+")").addClass('playing');

                $(JPlaylist.cssSelector.cssSelectorAncestor + " .playControls .new-jp-play").addClass('playing').hide();
                $(JPlaylist.cssSelector.cssSelectorAncestor + " .playControls .new-jp-pause").show().addClass('playing');
            });
            $(JPlaylist.cssSelector.jPlayer).bind($.jPlayer.event.pause, function(){
                setCookie('linerPlay',0);
                $(JPlaylist.cssSelector.cssSelectorAncestor + " .jp-playlist li:nth-child("+(JPlaylist.current+1)+")").removeClass('playing');
                $(JPlaylist.cssSelector.cssSelectorAncestor + " .playControls .new-jp-pause").removeClass('playing').hide();
                $(JPlaylist.cssSelector.cssSelectorAncestor + " .playControls .new-jp-play").show().removeClass('playing');
            });

            // Ð¤ÑƒÐ½ÐºÑ†Ð¸Ð¸ Ð¾Ð±Ñ€Ð°Ð±Ð¾Ñ‚ÐºÐ¸ Ð¿ÐµÑ€ÐµÐ¼ÐµÑˆÐ¸Ð²Ð°Ð½Ð¸Ñ
            function shuffler(){
                JPlaylist.playlist.sort(function() {
                    return 0.5 - Math.random();
                });
                if (options.continuous){
                    setCookie('linerPlaylist', JSON.stringify(JPlaylist.playlist));
                }
                JPlaylist.shuffled = true;
                rebuild = true;
            }
            function shufflerOff(){
                JPlaylist._originalPlaylist();
                if (options.continuous){
                    setCookie('linerPlaylist', JSON.stringify(JPlaylist.playlist));
                }
                rebuild = true;
            }
            $(JPlaylist.cssSelector.cssSelectorAncestor + " .jp-shuffle").click(function() {
                shuffler();
            });
            $(JPlaylist.cssSelector.cssSelectorAncestor + " .jp-shuffle-off").click(function() {
                shufflerOff();
            });

            // ÐšÐ»Ð¸ÐºÐ¸ Ð¿Ð¾ Ð¿ÐµÑÐ½ÑÐ¼ Ð¿Ð»ÐµÐ¹Ð»Ð¸ÑÑ‚Ð°
            function goPlaying(event){
                if ($(this).hasClass('playing')){
                    $(JPlaylist.cssSelector.jPlayer).jPlayer("pause");
                }else{
                    JPlaylist.play($(this).data('i'));
                    $(JPlaylist.cssSelector.cssSelectorAncestor + " .playlist li").removeClass('playing');
                }
            }


            // ÐžÑ‡Ð¸Ñ‰Ð°ÐµÐ¼ Ð¸ Ð Ð¸ÑÑƒÐµÐ¼ Ð±Ð¸Ñ‚Ñ‹ Ð¿Ð¾Ð´ Ð¿Ð¾Ð»Ð¾ÑÐºÐ¾Ð¹
            function makebits(event){
                $(myjPlayer.options.cssSelectorAncestor + ' .allbits').stop().css('width',0).empty();
                var countpix = Math.round(screen.width/2);
                var i=0;
                while (i<countpix){
                    i++;
                    heigth = Math.round(Math.random()*10)+4;
                    minustop = Math.floor(6-(heigth-1)/2)+1;
                    $(myjPlayer.options.cssSelectorAncestor + ' .allbits').append('<div class="bit" style="margin-top:'+minustop+'px; height:'+heigth+'px;" ></div>');
                }
            }
        };
        return this.each(linerPlayer);
    };
})(jQuery);


/**
 *
 * Base64 encode/decode
 * http://www.webtoolkit.info
 *
 **/

var Base64 = {
    _keyStr : "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",
    //Ð¼ÐµÑ‚Ð¾Ð´ Ð´Ð»Ñ ÐºÐ¾Ð´Ð¸Ñ€Ð¾Ð²ÐºÐ¸ Ð² base64 Ð½Ð° javascript
    encode : function (input) {
        var output = "";
        var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
        var i = 0;
        input = Base64._utf8_encode(input);
        while (i < input.length) {
            chr1 = input.charCodeAt(i++);
            chr2 = input.charCodeAt(i++);
            chr3 = input.charCodeAt(i++);
            enc1 = chr1 >> 2;
            enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
            enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
            enc4 = chr3 & 63;
            if( isNaN(chr2) ) {
                enc3 = enc4 = 64;
            }else if( isNaN(chr3) ){
                enc4 = 64;
            }
            output = output +
            this._keyStr.charAt(enc1) + this._keyStr.charAt(enc2) +
            this._keyStr.charAt(enc3) + this._keyStr.charAt(enc4);
        }
        return output;
    },

    //Ð¼ÐµÑ‚Ð¾Ð´ Ð´Ð»Ñ Ñ€Ð°ÑÐºÐ¾Ð´Ð¸Ñ€Ð¾Ð²ÐºÐ¸ Ð¸Ð· base64
    decode : function (input) {
        var output = "";
        var chr1, chr2, chr3;
        var enc1, enc2, enc3, enc4;
        var i = 0;
        input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");
        while (i < input.length) {
            enc1 = this._keyStr.indexOf(input.charAt(i++));
            enc2 = this._keyStr.indexOf(input.charAt(i++));
            enc3 = this._keyStr.indexOf(input.charAt(i++));
            enc4 = this._keyStr.indexOf(input.charAt(i++));
            chr1 = (enc1 << 2) | (enc2 >> 4);
            chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
            chr3 = ((enc3 & 3) << 6) | enc4;
            output = output + String.fromCharCode(chr1);
            if( enc3 != 64 ){
                output = output + String.fromCharCode(chr2);
            }
            if( enc4 != 64 ) {
                output = output + String.fromCharCode(chr3);
            }
        }
        output = Base64._utf8_decode(output);
        return output;
    },
    // Ð¼ÐµÑ‚Ð¾Ð´ Ð´Ð»Ñ ÐºÐ¾Ð´Ð¸Ñ€Ð¾Ð²ÐºÐ¸ Ð² utf8
    _utf8_encode : function (string) {
        string = string.replace(/\r\n/g,"\n");
        var utftext = "";
        for (var n = 0; n < string.length; n++) {
            var c = string.charCodeAt(n);
            if( c < 128 ){
                utftext += String.fromCharCode(c);
            }else if( (c > 127) && (c < 2048) ){
                utftext += String.fromCharCode((c >> 6) | 192);
                utftext += String.fromCharCode((c & 63) | 128);
            }else {
                utftext += String.fromCharCode((c >> 12) | 224);
                utftext += String.fromCharCode(((c >> 6) & 63) | 128);
                utftext += String.fromCharCode((c & 63) | 128);
            }
        }
        return utftext;

    },

    //Ð¼ÐµÑ‚Ð¾Ð´ Ð´Ð»Ñ Ñ€Ð°ÑÐºÐ¾Ð´Ð¸Ñ€Ð¾Ð²ÐºÐ¸ Ð¸Ð· urf8
    _utf8_decode : function (utftext) {
        var string = "";
        var i = 0;
        var c = c1 = c2 = 0;
        while( i < utftext.length ){
            c = utftext.charCodeAt(i);
            if (c < 128) {
                string += String.fromCharCode(c);
                i++;
            }else if( (c > 191) && (c < 224) ) {
                c2 = utftext.charCodeAt(i+1);
                string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
                i += 2;
            }else {
                c2 = utftext.charCodeAt(i+1);
                c3 = utftext.charCodeAt(i+2);
                string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
                i += 3;
            }
        }
        return string;
    }
};

/**
 *
 * Cookies Set/Read
 * http://www.stormybyte.com
 *
 **/
function setCookie(c_name, value) {
    var exdate = new Date();
    var exdays = 1;
    exdate.setDate(exdate.getDate() + exdays);
    var c_value = escape(value) + ((exdays == null) ? "" : "; expires=" + exdate.toUTCString() + "; path=/;");
    document.cookie = c_name + "=" + c_value;
}

function getCookie(c_name) {
    var c_value = document.cookie;
    var c_start = c_value.indexOf(" " + c_name + "=");
    if (c_start == -1) {
        c_start = c_value.indexOf(c_name + "=");
    }
    if (c_start == -1) {
        c_value = null;
    } else {
        c_start = c_value.indexOf("=", c_start) + 1;
        var c_end = c_value.indexOf(";", c_start);
        if (c_end == -1) {
            c_end = c_value.length;
        }
        c_value = unescape(c_value.substring(c_start, c_end));
    }
    return c_value;
}