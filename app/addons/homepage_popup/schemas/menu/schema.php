<?php
/***************************************************************************
 *                                                                          *
 *   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
 *                                                                          *
 * This  is  commercial  software,  only  users  who have purchased a valid *
 * license  and  accept  to the terms of the  License Agreement can install *
 * and use this program.                                                    *
 *                                                                          *
 ****************************************************************************
 * PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
 * "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/

defined('BOOTSTRAP') or die('Access denied');

$schema['central']['marketing']['items']['homepage_popup_banners'] = array(
    'attrs' => array(
        'class' => 'is-addon' // Marks it as an addon-contributed menu item
    ),
    'href' => 'homepage_popup_banners.manage',
    'position' => 150, // Adjust position relative to other Marketing items (e.g., Banners is often around 100)
    'title' => __('homepage_popup.marketing_menu_title') // New language variable
);

// Optional: Add a direct link to addon settings under Add-ons menu for convenience
// This is standard for most addons.
$schema['central']['addons']['items']['manage_addons']['subitems']['homepage_popup_settings'] = array(
    'attrs' => array(
        'class' => 'is-addon'
    ),
    'href' => 'addons.update?addon=homepage_popup',
    'position' => 1000, // Adjust as needed
    'title' => __('homepage_popup.addon_name') // Use existing addon name lang var
);


return $schema;
?>
