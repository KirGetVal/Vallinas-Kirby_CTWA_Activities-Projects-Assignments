<?php
declare(strict_types=1);

const APP_NAME = 'Complaint Management System';
const APP_ROOT = __DIR__ . '/..';
const UPLOAD_PATH = APP_ROOT . '/uploads/complaints';
const SESSION_NAME = 'cms_session';
const SESSION_LIFETIME = 3600;       // seconds of inactivity
const MAX_LOGIN_ATTEMPTS = 5;
const LOGIN_LOCK_SECONDS = 300;
const PAGE_SIZE = 10;
const SEED_DEMO_DATA = true;         // development only: set false in production

date_default_timezone_set('Asia/Manila');

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
