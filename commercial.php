<?php
/**
 * Legacy entry point — forwards to unified /listings.php
 * Kept so existing links / bookmarks / nav still work.
 */
$_GET['category'] = 'commercial';
require __DIR__ . '/listings.php';
