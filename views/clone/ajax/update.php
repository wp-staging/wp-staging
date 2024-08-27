<?php

/**
 * @see \WPStaging\Backend\Administrator::ajaxUpdateProcess A place where this view is being called.
 * @see \WPStaging\Backend\Administrator::ajaxResetProcess A place where this view is being called.
 * @var \WPStaging\Backend\Modules\Jobs\Cloning $cloning
 */

$modalType = 'clone';
require_once(WPSTG_VIEWS_DIR . 'backup/modal/download.php');
require_once(WPSTG_VIEWS_DIR . 'backup/modal/progress.php');
