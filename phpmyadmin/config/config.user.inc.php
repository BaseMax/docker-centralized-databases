<?php
declare(strict_types=1);

/**
 * phpMyAdmin – User Security Configuration
 *
 * This file is loaded after the main generated config.inc.php.
 * It overrides defaults to harden the phpMyAdmin installation.
 *
 * @see https://docs.phpmyadmin.net/en/latest/config.html
 */

// ── Server connection ─────────────────────────────────────────────────────────
// Disallow connecting to arbitrary servers from the login page.
// PMA_ARBITRARY=0 in docker-compose.yml already sets this, but we enforce it
// here as a defence-in-depth measure.
$cfg['AllowArbitraryServer'] = false;

// ── Session / cookie security ─────────────────────────────────────────────────
// Idle timeout in seconds before the login cookie is invalidated (default 1440 s / 24 min).
$cfg['LoginCookieValidity'] = 1440;

// Delete the login cookie when the browser is closed.
$cfg['LoginCookieDeleteAll'] = true;

// ── Feature toggles ───────────────────────────────────────────────────────────
// Hide PHP info page from authenticated users (prevents information disclosure).
$cfg['ShowPhpInfo'] = false;

// Show the change-password form in the UI.
$cfg['ShowChgPassword'] = true;

// Enable gzip compression for query results sent to the browser.
$cfg['CompressOnFly'] = true;

// ── Authentication logging ────────────────────────────────────────────────────
// Log failed login attempts to syslog (visible via `docker logs`).
$cfg['AuthLog']        = 'syslog';
$cfg['AuthLogSuccess'] = false;  // Only log failures, not every successful login

// ── IP-based access restriction (optional) ───────────────────────────────────
// Uncomment and adjust to restrict phpMyAdmin to specific source IPs.
// $cfg['Servers'][$i]['AllowDeny']['order'] = 'deny,allow';
// $cfg['Servers'][$i]['AllowDeny']['rules'] = [
//     'deny  all',
//     'allow 192.168.1.0/24',
// ];

// ── TLS / HTTPS ───────────────────────────────────────────────────────────────
// Set to true when phpMyAdmin is served behind a TLS-terminating reverse proxy
// (e.g. nginx with HTTPS). This enables the Secure cookie flag.
$cfg['ForceSSL'] = false;
