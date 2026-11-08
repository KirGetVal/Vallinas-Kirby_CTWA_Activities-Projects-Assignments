<?php
declare(strict_types=1);
// Single entry include for every page and action.
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/storage.php';
require_once __DIR__ . '/Storage.php';
require_once __DIR__ . '/Helpers.php';
require_once __DIR__ . '/Validator.php';
require_once __DIR__ . '/Auth.php';

Auth::start();
Storage::init();
require_once __DIR__ . '/Notification.php';
require_once __DIR__ . '/Complaint.php';
require_once __DIR__ . '/Report.php';
require_once __DIR__ . '/../includes/functions.php';
