<?php

namespace App\Http\Controllers\Api\V1\WP\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Representation;
use App\Models\User;
class WpRepresentationController extends Controller
{
    /**
 * Get representation status for a specific user
 * 
 * @param int $userId
 * @return \Illuminate\Http\JsonResponse
 */
public function status($userId)
{
    try {
        // Validate if user exists
        $user = User::find($userId);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'status_code' => 404,
                'message' => 'User not found'
            ], 404);
        }
        
        // Get representation for the user
        $representation = Representation::where('user_id', $userId)->first();
        
        if (!$representation) {
            return response()->json([
                'success' => false,
                'status_code' => 404,
                'message' => 'Representation not found for this user'
            ], 404);
        }
        
        // Return only the status
        return response()->json([
            'success' => true,
            'status_code' => 200,
            'message' => 'Representation status retrieved successfully',
            'data' => [
                'status' => $representation->status
            ]
        ], 200);
        
    } catch (\Exception $e) {
        Log::error('Error fetching representation status', [
            'user_id' => $userId ?? null,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'status_code' => 500,
            'message' => 'An error occurred while fetching representation status: ' . $e->getMessage(),
        ], 500);
    }
}
}
