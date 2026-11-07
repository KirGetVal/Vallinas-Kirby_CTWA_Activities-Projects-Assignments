<?php
// Router for PHP's built-in server ONLY. It ignores .htaccess, so this applies the same deny rules.
// Start with:  php -S localhost:8000 router.php        (Apache/XAMPP does not use this file.)
$path = rawurldecode((string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
$blocked = str_contains($path, '..') || str_contains($path, "\0")
    || preg_match('#(^|/)(uploads|config|core|includes)(/|$)#i', $path)
    || preg_match('#(^|/)\.#', $path)
    || preg_match('#\.(json|log|md|htaccess)$#i', $path) && !preg_match('#(^|/)README\.md$#i', $path);
if ($blocked || preg_match('#(^|/)router\.php$#i', $path)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit('403 Forbidden');
}
return false;   // let the built-in server handle everything else normally
