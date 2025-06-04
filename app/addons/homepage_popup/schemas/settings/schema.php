<?php

if (!defined('BOOTSTRAP')) { die('Access denied'); }

$schema = array(
    'tabs' => array(
        'general' => array(
            'title' => __('homepage_popup.settings.tab_general'),
            'position' => 10,
            'items' => array(
                'popup_width' => array(
                    'type' => 'input',
                    'default_value' => '600',
                    'name' => __('homepage_popup.settings.popup_width'),
                    'tooltip' => __('homepage_popup.settings.tooltip.popup_width'),
                    'suffix' => 'px',
                    'position' => 10,
                ),
                'popup_height' => array(
                    'type' => 'input',
                    'default_value' => '400',
                    'name' => __('homepage_popup.settings.popup_height'),
                    'tooltip' => __('homepage_popup.settings.tooltip.popup_height'),
                    'suffix' => 'px',
                    'position' => 20,
                ),
                'enable_background_dimming' => array(
                    'type' => 'checkbox',
                    'default_value' => 'Y',
                    'name' => __('homepage_popup.settings.enable_background_dimming'),
                    'tooltip' => __('homepage_popup.settings.tooltip.enable_background_dimming'),
                    'position' => 30,
                ),
            ),
        ),
        // 'banner_management' tab REMOVED
    )
);
return $schema;
?>
