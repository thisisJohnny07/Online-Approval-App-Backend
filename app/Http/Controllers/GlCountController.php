<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GlApbList;
use App\Models\GlArbList;
use App\Models\GlCdbList;
use App\Models\GlCrbList;
use App\Models\GlGjList;
use Carbon\Carbon;

class GlCountController extends Controller
{
    public function countRecords(Request $request)
    {
        // Get the mod_access value as a comma-separated string
        $modAccessString = $request->input('mod_access');

        // Convert the comma-separated string into an array
        $modAccessArray = explode(',', $modAccessString);
        
        // Trim any extra spaces around the values
        $modAccessArray = array_map('trim', $modAccessArray);

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
            $oneMonthAgo = Carbon::now()->subMonth();
            $now = Carbon::now();

            $records = $modelClass::whereBetween('date_trans', [$oneMonthAgo, $now])->get();

            // Count the different transaction statuses
            $toUploadCount  = $records->where('transaction_status', 'R')->count();
            $toReviewCount  = $records->whereIn('transaction_status', ['UT', 'U'])->count();
            $toApproveCount = $records->whereIn('transaction_status', ['T', 'TT'])->count();
            $returnedCount  = $records->whereIn('transaction_status', ['UR', 'UTR'])->count();

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