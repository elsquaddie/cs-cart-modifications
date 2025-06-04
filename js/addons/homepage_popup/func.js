(function(_, $) { // Tygh Utilities and jQuery
    $(document).ready(function() {
        // Existing popup closing logic
        var popup_wrapper = $('#homepage_popup_wrapper');
        var popup_overlay = $('#homepage_popup_overlay');
        var close_button = $('#close_homepage_popup');
        var cookie_name = 'homepage_popup_closed';

        // Renamed existing functions to avoid potential conflicts if this file grows
        function showMainPopupContainer() {
            if (popup_overlay.length && popup_wrapper.data('dimming-enabled') === 'Y') {
                popup_overlay.show();
            }
            if (popup_wrapper.length) {
                popup_wrapper.show(); // This makes the entire wrapper visible
            }
        }

        function hideMainPopupContainer() {
            if (popup_overlay.length) {
                popup_overlay.hide();
            }
            if (popup_wrapper.length) {
                popup_wrapper.hide();
            }
        }

        // This logic determines if the popup (wrapper) should be visible at all on page load
        if (popup_wrapper.length && popup_wrapper.css('display') === 'block') {
            // If server decided to show it (via $show_homepage_popup in TPL resulting in display:block)
            // and overlay is enabled, show overlay too. The wrapper is already 'display:block'.
            if (popup_overlay.length && popup_wrapper.data('dimming-enabled') === 'Y') {
                popup_overlay.show();
            }
        } else if ($.cookies.get(cookie_name) === 'true') {
            // If cookie says closed, ensure it's hidden (this might be redundant if TPL already handles it via $show_homepage_popup)
             hideMainPopupContainer();
        }

        if (close_button.length) {
            close_button.on('click', function() {
                hideMainPopupContainer();
                $.cookies.set(cookie_name, 'true', { path: '/' });
            });
        }

        // Optional: Close popup if overlay is clicked (remains commented as per original)
        if (popup_overlay.length && popup_wrapper.data('dimming-enabled') === 'Y') {
            popup_overlay.on('click', function() {
                // hideMainPopupContainer();
                // $.cookies.set(cookie_name, 'true', { path: '/' });
            });
        }

        // New Slider Logic - only proceeds if the main popup_wrapper is visible
        if (popup_wrapper.length && popup_wrapper.css('display') === 'block') {
            var slider_container = $('#homepage_popup_slider_container');
            if (slider_container.length) {
                var slides = slider_container.find('.hp-slide');
                var next_btn = slider_container.find('.hp-next');
                var prev_btn = slider_container.find('.hp-prev');
                var dots = slider_container.find('.hp-dot');
                var current_slide_index = 0;
                var num_slides = slides.length;

                if (num_slides > 0) { // Ensure there's at least one slide to manage
                    function showSlide(index) {
                        slides.hide().removeClass('active');
                        // .get(index) to get DOM element then re-wrap if not already jQuery object
                        $(slides[index]).show().addClass('active');

                        if (dots.length) { // Check if dots exist
                            dots.removeClass('active');
                            $(dots[index]).addClass('active');
                        }

                        current_slide_index = index;
                    }

                    if (num_slides > 1) {
                        next_btn.on('click', function() {
                            var new_index = (current_slide_index + 1) % num_slides;
                            showSlide(new_index);
                        });

                        prev_btn.on('click', function() {
                            var new_index = (current_slide_index - 1 + num_slides) % num_slides;
                            showSlide(new_index);
                        });

                        dots.on('click', function() {
                            var new_index = $(this).data('slide-index');
                            if (typeof new_index !== 'undefined') {
                                showSlide(parseInt(new_index, 10));
                            }
                        });

                        // Initial state for dots if multiple slides (slide 0 already visible from TPL)
                        $(dots[0]).addClass('active');

                    } else { // Only one slide
                        // Hide nav buttons and dots if they were somehow rendered
                        if (next_btn.parent().hasClass('hp-slider-nav')) { // Check parent to hide whole nav div
                           next_btn.parent().hide();
                        }
                        slider_container.find('.hp-slider-dots').hide();
                    }

                    // Initial state for the first slide (already visible from TPL)
                    $(slides[0]).addClass('active');

                }
            }
        }
    });
}(Tygh, Tygh.$));
