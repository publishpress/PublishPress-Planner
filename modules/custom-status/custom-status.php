<?php

$includeFileRelativePath = '/publishpress/publishpress-functions/publishpress-functions.php';
if (file_exists(PUBLISHPRESS_FREE_PLUGIN_PATH . '/vendor' . $includeFileRelativePath)) {
    require_once PUBLISHPRESS_FREE_PLUGIN_PATH . '/vendor' . $includeFileRelativePath;
}

$includeFileRelativePath = '/publishpress/custom-status/custom-status.php';
if (file_exists(PUBLISHPRESS_FREE_PLUGIN_PATH . '/vendor' . $includeFileRelativePath)) {
    require_once PUBLISHPRESS_FREE_PLUGIN_PATH . '/vendor' . $includeFileRelativePath;
}
