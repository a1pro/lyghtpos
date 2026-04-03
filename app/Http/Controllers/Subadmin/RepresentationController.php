<?php

namespace App\Http\Controllers\Subadmin;

use App\Http\Controllers\Controller;
use App\Models\Representation;
use App\Models\RepresentationReviewLog;
use App\Models\User;
use App\Models\Notification;
use App\Models\GovernanceLayer;
use App\Models\UserGovernanceLayer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;


class RepresentationController extends Controller
{
    /**
     * Display the representation for review
     */
    public function viewRepresentation($userId)
    {
        $representation = Representation::where('user_id', $userId)
            ->with('user', 'user.merchant', 'reviewLogs.user')
            ->first();

        $isReviewed = false;
        $governanceLayers = collect();
        $userGovernanceLayers = collect();
        $allGovernanceApproved = false;
        $pendingGovernanceCount = 0;
        $rejectedGovernanceCount = 0;
        $totalGovernanceCount = 0;

        if ($representation) {
            $isReviewed = $representation->reviewer_user_id && $representation->last_reviewed;
            
            // Get all active governance layers
            $governanceLayers = GovernanceLayer::where('status', 'active')->get();
            $totalGovernanceCount = $governanceLayers->count();
            
            // Get user's governance layer statuses
            if ($totalGovernanceCount > 0) {
                $userGovernanceLayers = UserGovernanceLayer::where('user_id', $userId)
                    ->with('governance')
                    ->get()
                    ->keyBy('governance_id');
                
                // Count pending and rejected governance layers
                $pendingGovernanceCount = $governanceLayers->filter(function($layer) use ($userGovernanceLayers) {
                    $userLayer = $userGovernanceLayers->get($layer->id);
                    return !$userLayer || $userLayer->status === 'pending';
                })->count();
                
                $rejectedGovernanceCount = $userGovernanceLayers->filter(function($layer) {
                    return $layer->status === 'rejected';
                })->count();
                
                // Check if all governance layers are approved
                $allGovernanceApproved = $userGovernanceLayers->count() === $totalGovernanceCount && 
                                        $userGovernanceLayers->every(function($layer) {
                                            return $layer->status === 'approved';
                                        });
            }
        }

        return view('subadmin.pages.merchants.representation-view', compact(
            'representation', 
            'isReviewed',
            'governanceLayers',
            'userGovernanceLayers',
            'allGovernanceApproved',
            'pendingGovernanceCount',
            'rejectedGovernanceCount',
            'totalGovernanceCount'
        ));
    }

    /**
     * Update governance layer status
     */
     
    // app/Http/Controllers/Subadmin/RepresentationController.php
    
    public function updateGovernanceLayer(Request $request, $userId, $governanceId)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'review_message' => 'required_if:status,rejected|nullable|string|max:500',
        ], [
            'review_message.required_if' => 'Review message is required for rejection.',
        ]);
    
        try {
            DB::beginTransaction();
    
            $user = User::findOrFail($userId);
            $governance = GovernanceLayer::findOrFail($governanceId);
    
            // Check if there's an existing record
            $existingRecord = UserGovernanceLayer::where('user_id', $userId)
                ->where('governance_id', $governanceId)
                ->first();
    
            $oldStatus = $existingRecord ? $existingRecord->status : 'pending';
            $oldMessage = $existingRecord ? $existingRecord->message : null;
    
            // Prepare data for update/create
            $data = [
                'user_id' => $userId,
                'governance_id' => $governanceId,
                'status' => $request->status,
                'message' => $request->review_message, // Store in message field
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ];


            // Update or create user governance layer
            $userGovernance = UserGovernanceLayer::updateOrCreate(
                [
                    'user_id' => $userId,
                    'governance_id' => $governanceId,
                ],
                $data
            );
            Notification::create([
                'user_id' => $userId,
                'message' => $request->review_message
            ]);
            DB::commit();
    
            // Get updated counts for response
            $totalGovernanceLayers = GovernanceLayer::where('status', 'active')->count();
            $approvedGovernanceLayers = UserGovernanceLayer::where('user_id', $userId)
                ->where('status', 'approved')
                ->count();
                
            $pendingCount = UserGovernanceLayer::where('user_id', $userId)
                ->where('status', 'pending')
                ->count();
            
            $rejectedCount = UserGovernanceLayer::where('user_id', $userId)
                ->where('status', 'rejected')
                ->count();
    
            // Determine the action message based on old and new status
            $actionMessage = '';
            if ($oldStatus === 'approved' && $request->status === 'rejected') {
                $actionMessage = 'Governance layer rejected. It was previously approved.';
            } elseif ($oldStatus === 'rejected' && $request->status === 'approved') {
                $actionMessage = 'Governance layer approved. It was previously rejected.';
            } elseif ($oldStatus === 'pending' && $request->status === 'approved') {
                $actionMessage = 'Governance layer approved successfully.';
            } elseif ($oldStatus === 'pending' && $request->status === 'rejected') {
                $actionMessage = 'Governance layer rejected successfully.';
            } else {
                $actionMessage = 'Governance layer ' . $request->status . ' successfully.';
            }
    
            // Add message change info if applicable
            if ($oldMessage != $request->review_message) {
                $actionMessage .= ' Review message has been updated.';
            }
    
            return response()->json([
                'success' => true,
                'message' => $actionMessage,
                'old_status' => $oldStatus,
                'new_status' => $request->status,
                'review_message' => $request->review_message,
                'old_message' => $oldMessage,
                'pending_count' => $pendingCount,
                'rejected_count' => $rejectedCount,
                'approved_count' => $approvedGovernanceLayers,
                'total_layers' => $totalGovernanceLayers,
                'all_approved' => ($totalGovernanceLayers > 0 && $approvedGovernanceLayers === $totalGovernanceLayers),
                'can_toggle' => true,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
    
            return response()->json([
                'success' => false,
                'message' => 'Failed to update governance layer. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
    /**
     * Submit final review for a representation
     */
    public function submitReview(Request $request, Representation $representation)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected,hold',
            'review_message' => 'required_if:status,rejected,hold|nullable|string|max:500',
        ], [
            'review_message.required_if' => 'Review message is required for reject or request changes status.',
            'status.in' => 'The selected status is invalid.',
        ]);

        try {
            DB::beginTransaction();

            $oldStatus = $representation->status;

            $representation->update([
                'status' => $request->status,
                'review_message' => $request->review_message,
                'last_reviewed' => now(),
                'reviewer_user_id' => auth()->id(),
            ]);

          
            RepresentationReviewLog::create([
                'user_id' => auth()->id(),
                'merchant_id' => $representation->merchant->id,
                'representation_id' => $representation->id,
                'before_status' => $oldStatus,
                'after_status' => $request->status,
                'message' => $request->review_message,
            ]);

            User::where('id',$representation->merchant->id)->update([
                'governance_check' => 1
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Representation reviewed successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit review. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}