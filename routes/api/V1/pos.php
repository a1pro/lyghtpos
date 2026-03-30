// routes/api/V1/pos.php

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\POS\Auth\LoginController;

// Make sure the route is properly defined
Route::post('check', [LoginController::class, 'login'])->name('pos.login');

// You can also try without the 'check' name to test
// Route::post('test', function() {
//     return response()->json(['message' => 'Test route works!']);
// });