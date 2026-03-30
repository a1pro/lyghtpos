// routes/api.php

<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Your existing route
Route::middleware('auth:api')->get('/user', fn(Request $request) => $request->user());

// Check if files exist before requiring
$posPath = base_path('routes/api/V1/pos.php');
$paxPath = base_path('routes/api/V1/pax.php');

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