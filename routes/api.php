<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

 

// Check if files exist before requiring
$posPath = base_path('routes/api/V1/pos.php');
$paxPath = base_path('routes/api/V1/pax.php');
$wp = base_path('routes/api/V1/wp.php');
$cloverPath = base_path('routes/api/V1/clover.php');
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

if (file_exists($cloverPath)) {
    require $cloverPath;
} else {
    \Log::error('Clover routes file not found: ' . $wp);
}