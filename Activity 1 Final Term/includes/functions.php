<?php
declare(strict_types=1);

/** Logo image (assets/images/logo.png) as a round badge; falls back to an icon if the file is missing. */
function brand_logo(int $px = 48, string $fallbackIcon = 'clipboard2-check'): string
{
    if (is_file(APP_ROOT . '/assets/images/logo.png')) {
        return '<img class="brand-logo" src="' . e(base_url('assets/images/logo.png')) . '" width="' . $px . '" height="' . $px
            . '" alt="' . e(school_name()) . ' logo">';
    }
    return '<span class="brand-mark" style="width:' . $px . 'px;height:' . $px . 'px;font-size:' . round($px * .45) . 'px"><i class="bi bi-' . e($fallbackIcon) . '"></i></span>';
}

function school_name(): string
{
    return Database::setting('school_name', SCHOOL_NAME);
}

/** Acronym of the school name, e.g. "Bulacan Polytechnic College" -> "BPC". */
function school_short(): string
{
    $out = '';
    foreach (preg_split('/\s+/', school_name()) ?: [] as $w) {
        if ($w !== '' && preg_match('/^\p{Lu}/u', $w)) { $out .= function_exists('mb_substr') ? mb_substr($w, 0, 1) : substr($w, 0, 1); }
    }
    return strlen($out) >= 2 ? $out : school_name();
}

function school_footer(string $class = 'mt-5'): string
{
    return '<footer class="text-center text-muted small ' . e($class) . ' pt-3 border-top no-print">&copy; ' . date('Y') . ' '
        . e(school_name()) . ' &middot; ' . e(SCHOOL_LOCATION) . '</footer>';
}

function app_name(): string
{
    return Database::setting('org_name', APP_NAME);
}
