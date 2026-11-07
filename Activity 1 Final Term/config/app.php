<?php
declare(strict_types=1);

const APP_NAME = 'Complaint Management System';
const SCHOOL_NAME = 'Bulacan Polytechnic College';   // default; editable in Admin > Settings
const SCHOOL_LOCATION = 'Malolos, Bulacan';
const APP_ROOT = __DIR__ . '/..';
const SESSION_NAME = 'cms_session';
const SESSION_LIFETIME = 3600;       // seconds of inactivity
const MAX_LOGIN_ATTEMPTS = 5;
const LOGIN_LOCK_SECONDS = 300;
const APP_DEBUG = false;             // true only while developing: shows PHP errors in the browser
const SEED_DEMO_DATA = true;         // development only: set false in production

date_default_timezone_set('Asia/Manila');
ini_set('display_errors', APP_DEBUG ? '1' : '0');   // never leak paths/stack traces to visitors
ini_set('log_errors', '1');

/** Base URL path of the app (works under /complaint-management-system/ or any folder). */
function base_url(string $path = ''): string
{
    static $base = null;
    if ($base === null) {
        $docRoot = rtrim(str_replace('\\', '/', (string)realpath($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
        $appRoot = str_replace('\\', '/', (string)realpath(APP_ROOT));
        $base = ($docRoot !== '' && str_starts_with($appRoot, $docRoot)) ? substr($appRoot, strlen($docRoot)) : '';
    }
    return $base . '/' . ltrim($path, '/');
}
