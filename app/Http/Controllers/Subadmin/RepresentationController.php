<?php

namespace App\Http\Controllers\Subadmin;

use App\Http\Controllers\Controller;
use App\Models\Representation;
use App\Models\RepresentationReviewLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RepresentationController extends Controller
{
    /**
     * Display the representation for review
     */
    public function viewRepresentation($userId)
    {
        $representation = Representation::where('user_id', $userId)->with('user' , 'user.merchant')->first();

        // Check if it's been reviewed
        $isReviewed = false;
        if($representation){
            $isReviewed = $representation->reviewer_user_id && $representation->last_reviewed;
        }

        return view('subadmin.pages.merchants.representation-view', compact('representation', 'isReviewed'));
        
    }

    /**
     * Submit review for a representation
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
