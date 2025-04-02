<?php

namespace App\Http\Controllers;
use App\Models\userDeviceInfo;

use Illuminate\Http\Request;

class userDeviceInfoController extends Controller
{
    public function addDevice(Request $request)
    {
        try {
            // Validate the request data
            $request->validate([
                'id_code' => 'required|integer',
                'device_model' => 'required|string|max:100',
                'device_brand' => 'required|string|max:100',
                'device_sys_name' => 'required|string|max:100',
                'device_sys_version' => 'required|string|max:100',
                'device_id' => 'required|string|max:100',
            ]);

            // Insert the validated data into the database
            userDeviceInfo::create([
                'id_code' => $request->id_code,
                'device_model' => $request->device_model,
                'device_brand' => $request->device_brand,
                'device_sys_name' => $request->device_sys_name,
                'device_sys_version' => $request->device_sys_version,
                'device_id' => $request->device_id,
            ]);

            return response()->json(['message' => 'Device info added successfully'], 201);

        } catch(e) {
            return response()->json(['error' => 'Failed to add device info', 'details' => $e->getMessage()], 500);                      
        }
    }

    public function verifyDevice(Request $request)
    {
        try {
            // Validate the request data
            $request->validate([
                'device_id' => 'required|string|max:100',
            ]);

            // Check if the device exists in the database
            $deviceInfo = userDeviceInfo::where('device_id', $request->device_id)
            ->where('id_code', $request->id_code)->first();

            if ($deviceInfo) {
                if ($deviceInfo->active_status == 'Y') {
                    return response()->json(['status' => true], 200);
                } else if ($deviceInfo->active_status == 'N') {
                    return response()->json(['status' => false], 200);
                }
            } else {
                return response()->json(['status' => 'none'], 200);
            }

        } catch(\Exception $e) {
            return response()->json(['error' => 'Failed to verify device', 'details' => $e->getMessage()], 500);                      
        }

    }

    public function getAllDeviceInfo(Request $request)
    {
        try {
            $deviceInfo = userDeviceInfo::where('id_code', $request->id_code)->get();
            return response()->json($deviceInfo, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve device info', 'details' => $e->getMessage()], 500);
        }
    }
}
