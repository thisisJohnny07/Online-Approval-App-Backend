<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GlCrbList;
use App\Models\AmountRange;
use App\Models\ApproverTagging;
use App\Models\ReviewerTagging;
use App\Models\ApprovalStatus;
use App\Models\ReviewStatus;
use App\Models\GlRefDocumentsUploaded;
use Carbon\Carbon;

class GlCrbListController extends Controller
{
    // Read data
    public function index(Request $request)
    {
        $transaction_statuses = $request->query('transaction_statuses');
        $start_date = $request->query('start_date');
        $end_date = $request->query('end_date');
        $user_id = $request->query('id'); // Get the current user's ID
        $approval_access = $request->input('approval_access'); // Get the approval access value

        if (empty($transaction_statuses)) {
            return response()->json(['message' => 'At least one transaction status is required'], 400);
        }

        if (!$start_date || !$end_date) {
            return response()->json(['message' => 'Start date and end date are required'], 400);
        }

        // Convert approval_access into an array and trim each value
        $approvalAccessArray = explode(',', $approval_access);
        $approvalAccessArray = array_map('trim', $approvalAccessArray);

        // Fetch records based on transaction statuses and date range
        $records = GlCrbList::whereIn('transaction_status', $transaction_statuses)
            ->whereBetween('date_trans', [$start_date, $end_date])
            ->orderBy('date_trans', 'desc')
            ->get();

        // Include records for 'UT' and 'U' statuses based on ReviewerTagging conditions
        if (in_array('UT', $transaction_statuses) || in_array('U', $transaction_statuses)) {
            if (in_array('transmit', $approvalAccessArray)) {
                $records = $records->filter(function ($record) use ($user_id) {
                    // Get the check_amount and determine the review_type
                    $totalAmount = round($record->total_amount, 2);
        
                    $amountRange = AmountRange::where('range_from', '<=', $totalAmount)
                        ->where('range_to', '>=', $totalAmount)
                        ->first();
        
                    if (!$amountRange) {
                        return true; // Include the record if no matching amount range is found
                    }
        
                    $review_type = $amountRange->review_type;
        
                    // Retrieve all matching ReviewerTagging records
                    $ReviewerTaggings = ReviewerTagging::where('transaction_type', 'like', '%' . $record->doc_type . '%')
                        ->where('review_type', $review_type)
                        ->get();
        
                    if ($ReviewerTaggings->isEmpty()) {
                        return true; // Include the record if no ReviewerTagging is found
                    }
        
                    // Check if the user ID matches any of the conditions
                    $matches = $ReviewerTaggings->filter(function ($tagging) use ($user_id) {
                        $conditions = array_map('trim', explode(',', $tagging->condition));
                        return in_array($user_id, $conditions);
                    });
        
                    return $matches->isNotEmpty(); // Include the record if any match
                });
            }
        }
        
        // Include records for 'TT' and 'T' statuses based on ApproverTagging conditions
        if (in_array('TT', $transaction_statuses) || in_array('T', $transaction_statuses)) {
            if (in_array('approve', $approvalAccessArray)) {
                $records = $records->filter(function ($record) use ($user_id) {
                    // Get the check_amount and determine the approval_type
                    $totalAmount = round($record->total_amount, 2);

                    $amountRange = AmountRange::where('range_from', '<=', $totalAmount)
                        ->where('range_to', '>=', $totalAmount)
                        ->first();

                    if (!$amountRange) {
                        return true; // Include the record if no matching amount range is found
                    }

                    $approval_type = $amountRange->approval_type;

                    $ApproverTaggings = ApproverTagging::where('transaction_type', 'like', '%' . $record->doc_type . '%')
                        ->where('approval_type', $approval_type)
                        ->get();

                    if ($ApproverTaggings->isEmpty()) {
                        return true; // Include the record if no ApproverTagging is found
                    }

                    // Check if the user ID matches any of the conditions in all rows
                    $matches = $ApproverTaggings->filter(function ($tagging) use ($user_id) {
                        $conditions = array_map('trim', explode(',', $tagging->condition));
                        return in_array($user_id, $conditions);
                    });

                    return $matches->isNotEmpty(); // Include the record if any match
                });
            }
        }

        if ($records->isEmpty()) {
            return response()->json(['message' => 'No record found'], 404);
        }

        return response()->json($records);
    }

