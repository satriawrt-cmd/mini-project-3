<?php
// Syntactic validation: tipe, format, panjang
function clean_str($value, int $max = 255): string
{
    return mb_substr(trim((string) $value), 0, $max);
}

function valid_int($value, int $min = 0)
{
    $v = filter_var($value, FILTER_VALIDATE_INT);
    return ($v === false || $v < $min) ? false : $v;
}

function valid_decimal($value)
{
    $v = filter_var($value, FILTER_VALIDATE_FLOAT);
    return ($v === false || $v < 0) ? false : $v;
}

function rupiah($n): string
{
    return 'Rp ' . number_format((float) $n, 0, ',', '.');
}
