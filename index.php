<?php
require_once __DIR__ . '/local_conf.php';
test_logwrite_debug();
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

define('AREA', 'C');

try {
    require(dirname(__FILE__) . '/init.php');
    fn_dispatch();
} catch (Exception $e) {
    \Tygh\Tools\ErrorHandler::handleException($e);
} catch (Throwable $e) {
    \Tygh\Tools\ErrorHandler::handleException($e);
}