    // forward to reviewer
    public function forward(Request $request)
    {
        $doc_no = $request->query('doc_no');
        $doc_type = $request->query('doc_type');
    
        if (!$doc_no || !$doc_type) {
            return response()->json(['message' => 'Document Number and Document Type are required'], 400);
        }

        // Find records that match the given doc_no and doc_type
        $record = GlCrbList::where('doc_no', $doc_no)
            ->where('doc_type', $doc_type)
            ->first();

        // Check if any records exist
        if (!$record) {
            return response()->json(['message' => 'No matching records found'], 404);
        }

        // Check if the transaction has already been forwarded
        if (!in_array($record->transaction_status, ['R', 'UTR', 'UR'])) {
            return response()->json(['message' => 'The transaction has already been executed.'], 400);
        }

        $hasAttachment = GlRefDocumentsUploaded::where('doc_type', $record->doc_type)
        ->where('doc_no', $record->doc_no)
        ->exists();

        $newStatus = $hasAttachment ? 'UT' : 'U';
    
        // Update only transaction_status
        $record->update(['transaction_status' => $newStatus]);
    
        // Check if update was successful
        return response()->json([
            'message' => 'Record updated successfully',
            'updated_record' => $record
        ]);
    }

    // Review
    public function review(Request $request)
    {
        $doc_no = $request->query('doc_no');
        $doc_type = $request->query('doc_type');
        $user_id = $request->query('id'); // Use 'id' for the user performing the review
    
        if (!$doc_no || !$doc_type || !$user_id) {
            return response()->json(['message' => 'All fields are required'], 400);
        }
    
        // Find records that match the given doc_no and doc_type
        $record = GlCrbList::where('doc_no', $doc_no)
            ->where('doc_type', $doc_type)
            ->first();
    
        // Check if any records exist
        if (!$record) {
            return response()->json(['message' => 'No matching records found'], 404);
        }
    
        // Cast check_amount to match the precision of range_from and range_to
        $totalAmount = round($record->total_amount, 2);
    
        // Check the amount range in the AmountRange table
        $amountRange = AmountRange::where('range_from', '<=', $totalAmount)
            ->where('range_to', '>=', $totalAmount)
            ->first();
    
        if (!$amountRange) {
            return response()->json(['message' => 'No matching amount range found'], 404);
        }
    
        $review_type = $amountRange->review_type;
    
        // Retrieve all matching ReviewerTagging records
        $ReviewerTaggings = ReviewerTagging::where('transaction_type', 'like', '%' . $doc_type . '%')
            ->where('review_type', $review_type)
            ->orderBy('id', 'asc') // Ensure the order is consistent
            ->get();
    
        if ($ReviewerTaggings->isEmpty()) {
            return response()->json(['message' => 'No reviewer tagging found for this transaction type and review type'], 404);
        }
    
        // Iterate through each condition group
        foreach ($ReviewerTaggings as $tagging) {
            $conditions = array_map('trim', explode(',', $tagging->condition));
    
            // Check if the user ID is in the current condition group
            if (in_array($user_id, $conditions)) {
                // Insert or update the current user's review status as 'PARTIAL'
                ReviewStatus::updateOrCreate(
                    [
                        'doc_type' => $doc_type,
                        'doc_no' => $doc_no,
                        'reviewer' => $user_id,
                    ],
                    [
                        'transaction_type' => 'general',
                        'review_type' => $review_type,
                        'review_status' => 'PARTIAL',
                    ]
                );
    
                // Check if all reviewers in the current condition group have completed their reviews
                $partialReviews = ReviewStatus::where('doc_type', $doc_type)
                    ->where('doc_no', $doc_no)
                    ->whereIn('reviewer', $conditions)
                    ->where('review_status', 'PARTIAL')
                    ->pluck('reviewer')
                    ->toArray();
    
                $remainingReviewers = array_diff($conditions, $partialReviews);
    
                // If all reviewers in the current condition group have reviewed, mark them as 'REVIEWED'
                if (empty($remainingReviewers)) {
                    ReviewStatus::where('doc_type', $doc_type)
                        ->where('doc_no', $doc_no)
                        ->whereIn('reviewer', $conditions)
                        ->update(['review_status' => 'REVIEWED']);
    
                    // Update the transaction status if all condition groups are reviewed
                    $anyConditionReviewed = $ReviewerTaggings->contains(function ($tagging) use ($doc_type, $doc_no) {
                        $groupConditions = array_map('trim', explode(',', $tagging->condition));
                        $reviewed = ReviewStatus::where('doc_type', $doc_type)
                            ->where('doc_no', $doc_no)
                            ->whereIn('reviewer', $groupConditions)
                            ->where('review_status', 'REVIEWED')
                            ->count();
                    
                        return $reviewed === count($groupConditions);
                    });
    
                    // If at least one group condition is reviewed, update the transaction status
                    if ($anyConditionReviewed) {
                        // Determine the new transaction status
                        if ($record->transaction_status == 'U') {
                            $newStatus = 'T'; // Transition from Unreviewed to Reviewed
                        } else if ($record->transaction_status == 'UT') {
                            $newStatus = 'TT'; // Transition from Under Review to Fully Reviewed
                        }

                        // Update the main table's transaction_status
                        $record->update([
                            'transaction_status' => $newStatus,
                            'reviewed_by' => $user_id,
                            'reviewed_date' => now(),
                        ]);
                    }
                }
    
                // Return success response for the current review
                return response()->json([
                    'message' => 'Record reviewed successfully',
                    'updated_record' => $record,
                ]);
            }
        }
    
        // If the user is not part of any condition group, return an error
        return response()->json(['message' => 'You are not authorized to review this transaction'], 403);
    }

