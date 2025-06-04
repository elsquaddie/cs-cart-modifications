<?php

use Tygh\Registry;
use Tygh\Tygh;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

$dispatch_permission_check = true;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $return_url = 'homepage_popup_banners.manage';

    // SAVE BANNER
    if ($mode == 'update') {
        $banner_data = $_REQUEST['banner_data'];
        $banner_id = isset($_REQUEST['banner_id']) ? (int)$_REQUEST['banner_id'] : 0;

        if (empty($banner_data['lang_code'])) {
            $banner_data['lang_code'] = DESPATCH_LANG_CODE;
        }

        $updated_banner_id = fn_homepage_popup_update_banner($banner_data, $banner_id, DESPATCH_LANG_CODE);

        if ($updated_banner_id) {
            fn_set_notification('N', __('notice'), __('homepage_popup.banner_saved_successfully'));
            $return_url = 'homepage_popup_banners.update?banner_id=' . $updated_banner_id;
        } else {
            fn_set_notification('E', __('error'), __('homepage_popup.banner_save_error'));
            if ($banner_id) {
                $return_url = 'homepage_popup_banners.update?banner_id=' . $banner_id;
            } else {
                $return_url = 'homepage_popup_banners.manage';
            }
        }
    }

    // DELETE BANNERS (Multiple)
    if ($mode == 'm_delete') {
        if (!empty($_REQUEST['banner_ids'])) {
            $deleted_count = 0;
            foreach ($_REQUEST['banner_ids'] as $b_id) {
                if (fn_homepage_popup_delete_banner((int)$b_id)) {
                    $deleted_count++;
                }
            }
            if ($deleted_count > 0) {
                fn_set_notification('N', __('notice'), __('homepage_popup.banners_deleted_successfully', array($deleted_count)));
            }
        }
    }
    return array(CONTROLLER_STATUS_OK, $return_url);
}

// MANAGE BANNERS (LIST VIEW)
if ($mode == 'manage') {
    list($banners, $search) = fn_homepage_popup_get_banners($_REQUEST, Registry::get('settings.Appearance.admin_elements_per_page'), DESPATCH_LANG_CODE);
    Registry::get('view')->assign('banners', $banners);
    Registry::get('view')->assign('search', $search);

// ADD/EDIT BANNER VIEW
} elseif ($mode == 'update') {
    $banner_id = isset($_REQUEST['banner_id']) ? (int)$_REQUEST['banner_id'] : 0;
    $banner_data = fn_homepage_popup_get_banner_data($banner_id, DESPATCH_LANG_CODE);

    if (empty($banner_data) && $banner_id > 0) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }
    Registry::get('view')->assign('banner_data', $banner_data);
    Registry::get('view')->assign('languages', fn_get_translation_languages());

// DELETE BANNER (SINGLE from list)
} elseif ($mode == 'delete') {
    $banner_id = isset($_REQUEST['banner_id']) ? (int)$_REQUEST['banner_id'] : 0;
    if ($banner_id) {
        if (fn_homepage_popup_delete_banner($banner_id)) {
            fn_set_notification('N', __('notice'), __('homepage_popup.banner_deleted_successfully'));
        }
    }
    return array(CONTROLLER_STATUS_OK, 'homepage_popup_banners.manage');
}
?>
