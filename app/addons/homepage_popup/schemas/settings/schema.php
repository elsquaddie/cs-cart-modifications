<?php

if (!defined('BOOTSTRAP')) { die('Access denied'); }

$schema = array(
    'tabs' => array(
        'general' => array(
            'title' => __('homepage_popup.settings.tab_general'),
            'position' => 10, // Added position
            'items' => array(
                'popup_width' => array(
                    'type' => 'input',
                    'default_value' => '600',
                    'name' => __('homepage_popup.settings.popup_width'),
                    'tooltip' => __('homepage_popup.settings.tooltip.popup_width'),
                    'suffix' => 'px',
                    'position' => 10, // Added position
                ),
                'popup_height' => array(
                    'type' => 'input',
                    'default_value' => '400',
                    'name' => __('homepage_popup.settings.popup_height'),
                    'tooltip' => __('homepage_popup.settings.tooltip.popup_height'),
                    'suffix' => 'px',
                    'position' => 20, // Added position
                ),
                'enable_background_dimming' => array(
                    'type' => 'checkbox',
                    'default_value' => 'Y',
                    'name' => __('homepage_popup.settings.enable_background_dimming'),
                    'tooltip' => __('homepage_popup.settings.tooltip.enable_background_dimming'),
                    'position' => 30, // Added position
                ),
            ),
        ),
        'banner_management' => array(
            'title' => __('homepage_popup.settings.tab_banner_management'),
            'position' => 20, // Added position
            'items' => array(
                'manage_banners_link' => array(
                    'type' => 'template',
                    'template' => 'addons/homepage_popup/components/manage_banners_link.tpl',
                    'tooltip' => __('homepage_popup.settings.tooltip.manage_banners_link'),
                    'position' => 10, // Added position
                ),
            ),
        ),
    )
);
return $schema;
?>
