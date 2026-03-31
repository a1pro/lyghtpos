<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

 

// Check if files exist before requiring
$posPath = base_path('routes/api/V1/pos.php');
$paxPath = base_path('routes/api/V1/pax.php');
$wp = base_path('routes/api/V1/wp.php');
if (file_exists($posPath)) {
    require $posPath;
} else {
    // Log or handle missing file
    \Log::error('POS routes file not found: ' . $posPath);
}

if (file_exists($paxPath)) {
    require $paxPath;
} else {
    \Log::error('PAX routes file not found: ' . $paxPath);
}


if (file_exists($wp)) {
    require $wp;
} else {
    \Log::error('PAX routes file not found: ' . $wp);
}