    // Return
    public function return(Request $request)
    {
        $doc_no = $request->query('doc_no');
        $doc_type = $request->query('doc_type');
        $remarks = $request->query('remarks');
        $approval_access = $request->query('approval_access');
        $isApprover = filter_var($request->query('isApprover'), FILTER_VALIDATE_BOOLEAN);
    
        if (!$doc_no || !$doc_type || !$remarks || !$approval_access) {
            return response()->json(['message' => 'All parameter are required'], 400);
        }

        // Find records that match the given doc_no and doc_type
        $record = GlCrbList::where('doc_no', $doc_no)
            ->where('doc_type', $doc_type)
            ->first();

        // Check if any records exist
        if (!$record) {
            return response()->json(['message' => 'No matching records found'], 404);
        }

        if (!in_array($record->transaction_status, ['U', 'UT', 'T', 'TT'])) {
            return response()->json(['message' => 'The transaction has already been executed.'], 400);
        }

        if($record->transaction_status == 'U' || $record->transaction_status == 'T') {
            $newStatus = 'UR';
        } else if($record->transaction_status == 'UT' || $record->transaction_status == 'TT') {
            $newStatus = 'UTR';
        }
    
        if($approval_access === 'transmit' || $approval_access === 'upload, transmit' || (($approval_access === 'transmit, approve' || $approval_access === 'upload, transmit, approve') && $isApprover === false)) {
            // for reviewer
            $record->update([
                'transaction_status' => $newStatus, 
                'returned_remarks' => $remarks, 
            ]);
        } else if($approval_access === 'approve' || (($approval_access === 'transmit, approve' || $approval_access === 'upload, transmit, approve') && $isApprover === true)) {
            // for approver
            $record->update([
                'transaction_status' => $newStatus, 
                'approver_remarks' => $remarks, 
            ]);
        }
    
        // Check if update was successful
        return response()->json([
            'message' => 'Record updated successfully',
            'updated_record' => $record
        ]);
    }

