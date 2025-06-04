<?php

if (!defined('BOOTSTRAP')) { die('Access denied'); }

use Tygh\Registry;
use Tygh\Session;
use Tygh\Tygh;

/**
 * Hook for index_post: Decides whether to show the homepage popup.
 *
 * @param string $controller The current controller.
 * @param string $mode The current mode.
 * @param string $action The current action.
 * @param array $dispatch_extra Any extra dispatch parameters.
 * @param array $params The request parameters.
 * @param string $lang_code The language code for the current context (CART_LANGUAGE on storefront).
 */
function fn_homepage_popup_index_post($controller, $mode, $action, $dispatch_extra, $params, $lang_code)
{
    if (AREA == 'C' && $controller == 'index' && $mode == 'index') {
        $popup_closed_cookie = !empty($_COOKIE['homepage_popup_closed']) && $_COOKIE['homepage_popup_closed'] === 'true';

        if (empty(Session::get('homepage_popup_shown')) && !$popup_closed_cookie) {
            // Use the $lang_code from the hook, which is CART_LANGUAGE on storefront
            $active_banners = fn_homepage_popup_get_active_banners($lang_code);
            if (!empty($active_banners)) {
                Registry::get('view')->assign('homepage_popup_banners', $active_banners);
                Registry::get('view')->assign('show_homepage_popup', true);
                Session::set('homepage_popup_shown', true);
            } else {
                Registry::get('view')->assign('show_homepage_popup', false);
            }
        } else {
            Registry::get('view')->assign('show_homepage_popup', false);
        }
    }
}

/**
 * Gets a list of homepage popup banners for the backend.
 *
 * @param array $params Search parameters.
 * @param int $items_per_page Number of items per page for pagination.
 * @param string $lang_code Language code (typically DESPATCH_LANG_CODE for backend).
 * @return array An array containing the list of banners and search parameters.
 */
