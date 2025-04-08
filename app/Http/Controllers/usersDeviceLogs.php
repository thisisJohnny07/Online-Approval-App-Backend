<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DeviceLogs;

class usersDeviceLogs extends Controller
{
    public function getAllUserDeviceLogs(Request $request)
    {
        try {
            // Validate the request to ensure id_code is provided
            $request->validate([
                'id_code' => 'required|integer',
            ]);

            // Retrieve device logs for the given id_code
            $deviceLogs = DeviceLogs::where('id_code', $request->id_code)
                        ->orderBy('date_time', 'desc')
                        ->get();

            // Return the logs with a success response
            return response()->json([
                'success' => true,
                'message' => 'Device logs retrieved successfully',
                'data' => $deviceLogs,
            ], 200);
        } catch (\Exception $e) {
            // Handle exceptions and return an error response
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve device logs',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
