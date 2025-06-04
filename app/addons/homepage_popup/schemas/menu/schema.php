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

// Main menu item for Banner Management under "Marketing"
$schema['central']['marketing']['items']['homepage_popup_banners_management'] = array(
    'attrs' => array(
        'class' => 'is-addon'
    ),
    'href' => 'homepage_popup_banners.manage',
    'position' => 150, // Example position, adjust as needed
    'title' => __('homepage_popup.marketing_menu_title'),
    'permissions' => 'manage_homepage_popup_banners' // Explicitly state required permission
);

// Direct link to Addon Settings under "Add-ons -> Manage Add-ons" gear icon
$schema['central']['addons']['items']['manage_addons']['subitems']['homepage_popup_addon_settings'] = array(
    'attrs' => array(
        'class' => 'is-addon'
    ),
    'href' => 'addons.update?addon=homepage_popup',
    'position' => 100, // Position among other addon settings links
    'title' => __('homepage_popup.addon_name') // Uses the addon's name as the link title
);

return $schema;
?>
