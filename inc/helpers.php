<?php

// Check for direct access
if (!defined('ABSPATH')) exit;

/**
 * Generate HMAC signature for API requests
 *
 * @param string $data The data to be signed
 * @return array The signature and timestamp
 */
function qqm_generate_signature($data) {
    $timestamp = time();
    $signature = hash_hmac('sha256', $data . $timestamp, QOOKIE_SECRET);
    return ['timestamp' => $timestamp, 'signature' => $signature];
}
