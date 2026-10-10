<?php
// Output encoding untuk mencegah XSS. Pakai e() setiap mencetak data ke HTML.
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
