(function(_, $) {
    $(document).ready(function() {
        var popup_wrapper = $('#customizable_homepage_popup_wrapper');
        if (!popup_wrapper.length) {
            return; // Popup HTML not on page
        }

        var popup_overlay = $('#customizable_homepage_popup_overlay');

        // Read data attributes
        var popup_width = popup_wrapper.data('ca-popup-width');
        var popup_height = popup_wrapper.data('ca-popup-height');
        var delay_ms = parseInt(popup_wrapper.data('ca-popup-delay'), 10) * 1000;
        if (isNaN(delay_ms)) {
            delay_ms = 0;
        }
        var animation_type = popup_wrapper.data('ca-popup-animation');
        var frequency = popup_wrapper.data('ca-popup-frequency');
        var dimming_enabled = popup_wrapper.data('ca-dimming-enabled') === 'Y';

        // Define cookie names
        var close_cookie_name = 'customizable_homepage_popup_closed'; // For current session/visit
        var daily_cookie_name = 'customizable_homepage_popup_closed_daily';

        // Apply dimensions
        if (popup_width) {
            popup_wrapper.css('width', popup_width);
        }
        if (popup_height) {
            popup_wrapper.css('height', popup_height);
            // Consider adding overflow-y: auto if content might exceed height
            // popup_wrapper.css('overflow-y', 'auto');
        }

        function initializeSlider() {
            var slider_container = $('#customizable_homepage_popup_slider_container');
            if (slider_container.length) {
                var slides = slider_container.find('.hp-slide');
                var next_btn = slider_container.find('.hp-next');
                var prev_btn = slider_container.find('.hp-prev');
                var dots = slider_container.find('.hp-dot');
                var current_slide_index = 0;
                var num_slides = slides.length;

                if (num_slides > 0) {
                    function showSlide(index) {
                        slides.hide().removeClass('active');
                        $(slides[index]).show().addClass('active');

                        if (dots.length) {
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

                        if (dots.length) { // Ensure dots exist before trying to activate one
                           $(dots[0]).addClass('active');
                        }

                    } else { // Only one slide
                        if (next_btn.parent().hasClass('hp-slider-nav')) {
                           next_btn.parent().hide();
                        }
                        slider_container.find('.hp-slider-dots').hide();
                    }
                    // Ensure first slide is active, it's already display:block from TPL if it's the first
                    $(slides[0]).addClass('active');
                }
            }
        }

        function showPopup() {
            if (dimming_enabled) {
                popup_overlay.fadeIn(); // Use fadeIn for a smoother effect
            }

            if (animation_type === 'fade') {
                popup_wrapper.fadeIn();
            } else if (animation_type === 'slide_from_top') {
                // Ensure it's properly positioned by transform: translate(-50%, -50%)
                // Animate top from an off-screen position to the center
                popup_wrapper.css({top: '-100%', display: 'block', opacity: 0}).animate({top: '50%', opacity: 1}, 500);
            } else if (animation_type === 'slide_from_bottom') {
                 popup_wrapper.css({top: '200%', display: 'block', opacity: 0}).animate({top: '50%', opacity: 1}, 500);
            } else { // 'none' or undefined
                popup_wrapper.show();
            }
            initializeSlider(); // Initialize slider after popup is made visible
        }

        function closePopup() {
            if (animation_type === 'fade') {
                popup_wrapper.fadeOut();
                if (dimming_enabled) {
                    popup_overlay.fadeOut();
                }
            } else {
                popup_wrapper.hide();
                if (dimming_enabled) {
                    popup_overlay.hide();
                }
            }

            if (frequency === 'once_daily') {
                // Set cookie for 1 day
                $.cookies.set(daily_cookie_name, 'true', { expires: 1, path: '/' });
            }
            // This cookie is for 'dont show again THIS visit/until browser close' for all frequencies if user closes it.
            // For 'every_visit', it prevents re-opening on the same page load if some other dynamic trigger existed.
            // For 'once_per_session' and 'once_daily', it ensures it doesn't pop up again if server logic (e.g. cache) somehow re-triggers.
            $.cookies.set(close_cookie_name, 'true', { path: '/' });
        }

        $('#close_customizable_homepage_popup').on('click', closePopup);
        if (dimming_enabled) {
             popup_overlay.on('click', closePopup);
        }

        // Main decision logic to show popup
        var is_server_requesting_show = popup_wrapper.data('show-on-load') === 'Y';
        var is_closed_by_session_cookie = $.cookies.get(close_cookie_name) === 'true';
        var daily_cookie_val = $.cookies.get(daily_cookie_name);

        if (frequency === 'every_visit') {
            // For 'every_visit', we only care if the user closed it *during this current browser session/visit*.
            // The server will always try to show it ($show_customizable_homepage_popup = true).
            // The 'close_cookie_name' is session-based.
            if (!is_closed_by_session_cookie) {
                setTimeout(showPopup, delay_ms);
            }
        } else if (frequency === 'once_daily') {
            // For 'once_daily', show if server wants to (session not marked),
            // AND daily cookie is not set, AND session cookie not set (user hasn't closed it this session).
            if (is_server_requesting_show && !daily_cookie_val && !is_closed_by_session_cookie) {
                setTimeout(showPopup, delay_ms);
            }
        } else { // 'once_per_session' (default)
            // Show if server wants to (session not marked), AND session cookie not set.
            if (is_server_requesting_show && !is_closed_by_session_cookie) {
                setTimeout(showPopup, delay_ms);
            }
        }
    });
}(Tygh, Tygh.$));
