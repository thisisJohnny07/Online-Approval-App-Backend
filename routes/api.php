<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\GlCdbListController;
use App\Http\Controllers\GlCrbListController;
use App\Http\Controllers\GlTransactionListingController;
use App\Http\Controllers\GlRefDocumentsUploadedController;
use App\Http\Controllers\userDeviceInfoController;

// Authentication
Route::post('/register', [UsersController::class, 'register']);
Route::post('/login', [UsersController::class, 'login']);
Route::get('/dashboard', [UsersController::class, 'dashboard']);
Route::post('/logout', [UsersController::class, 'logout']);
Route::post('/send-otp', [UsersController::class, 'sendOtp']);
Route::post('/verify-otp', [UsersController::class, 'verifyOtp']);
Route::post('/change-password', [UsersController::class, 'changePassword']);

// device verification
Route::get('/device-info', [userDeviceInfoController::class, 'getAllDeviceInfo']);
Route::post('/verify-device', [userDeviceInfoController::class, 'verifyDevice']);
Route::post('/add-device', [userDeviceInfoController::class, 'addDevice']);

// GCL_DB_LIST
Route::get('glcdb', [GlCdbListController::class, 'index']);
Route::put('forward', [GlCdbListController::class, 'forward']);
Route::put('review', [GlCdbListController::class, 'review']);
Route::put('return', [GlCdbListController::class, 'return']);
Route::put('approve-reject', [GlCdbListController::class, 'approveReject']);
Route::get('disbursement-count', [GlCdbListController::class, 'disbursementCount']);

Route::get('listing', [GlTransactionListingController::class, 'listing']);

// GCL_RB_LIST
Route::get('glcrb', [GlCrbListController::class, 'index']);
Route::put('glcrb-forward', [GlCrbListController::class, 'forward']);
Route::put('glcrb-review', [GlCrbListController::class, 'review']);
Route::put('glcrb-return', [GlCrbListController::class, 'return']);
Route::put('glcrb-approve-reject', [GlCrbListController::class, 'approveReject']);
Route::get('cash-receipt-count', [GlCrbListController::class, 'cashReceiptCount']);

// document uploading
Route::get('documents', [GlRefDocumentsUploadedController::class, 'index']);
Route::post('upload', [GlRefDocumentsUploadedController::class, 'upload']);
Route::get('get-document', [GlRefDocumentsUploadedController::class, 'getDocument']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');