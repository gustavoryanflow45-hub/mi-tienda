<?php

if (!function_exists('uploaded_asset')) {
    function uploaded_asset($path)
    {
        if (!$path) return asset('assets/img/placeholder.jpg');
        return asset('storage/' . $path);
    }
}