    // Approve
    public function approveReject(Request $request)
    {
        $doc_no = $request->query('doc_no');
        $doc_type = $request->query('doc_type');
        $approved_by = $request->query('approved_by');
        $new_status = $request->query('new_status');
        $user_id = $request->query('id');

        if (!$doc_no || !$doc_type || !$approved_by || !$new_status || !$user_id) {
            return response()->json(['message' => 'All fields are required'], 400);
        }

        // Find records that match the given doc_no and doc_type
        $record = GlCrbList::where('doc_no', $doc_no)
            ->where('doc_type', $doc_type)
            ->first();

        // Check if any records exist
        if (!$record) {
            return response()->json(['message' => 'No matching records found'], 404);
        }

        if (!in_array($record->transaction_status, ['TT', 'T'])) {
            return response()->json(['message' => 'The transaction has already been executed.'], 400);
        }

        // Handle rejection directly
        if ($new_status === 'N') {
            $record->update([
                'transaction_status' => $new_status,
            ]);

            return response()->json([
                'message' => 'Record rejected successfully',
                'updated_record' => $record,
            ]);
        }

        // Handle approval logic
        if ($new_status === 'A') {
            // Cast check_amount to match the precision of range_from and range_to
            $totalAmount = round($record->total_amount, 2);

            // Check the amount range in the AmountRange table
            $amountRange = AmountRange::where('range_from', '<=', $totalAmount)
                ->where('range_to', '>=', $totalAmount)
                ->first();

            if (!$amountRange) {
                return response()->json(['message' => 'No matching amount range found'], 404);
            }

            $approval_type = $amountRange->approval_type;

            // Retrieve all matching ApproverTagging records
            $ApproverTaggings = ApproverTagging::where('transaction_type', 'like', '%' . $doc_type . '%')
                ->where('approval_type', $approval_type)
                ->orderBy('id', 'asc') // Ensure the order is consistent
                ->get();

            if ($ApproverTaggings->isEmpty()) {
                return response()->json(['message' => 'No approver tagging found for this transaction type and approval type'], 404);
            }

            // Iterate through each condition group
            foreach ($ApproverTaggings as $tagging) {
                $conditions = array_map('trim', explode(',', $tagging->condition));

                // Check if the user ID is in the current condition group
                if (in_array($user_id, $conditions)) {
                    // Insert or update the current user's approval status as 'PARTIAL'
                    ApprovalStatus::updateOrCreate(
                        [
                            'doc_type' => $doc_type,
                            'doc_no' => $doc_no,
                            'approver' => $user_id,
                        ],
                        [
                            'transaction_type' => 'general',
                            'approval_type' => $approval_type,
                            'approval_status' => 'PARTIAL',
                        ]
                    );

                    // Check if all approvers in the current condition group have completed their approvals
                    $partialApprovals = ApprovalStatus::where('doc_type', $doc_type)
                        ->where('doc_no', $doc_no)
                        ->whereIn('approver', $conditions)
                        ->where('approval_status', 'PARTIAL')
                        ->pluck('approver')
                        ->toArray();

                    $remainingApprovers = array_diff($conditions, $partialApprovals);

                    // If all approvers in the current condition group have approved, mark them as 'APPROVED'
                    if (empty($remainingApprovers)) {
                        ApprovalStatus::where('doc_type', $doc_type)
                            ->where('doc_no', $doc_no)
                            ->whereIn('approver', $conditions)
                            ->update(['approval_status' => 'APPROVED']);

                        // Update the transaction status if at least one group condition is approved
                        $anyConditionApproved = $ApproverTaggings->contains(function ($tagging) use ($doc_type, $doc_no) {
                            $groupConditions = array_map('trim', explode(',', $tagging->condition));
                            $approved = ApprovalStatus::where('doc_type', $doc_type)
                                ->where('doc_no', $doc_no)
                                ->whereIn('approver', $groupConditions)
                                ->where('approval_status', 'APPROVED')
                                ->count();

                            return $approved === count($groupConditions);
                        });

                        // If at least one group condition is approved, update the transaction status
                        if ($anyConditionApproved) {
                            // Determine the new transaction status
                            if ($record->transaction_status == 'T') {
                                $newStatus = 'A'; // Transition from Reviewed to Approved
                            } else if ($record->transaction_status == 'TT') {
                                $newStatus = 'AA'; // Transition from Fully Reviewed to Fully Approved
                            }

                            // Update the main table's transaction_status
                            $record->update([
                                'transaction_status' => $newStatus,
                                'approved_by' => $approved_by,
                                'approved_date' => now(),
                            ]);
                        }
                    }

                    // Return success response for the current approval
                    return response()->json([
                        'message' => 'Record approved successfully',
                        'updated_record' => $record,
                    ]);
                }
            }

            // If the user is not part of any condition group, return an error
            return response()->json(['message' => 'You are not authorized to approve this transaction'], 403);
        }

        // If the new status is neither 'A' nor 'N', return an error
        return response()->json(['message' => 'Invalid status'], 400);
    }
}