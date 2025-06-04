<?php

if (!defined('BOOTSTRAP')) { die('Access denied'); }

fn_register_hooks(
    'index_post' // This hook is executed after the main index controller logic
);