function fn_homepage_popup_get_banners($params = array(), $items_per_page = 0, $lang_code = DESPATCH_LANG_CODE)
{
    $default_params = array(
        'page' => 1,
        'items_per_page' => $items_per_page,
        'sort_by' => 'position',
        'sort_order' => 'asc',
    );

    $params = array_merge($default_params, $params);

    $sortings = array(
        'title' => '?:homepage_popup_banners.title',
        'position' => '?:homepage_popup_banners.position',
        'status' => '?:homepage_popup_banners.status'
    );

    $sortings = array(
        'title' => '?:homepage_popup_banners.title',
        'position' => '?:homepage_popup_banners.position',
        'status' => '?:homepage_popup_banners.status'
    );

    $condition_parts = array(); // Array to hold parts of the WHERE clause
    $limit = '';

    // Language condition
    $condition_parts[] = db_quote("lang_code = ?s", $lang_code);

    if (!empty($params['item_ids'])) {
        $item_ids_array = explode(',', $params['item_ids']);
        $condition_parts[] = db_quote("banner_id IN (?n)", $item_ids_array);
    }

    // Add other conditions to $condition_parts as needed, for example:
    // if (!empty($params['status'])) {
    //     $condition_parts[] = db_quote("status = ?s", $params['status']);
    // }

    $condition = '';
    if (!empty($condition_parts)) {
        $condition = " WHERE " . implode(" AND ", $condition_parts);
    }
    // If $condition_parts is empty, $condition remains empty, resulting in "SELECT * FROM table ORDER BY ... LIMIT ..."
    // If you always need a WHERE clause (e.g. for security, or if lang_code is mandatory), ensure $condition_parts is never empty
    // or add a "WHERE 1" if $condition is empty. Given lang_code is always added, it won't be empty.

    $sorting = db_sort($params, $sortings, 'position', 'asc'); // $sorting is "ORDER BY field dir"

    if (!empty($params['items_per_page'])) {
        $count_query = "SELECT COUNT(*) FROM ?:homepage_popup_banners $condition";
        $params['total_items'] = db_get_field($count_query);
        $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
    }

    $query = "SELECT * FROM ?:homepage_popup_banners $condition $sorting $limit";

    $banners = db_get_array($query);

    // Get images
    if (!empty($banners)) {
        foreach ($banners as &$banner) {
        $banner['main_pair'] = fn_get_image_pairs($banner['banner_id'], 'homepage_popup_banner', 'M', true, true, $lang_code);
    }

    return array($banners, $params);
}

/**
 * Gets data for a specific homepage popup banner for the backend.
 *
 * @param int $banner_id The ID of the banner.
 * @param string $lang_code Language code (typically DESPATCH_LANG_CODE for backend).
 * @return array The banner data.
 */
function fn_homepage_popup_get_banner_data($banner_id, $lang_code = DESPATCH_LANG_CODE)
{
    $banner = array();
    if (!empty($banner_id)) {
        $banner = db_get_row("SELECT * FROM ?:homepage_popup_banners WHERE banner_id = ?i AND lang_code = ?s", $banner_id, $lang_code);

        // Fallback: if no banner for the specified lang_code, try to get data from any other language for this banner_id
        // This might be useful if you want to create a new language version based on an existing one.
        if (empty($banner)) {
            $any_lang_banner = db_get_row("SELECT * FROM ?:homepage_popup_banners WHERE banner_id = ?i", $banner_id);
            if (!empty($any_lang_banner)) {
                // Return the existing data but clear content fields that need translation and set the target lang_code
                $banner = $any_lang_banner;
                $banner['title'] = ''; // Clear title for new translation
                $banner['content'] = ''; // Clear content for new translation
                $banner['lang_code'] = $lang_code; // Set to the target language
                // Image might be shared or re-uploaded, main_pair will be fetched/created for target lang_code
            }
        }

        if (!empty($banner)) {
             // Fetch image pair for the target $lang_code
             $banner['main_pair'] = fn_get_image_pairs($banner_id, 'homepage_popup_banner', 'M', true, true, $lang_code);
        }
    }
    return $banner;
}

/**
 * Updates or creates a homepage popup banner.
 *
 * @param array $data The banner data from the form.
 * @param int $banner_id The ID of the banner to update, or 0 to create.
 * @param string $lang_code Language code for the content (typically DESPATCH_LANG_CODE for backend).
 * @return int|false The ID of the updated/created banner, or false on failure.
 */
function fn_homepage_popup_update_banner($data, $banner_id, $lang_code = DESPATCH_LANG_CODE)
{
    if (empty($data)) {
        return false;
    }

    // Ensure lang_code is set in the data array for insertion/update
    $data['lang_code'] = $lang_code;

    if (empty($banner_id)) {
        // Create new banner - banner_id will be auto-incremented
        $banner_id = db_query("INSERT INTO ?:homepage_popup_banners ?e", $data);
    } else {
        // Update existing banner for the given banner_id and lang_code
        // Check if a record for this banner_id and lang_code already exists
        $exists = db_get_field("SELECT COUNT(*) FROM ?:homepage_popup_banners WHERE banner_id = ?i AND lang_code = ?s", $banner_id, $lang_code);
        if ($exists) {
            db_query("UPDATE ?:homepage_popup_banners SET ?u WHERE banner_id = ?i AND lang_code = ?s", $data, $banner_id, $lang_code);
        } else {
            // Insert a new language version for an existing banner_id
            $data['banner_id'] = $banner_id; // Ensure banner_id is part of data for insert
            db_query("INSERT INTO ?:homepage_popup_banners ?e", $data);
        }
    }

    if ($banner_id) {
        // Attach image pairs (handles new uploads and existing image linking)
        // 'new_banner_image' is the typical name for the main image uploader in the form
        fn_attach_image_pairs('new_banner_image', 'homepage_popup_banner', $banner_id, $lang_code);
    }
    return $banner_id;
}

/**
 * Deletes a homepage popup banner and its associated images.
 *
 * @param int $banner_id The ID of the banner to delete.
 * @return bool True on success, false otherwise.
 */
function fn_homepage_popup_delete_banner($banner_id)
{
    if (!empty($banner_id)) {
        // Delete all language versions of the banner
        $res = db_query("DELETE FROM ?:homepage_popup_banners WHERE banner_id = ?i", $banner_id);
        // Deletes all image pairs associated with this banner_id for the 'homepage_popup_banner' object type
        fn_delete_image_pairs($banner_id, 'homepage_popup_banner');
        return (bool)$res;
    }
    return false;
}

/**
 * Gets active homepage popup banners for the storefront.
 *
 * @param string $lang_code Language code (typically CART_LANGUAGE for storefront).
 * @return array An array of active banners.
 */
function fn_homepage_popup_get_active_banners($lang_code = CART_LANGUAGE)
{
    $banners_data = db_get_array(
        "SELECT * FROM ?:homepage_popup_banners WHERE status = 'A' AND lang_code = ?s ORDER BY position ASC", $lang_code
    );
    foreach ($banners_data as &$banner) {
        $banner['main_pair'] = fn_get_image_pairs($banner['banner_id'], 'homepage_popup_banner', 'M', true, true, $lang_code);
    }
    return $banners_data;
}
?>
