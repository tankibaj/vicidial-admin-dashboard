jQuery(function($) {
	$('.now .colours .make.thin,.now .colours .make.large').click(function() {
		if ($(this).hasClass('thin')){
			$('#strm_liner').addClass('veryThin');
		}else{
			$('#strm_liner').removeClass('veryThin');		
		}
		
		if($('#strm_liner').hasClass('veryThin')){
			$('.playlist').css('bottom', 33);
		}else{
			$('.playlist').css('bottom', 58);
		}
	});
	$('.now .colours .colors').click(function() {
		$("#strm_liner")
			.removeClass('red')
			.removeClass('orange')
			.removeClass('yellow')
			.removeClass('green')
			.removeClass('blu')
			.removeClass('blue')
			.removeClass('magenta')
			.removeClass('white')
			.removeClass('custom');
		$("#strm_liner").addClass($(this).data('colour'));
		$("#strm_liner .nowPlaying .ruler").css('background-color', hexToRgbA1($(this).data('rgb'), 0.5));
		
		$(".green").css('color', $(this).data('rgb'));
	});
	$('.now .colours').hover(function() {
		if($('#strm_liner').hasClass('veryThin')){
			$('.playlist').css('bottom', 33);
		}else{
			$('.playlist').css('bottom', 58);
		}
	}, function() {
		$('.playlist').removeAttr('style');
	});
});
function hexToRgbA1(hex, al) {
	var c;
	if (/^#([A-Fa-f0-9]{3}){1,2}$/.test(hex)) {
		c = hex.substring(1).split('');
		if (c.length == 3) {
			c = [c[0], c[0], c[1], c[1], c[2], c[2]];
		}
		c = '0x' + c.join('');
		return 'rgba(' + [(c >> 16) & 255, (c >> 8) & 255, c & 255].join(',') + ',' + al + ')';
	}
	throw new Error('Bad color in options. Check # character.');
}