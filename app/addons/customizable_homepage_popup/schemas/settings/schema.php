<?php

if (!defined('BOOTSTRAP')) { die('Access denied'); }

$schema = array(
    'tabs' => array(
        'general' => array(
            'title' => __('customizable_homepage_popup.settings.tab_general'),
            'position' => 10,
            'items' => array(
                'popup_width' => array(
                    'type' => 'input',
                    'default_value' => '600',
                    'name' => __('customizable_homepage_popup.settings.popup_width'),
                    'tooltip' => __('customizable_homepage_popup.settings.tooltip.popup_width'),
                    'position' => 10,
                ),
                'popup_height' => array(
                    'type' => 'input',
                    'default_value' => '400',
                    'name' => __('customizable_homepage_popup.settings.popup_height'),
                    'tooltip' => __('customizable_homepage_popup.settings.tooltip.popup_height'),
                    'position' => 20,
                ),
                'enable_background_dimming' => array(
                    'type' => 'checkbox',
                    'default_value' => 'Y',
                    'name' => __('customizable_homepage_popup.settings.enable_background_dimming'),
                    'tooltip' => __('customizable_homepage_popup.settings.tooltip.enable_background_dimming'),
                    'position' => 30,
                ),
                'delay' => array(
                    'type' => 'input',
                    'default_value' => '0',
                    'name' => __('customizable_homepage_popup.settings.delay'),
                    'tooltip' => __('customizable_homepage_popup.settings.tooltip.delay'),
                    'suffix' => __('customizable_homepage_popup.settings.seconds'),
                    'position' => 40,
                ),
                'animation' => array(
                    'type' => 'selectbox',
                    'default_value' => 'none',
                    'name' => __('customizable_homepage_popup.settings.animation'),
                    'tooltip' => __('customizable_homepage_popup.settings.tooltip.animation'),
                    'variants' => array(
                        'none' => __('customizable_homepage_popup.settings.animation.none'),
                        'fade' => __('customizable_homepage_popup.settings.animation.fade'),
                        'slide_from_top' => __('customizable_homepage_popup.settings.animation.slide_from_top'),
                        'slide_from_bottom' => __('customizable_homepage_popup.settings.animation.slide_from_bottom'),
                    ),
                    'position' => 50,
                ),
                'frequency' => array(
                    'type' => 'selectbox',
                    'default_value' => 'once_per_session',
                    'name' => __('customizable_homepage_popup.settings.frequency'),
                    'tooltip' => __('customizable_homepage_popup.settings.tooltip.frequency'),
                    'variants' => array(
                        'once_per_session' => __('customizable_homepage_popup.settings.frequency.once_per_session'),
                        'every_visit' => __('customizable_homepage_popup.settings.frequency.every_visit'),
                        'once_daily' => __('customizable_homepage_popup.settings.frequency.once_daily'),
                    ),
                    'position' => 60,
                ),
            ),
        ),
        // 'banner_management' tab REMOVED
    )
);
return $schema;
?>
