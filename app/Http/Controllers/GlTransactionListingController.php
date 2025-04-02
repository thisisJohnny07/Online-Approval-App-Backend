<?php

namespace App\Http\Controllers;

use App\Models\GlTransactionListing;
use Illuminate\Http\Request;

class GlTransactionListingController extends Controller
{
    // Display CDB Listing
    public function listing(Request $request)
    {
        $doc_no = $request->query('doc_no');
        $doc_type = $request->query('doc_type');
    
        if (!$doc_no || !$doc_type) {
            return response()->json(['message' => 'Document Number and Document Type are required'], 400);
        }
    
        // Fetch all records where doc_no and doc_type match the provided values
        $records = GlTransactionListing::where('doc_no', $doc_no)
            ->where('doc_type', $doc_type)
            ->get();
    
        if ($records->isEmpty()) {
            return response()->json(['message' => 'No records found'], 404);
        }
    
        return response()->json($records);
    }    
}