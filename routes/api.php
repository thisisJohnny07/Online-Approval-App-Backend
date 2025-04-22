<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\GlCdbListController;
use App\Http\Controllers\GlCrbListController;
use App\Http\Controllers\GlArbListController;
use App\Http\Controllers\GlApbListController;
use App\Http\Controllers\GlGjListController;
use App\Http\Controllers\GlTransactionListingController;
use App\Http\Controllers\GlRefDocumentsUploadedController;
use App\Http\Controllers\userDeviceInfoController;
use App\Http\Controllers\usersDeviceLogs;
use App\Http\Controllers\GlCountController;

// Authentication
Route::post('/register', [UsersController::class, 'register']);
Route::post('/login', [UsersController::class, 'login']);
Route::get('/dashboard', [UsersController::class, 'dashboard']);
Route::post('/logout', [UsersController::class, 'logout']);
Route::post('/send-otp', [UsersController::class, 'sendOtp']);
Route::post('/verify-otp', [UsersController::class, 'verifyOtp']);
Route::post('/change-password', [UsersController::class, 'changePassword']);
Route::post('/register-fingerprint', [UsersController::class, 'registerFingerprint']);
Route::post('/login-fingerprint', [UsersController::class, 'loginWithFingerprint']);

// device verification
Route::get('/device-info', [userDeviceInfoController::class, 'getAllDeviceInfo']);
Route::post('/verify-device', [userDeviceInfoController::class, 'verifyDevice']);
Route::post('/add-device', [userDeviceInfoController::class, 'addDevice']);
Route::delete('/delete-device', [userDeviceInfoController::class, 'deleteDevice']);
Route::delete('/delete-all-device', [userDeviceInfoController::class, 'deleteAllDevice']);

// logs
Route::get('/device-logs', [usersDeviceLogs::class, 'getAllUserDeviceLogs']);

// dashboard counts
Route::get('gl-count', [GlCountController::class, 'countRecords']);

// GCL_DB_LIST
Route::get('glcdb', [GlCdbListController::class, 'index']);
Route::put('forward', [GlCdbListController::class, 'forward']);
Route::put('review', [GlCdbListController::class, 'review']);
Route::put('return', [GlCdbListController::class, 'return']);
Route::put('approve-reject', [GlCdbListController::class, 'approveReject']);

Route::get('listing', [GlTransactionListingController::class, 'listing']);

// GCL_RB_LIST
Route::get('glcrb', [GlCrbListController::class, 'index']);
Route::put('glcrb-forward', [GlCrbListController::class, 'forward']);
Route::put('glcrb-review', [GlCrbListController::class, 'review']);
Route::put('glcrb-return', [GlCrbListController::class, 'return']);
Route::put('glcrb-approve-reject', [GlCrbListController::class, 'approveReject']);

// GCL_ARB_LIST
Route::get('glarb', [GlArbListController::class, 'index']);
Route::put('glarb-forward', [GlArbListController::class, 'forward']);
Route::put('glarb-review', [GlArbListController::class, 'review']);
Route::put('glarb-return', [GlArbListController::class, 'return']);
Route::put('glarb-approve-reject', [GlArbListController::class, 'approveReject']);

// GlC_APB_LIST
Route::get('glapb', [GlApbListController::class, 'index']);
Route::put('glapb-forward', [GlApbListController::class, 'forward']);
Route::put('glapb-review', [GlApbListController::class, 'review']);
Route::put('glapb-return', [GlApbListController::class, 'return']);
Route::put('glapb-approve-reject', [GlApbListController::class, 'approveReject']);

// GL_GJ_LIST
Route::get('glgj', [GlGjListController::class, 'index']);
Route::put('glgj-forward', [GlGjListController::class, 'forward']);
Route::put('glgj-review', [GlGjListController::class, 'review']);
Route::put('glgj-return', [GlGjListController::class, 'return']);
Route::put('glgj-approve-reject', [GlGjListController::class, 'approveReject']);

// document uploading
Route::get('documents', [GlRefDocumentsUploadedController::class, 'index']);
Route::post('upload', [GlRefDocumentsUploadedController::class, 'upload']);
Route::get('get-document', [GlRefDocumentsUploadedController::class, 'getDocument']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');