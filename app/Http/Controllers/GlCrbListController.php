<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GlCrbList;
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

        if (empty($transaction_statuses)) {
            return response()->json(['message' => 'At least one transaction status is required'], 400);
        }

        if ($start_date && $end_date) {
            $records = GlCrbList::whereIn('transaction_status', $transaction_statuses)
                ->whereBetween('date_trans', [$start_date, $end_date])
                ->orderBy('date_trans', 'desc')
                ->get();
        } else {
            $records = GlCrbList::whereIn('transaction_status', $transaction_statuses)
            ->orderBy('date_trans', 'desc')
            ->get();
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
        $reviewed_by = $request->query('reviewed_by');
    
        if (!$doc_no || !$doc_type || !$reviewed_by) {
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

        if (!in_array($record->transaction_status, ['U', 'UT'])) {
            return response()->json(['message' => 'The transaction has already been executed.'], 400);
        }

        if($record->transaction_status == 'U') {
            $newStatus = 'T';
        } else if($record->transaction_status == 'UT') {
            $newStatus = 'TT';
        }
    
        // Update only transaction_status
        $record->update([
            'transaction_status' => $newStatus,
            'reviewed_by' => $reviewed_by, 
            'reviewed_date' => now()
        ]);
    
        // Check if update was successful
        return response()->json([
            'message' => 'Record updated successfully',
            'updated_record' => $record
        ]);
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
    
        if (!$doc_no || !$doc_type || !$approved_by || !$new_status) {
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

        // Update only transaction_status
        if($new_status === 'A') {
            $record->update([
                'transaction_status' => $new_status,
                'approved_by' => $approved_by, 
                'approved_date' => now()
            ]);
        } else if ($new_status == 'N') {
            $record->update([
                'transaction_status' => $new_status,
            ]);
        }
    
        // Check if update was successful
        return response()->json([
            'message' => 'Record updated successfully',
            'updated_record' => $record
        ]);
    }
}
