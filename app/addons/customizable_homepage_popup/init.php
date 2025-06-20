<?php

if (!defined('BOOTSTRAP')) { die('Access denied'); }

fn_register_hooks(
    // Hook for index_post: Decides whether to show the homepage popup.
    // Function name needs to match the new addon id prefix
    'index_post => fn_customizable_homepage_popup_index_post'
);
