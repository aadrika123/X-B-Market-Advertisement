<?php

use App\Http\Controllers\Marriage\MarriageRegistrationController;
use Illuminate\Support\Facades\Route;


/**
 * | Module Id = 10
 * | Marraige Registration
 */
// Route::group(['middleware' => ['auth.citizen', 'json.response']], function () {

#> Controller 01
Route::controller(MarriageRegistrationController::class)->group(function () {
    Route::post('apply', 'apply');                                              #API_ID=100101
    Route::post('get-doc-list', 'getDocList');                                  #API_ID=100102
    Route::post('upload-document', 'uploadDocument');                           #API_ID=100103
    Route::post('get-uploaded-document', 'getUploadedDocuments');               #API_ID=100104
    Route::post('static-details', 'staticDetails');                             #API_ID=100105
    Route::post('applied-application', 'listApplications');                     #API_ID=100106
    Route::post('inbox', 'inbox');                                              #API_ID=100107
    Route::post('details', 'details');                                          #API_ID=100108
    Route::post('set-appiontment-date', 'appointmentDate');                     #API_ID=100109
    Route::post('final-approval-rejection', 'approvalRejection');               #API_ID=100110
    Route::post('doc-verify-reject', 'docVerifyReject');                        #API_ID=100111
    Route::post('approved-application', 'approvedApplication');                 #API_ID=100112
    Route::post('edit-application', 'editApplication');                         #API_ID=100113
    Route::post("generate-order-id", "generateOrderId");                        #API_ID=100114
    Route::post("offline-payment", "offlinePayment");                           #API_ID=100115
    Route::post("payment-receipt", "paymentReceipt");                           #API_ID=100116
    Route::post("save-tran-dtl", "storeTransactionDtl");                        #API_ID=100117
    Route::post("search-application", "searchApplication");                     #API_ID=100118
    Route::post("post-next-level", "postNextLevel");                            #API_ID=100119

});
// });
