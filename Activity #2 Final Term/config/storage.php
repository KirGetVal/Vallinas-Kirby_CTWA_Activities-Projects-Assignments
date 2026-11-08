<?php
declare(strict_types=1);

const STORAGE_PATH = __DIR__ . '/../storage';

/** Collection name => default (empty) content. settings is an object, others are lists. */
const STORAGE_FILES = [
    'users' => [], 'complaints' => [], 'categories' => [], 'assignments' => [],
    'status_history' => [], 'attachments' => [], 'messages' => [], 'notifications' => [],
    'feedback' => [], 'activity_logs' => [], 'notes' => [], 'login_attempts' => [],
    'settings' => ['overdue_days' => 7, 'org_name' => 'Complaint Management System'],
];
