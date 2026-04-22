<?php

function resolveProductImagePath(?string $image, string $relativePrefix = '../'): string
{
    $image = trim((string) $image);

    if ($image === '') {
        return $relativePrefix . 'uploads/default.png';
    }

    if (preg_match('/^(https?:)?\/\//i', $image) === 1 || strpos($image, 'data:image/') === 0) {
        return $image;
    }

    if (file_exists($relativePrefix . 'uploads/' . $image)) {
        return $relativePrefix . 'uploads/' . $image;
    }

    if (file_exists($relativePrefix . 'admin/images/' . $image)) {
        return $relativePrefix . 'admin/images/' . $image;
    }

    return $relativePrefix . 'uploads/default.png';
}
