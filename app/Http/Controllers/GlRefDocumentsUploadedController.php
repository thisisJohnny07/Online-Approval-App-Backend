<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GlRefDocumentsUploaded;
use Illuminate\Support\Facades\Validator;

class GlRefDocumentsUploadedController extends Controller
{
    // Fetch and return all records
    public function index(Request $request)
    {
        $doc_no = $request->query('doc_no');
        $doc_type = $request->query('doc_type');

        if (!$doc_no || !$doc_type) {
            return response()->json(['message' => 'Document Number and Document Type are required'], 400);
        }

        $records = GlRefDocumentsUploaded::where('doc_no', $doc_no)
        ->where('doc_type', $doc_type)
        ->get();

        if ($records->isEmpty()) {
            return response()->json(['message' => 'No records found'], 404);
        }
        
        return response()->json($records);
    }

    // Fetch and specific document
    public function getDocument(Request $request)
    {
        $id = $request->query('id');

        if (!$id) {
            return response()->json(['message' => 'ID is required'], 400);
        }

        $records = GlRefDocumentsUploaded::where('id', $id)->get();

        if ($records->isEmpty()) {
            return response()->json(['message' => 'No records found'], 404);
        }
        
        return response()->json($records);
    }

    // Upload method to handle multiple files
    public function upload(Request $request)
    {
        // Validate uploaded files and other required fields
        $validator = Validator::make($request->all(), [
            'files.*' => 'required|file|mimes:jpg,png,jpeg,svg,pdf', // Accept multiple file types
            'doc_type' => 'required|string|max:15',
            'doc_no' => 'required|integer',
            'uploaded_by' => 'required|string|max:50',
        ]);

        // If validation fails, return error response
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $uploadedFiles = [];

        // Check if files were uploaded
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                // Get the original file name
                $fileName = $file->getClientOriginalName();
                $filePath = 'uploads/documents/'; // You can change this path as needed

                // Store the file in the specified directory (public/uploads/documents)
                $file->move(public_path($filePath), $fileName);

                // Store file details in the database
                $document = new GlRefDocumentsUploaded();
                $document->doc_type = $request->doc_type;
                $document->doc_no = $request->doc_no;
                $document->file_name = $fileName;
                $document->file_path = $filePath;
                $document->uploaded_by = $request->uploaded_by;
                $document->date_uploaded = now()->format('Y-m-d');
                $document->save();

                $uploadedFiles[] = $document;
            }

            return response()->json([
                'status' => true,
                'message' => 'Files uploaded successfully',
                'uploaded_files' => $uploadedFiles,
            ], 201);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'No files uploaded.',
            ], 400);
        }
    }
}