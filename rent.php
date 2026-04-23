<?php
/**
 * Legacy entry point — forwards to unified /listings.php
 * Kept so existing links / bookmarks / nav still work.
 * Supports the old ?tab=residential|commercial shim from the previous UI.
 */
$_GET['purpose'] = 'rent';

if (!isset($_GET['category'])) {
    $tab = $_GET['tab'] ?? '';
    if ($tab === 'commercial') {
        $_GET['category'] = 'commercial';
    } elseif ($tab === 'residential') {
        $_GET['category'] = 'residential';
    }
    // else leave category absent → shows all rentals
}
unset($_GET['tab']);

require __DIR__ . '/listings.php';
