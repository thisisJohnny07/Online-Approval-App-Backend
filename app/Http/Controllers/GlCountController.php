<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GlApbList;
use App\Models\GlArbList;
use App\Models\GlCdbList;
use App\Models\GlCrbList;
use App\Models\GlGjList;
use App\Models\AmountRange;
use App\Models\ApproverTagging;
use App\Models\ReviewerTagging;
use App\Models\ApprovalStatus;
use App\Models\ReviewStatus;
use Carbon\Carbon;

class GlCountController extends Controller
{
    public function countRecords(Request $request)
    {
        // Get the mod_access value as a comma-separated string
        $modAccessString = $request->input('mod_access');
        $user_id = $request->input('id'); // Get the current user's ID
        $approval_access = $request->input('approval_access'); // Get the approval access value
    
        // Convert the comma-separated string into an array
        $modAccessArray = explode(',', $modAccessString);
        $approvalAccessArray = explode(',', $approval_access);
    
        // Trim any extra spaces around the values
        $modAccessArray = array_map('trim', $modAccessArray);
        $approvalAccessArray = array_map('trim', $approvalAccessArray);
    
        // Define the models and their corresponding values
        $modelMapping = [
            'oa_cdb' => GlCdbList::class, // Disbursement
            'oa_crb' => GlCrbList::class, // Cash Receipt
            'oa_arb' => GlArbList::class, // Sales
            'oa_apb' => GlApbList::class, // Purchase
            'oa_gj'  => GlGjList::class    // Adjustments
        ];
    
        // Initialize an empty response array
        $response = [];
    
        // Loop through each mod_access value and count the records
        foreach ($modAccessArray as $modAccess) {
            // Check if the provided mod_access is valid
            if (!array_key_exists($modAccess, $modelMapping)) {
                return response()->json(['error' => 'Invalid mod_access value: ' . $modAccess], 400);
            }
    
            // Get the model class based on the mod_access
            $modelClass = $modelMapping[$modAccess];
    
            // Get the records within the last month
            $oneMonthAgo = Carbon::now()->subMonth()->startOfDay();
            $now = Carbon::now()->endOfDay();
    
            $records = $modelClass::whereBetween('date_trans', [$oneMonthAgo, $now])->get();
    
            // Count the different transaction statuses
            $toUploadCount  = $records->where('transaction_status', 'R')->count();
            $returnedCount  = $records->whereIn('transaction_status', ['UR', 'UTR'])->count();
    
            // Count 'U' and 'UT' statuses (to review)
            $toReviewCount = 0;
            if (in_array('transmit', $approvalAccessArray)) {
                // Apply custom logic for 'transmit'
                $toReviewCount = $records->filter(function ($record) use ($user_id) {
                    if (!in_array($record->transaction_status, ['U', 'UT'])) {
                        return false;
                    }
    
                    $alreadyReviewed = ReviewStatus::where('doc_type', $record->doc_type)
                        ->where('doc_no', $record->doc_no)
                        ->where('reviewer', $user_id)
                        ->exists();
    
                    if ($alreadyReviewed) {
                        return false;
                    }
    
                    $amount = $record->doc_type == 'CV' ? round($record->check_amount, 2) : round($record->total_amount, 2);
    
                    $amountRange = AmountRange::where('range_from', '<=', $amount)
                        ->where('range_to', '>=', $amount)
                        ->first();
    
                    if (!$amountRange) {
                        return false;
                    }
    
                    $review_type = $amountRange->review_type;
    
                    // Retrieve all matching ReviewerTagging records
                    $ReviewerTaggings = ReviewerTagging::where('transaction_type', 'like', '%' . $record->doc_type . '%')
                        ->where('review_type', $review_type)
                        ->get();
    
                    if ($ReviewerTaggings->isEmpty()) {
                        return false;
                    }
    
                    // Check if the user ID matches any of the conditions in all rows
                    foreach ($ReviewerTaggings as $tagging) {
                        $conditions = array_map('trim', explode(',', $tagging->condition));
                        if (in_array($user_id, $conditions)) {
                            return true;
                        }
                    }
    
                    return false;
                })->count();
            } else {
                // Count normally if 'transmit' is not in approval_access
                $toReviewCount = $records->whereIn('transaction_status', ['U', 'UT'])->count();
            }
    
            // Count 'T' and 'TT' statuses (to approve)
            $toApproveCount = 0;
            if (in_array('approve', $approvalAccessArray)) {
                // Apply custom logic for 'approve'
                $toApproveCount = $records->filter(function ($record) use ($user_id) {
                    if (!in_array($record->transaction_status, ['T', 'TT'])) {
                        return false;
                    }
    
                    $alreadyApproved = ApprovalStatus::where('doc_type', $record->doc_type)
                        ->where('doc_no', $record->doc_no)
                        ->where('approver', $user_id)
                        ->exists();
    
                    if ($alreadyApproved) {
                        return false;
                    }
    
                    $amount = $record->doc_type == 'CV' ? round($record->check_amount, 2) : round($record->total_amount, 2);
    
                    $amountRange = AmountRange::where('range_from', '<=', $amount)
                        ->where('range_to', '>=', $amount)
                        ->first();
    
                    if (!$amountRange) {
                        return false;
                    }
    
                    $approval_type = $amountRange->approval_type;
    
                    // Retrieve all matching ApproverTagging records
                    $ApproverTaggings = ApproverTagging::where('transaction_type', 'like', '%' . $record->doc_type . '%')
                        ->where('approval_type', $approval_type)
                        ->get();
    
                    if ($ApproverTaggings->isEmpty()) {
                        return false;
                    }
    
                    // Check if the user ID matches any of the conditions in all rows
                    foreach ($ApproverTaggings as $tagging) {
                        $conditions = array_map('trim', explode(',', $tagging->condition));
                        if (in_array($user_id, $conditions)) {
                            return true;
                        }
                    }
    
                    return false;
                })->count();
            } else {
                // Count normally if 'approve' is not in approval_access
                $toApproveCount = $records->whereIn('transaction_status', ['T', 'TT'])->count();
            }
    
            // Add the counts to the response array
            $response[$modAccess] = [
                'to_upload' => $toUploadCount,
                'to_review' => $toReviewCount,
                'to_approve' => $toApproveCount,
                'returned' => $returnedCount
            ];
        }
    
        // Return the final response as a JSON object
        return response()->json($response);
    }
}