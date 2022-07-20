define(['jquery'], function ($) {
	$(window).scroll(() => {
		if ($(window).scrollTop() > 168) {
			$("body").addClass('scrolling')
		} else {
			$("body").removeClass('scrolling')
		}
	})

	return
})
