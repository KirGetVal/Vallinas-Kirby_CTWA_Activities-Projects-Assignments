<?php
declare(strict_types=1);

const ROLE_USER  = 'user';
const ROLE_STAFF = 'staff';
const ROLE_ADMIN = 'admin';

const STATUS_SUBMITTED   = 'submitted';
const STATUS_REVIEW      = 'under_review';
const STATUS_ASSIGNED    = 'assigned';
const STATUS_PROGRESS    = 'in_progress';
const STATUS_AWAITING    = 'awaiting_response';
const STATUS_RESOLVED    = 'resolved';
const STATUS_CLOSED      = 'closed';
const STATUS_REJECTED    = 'rejected';
const STATUS_REOPENED    = 'reopened';

const STATUS_LABELS = [
    STATUS_SUBMITTED => 'Submitted',
    STATUS_REVIEW    => 'Under Review',
    STATUS_ASSIGNED  => 'Assigned',
    STATUS_PROGRESS  => 'In Progress',
    STATUS_AWAITING  => 'Awaiting Response',
    STATUS_RESOLVED  => 'Resolved',
    STATUS_CLOSED    => 'Closed',
    STATUS_REJECTED  => 'Rejected',
    STATUS_REOPENED  => 'Reopened',
];

/**
 * Allowed transitions: from => [to => [roles allowed]]
 * Enforced server-side in Complaint::changeStatus().
 */
const STATUS_TRANSITIONS = [
    STATUS_SUBMITTED => [STATUS_REVIEW => [ROLE_ADMIN], STATUS_REJECTED => [ROLE_ADMIN]],
    STATUS_REVIEW    => [STATUS_ASSIGNED => [ROLE_ADMIN], STATUS_REJECTED => [ROLE_ADMIN]],
    STATUS_ASSIGNED  => [STATUS_PROGRESS => [ROLE_STAFF, ROLE_ADMIN]],
    STATUS_PROGRESS  => [STATUS_AWAITING => [ROLE_STAFF, ROLE_ADMIN], STATUS_RESOLVED => [ROLE_STAFF, ROLE_ADMIN]],
    STATUS_AWAITING  => [STATUS_PROGRESS => [ROLE_USER, ROLE_STAFF, ROLE_ADMIN]],
    STATUS_RESOLVED  => [STATUS_CLOSED => [ROLE_ADMIN], STATUS_PROGRESS => [ROLE_ADMIN]],
    STATUS_CLOSED    => [STATUS_REOPENED => [ROLE_USER, ROLE_ADMIN]],
    STATUS_REJECTED  => [STATUS_REOPENED => [ROLE_ADMIN]],
    STATUS_REOPENED  => [STATUS_ASSIGNED => [ROLE_ADMIN], STATUS_PROGRESS => [ROLE_STAFF, ROLE_ADMIN]],
];

const PRIORITIES = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent'];

const MAX_UPLOAD_BYTES = 5 * 1024 * 1024;
const ALLOWED_UPLOAD_EXT = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'txt'];
const ALLOWED_UPLOAD_MIME = [
    'image/jpeg', 'image/png', 'application/pdf', 'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain',
    'application/octet-stream', 'application/zip',
];

const DEFAULT_CATEGORIES = [
    'Infrastructure / Facilities', 'Customer Service', 'Staff Conduct',
    'Safety and Security', 'Maintenance', 'Technical Issues', 'Other Concerns',
];
