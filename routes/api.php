<?php

use App\Http\Controllers\Advertisements\AgencyController;
use App\Http\Controllers\Advertisements\HoardingController;
use App\Http\Controllers\Advertisements\PrivateLandController;
use App\Http\Controllers\Advertisements\SearchController;
use App\Http\Controllers\Advertisements\SelfAdvetController;
use App\Http\Controllers\Advertisements\VehicleAdvetController;
use App\Http\Controllers\Bandobastee\BandobasteeController;
use App\Http\Controllers\Markets\BanquetMarriageHallController;
use App\Http\Controllers\Markets\LodgeController;
use App\Http\Controllers\Markets\HostelController;
use App\Http\Controllers\Markets\DharamshalaController;
use App\Http\Controllers\Params\ParamController;
use Illuminate\Routing\RouteGroup;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

/**
 * | Routers for Advertisement Modules
 * | Created On-14-12-2022 
 * | Created By- Anshu Kumar
 * | Changes By- Bikash Kumar (17 Jan 2023)
 * | Module Id for Advetisements=05
 * | Status - Closed By Bikash on 25 Apr 2023  , Re-Open For Bandobastee on 26 Apr 2023
 */
Route::post('advertisements/payment-success-failure', [ParamController::class, 'paymentSuccessFailure']);


// Route::group(['middleware' => ['auth.citizen', 'json.response']], function () {
// Route::group(['middleware' => ['auth:sanctum', 'request_logger']], function () {
Route::group(['middleware' => ['checkToken']], function () {
    /**
     * | Self Advertisements
     * | Controller-01
     * | Status - Closed By Bikash on 24 Apr 2023
     */
    Route::controller(SelfAdvetController::class)->group(function () {
        Route::post('advert/self/add-new', 'addNew');       // 01 ( Save Application )
        Route::post('advert/self/get-application-details-for-renew', 'applicationDetailsForRenew');       // 02 ( Renew Application )
        Route::post('advert/self/renewal-selfAdvt', 'renewalSelfAdvt');       // 03 ( Renew Application )
        Route::post('advert/self/list-self-advt-category', 'listSelfAdvtCategory');       // 04 ( Save Application )
        Route::post('advert/self/list-inbox', 'listInbox');      // 05 ( Application Inbox Lists )
        Route::post('advert/self/list-outbox', 'listOutbox');    // 06 ( Application Outbox Lists )
        Route::post('advert/self/get-details-by-id', 'getDetailsById');  // 07 ( Get Application Details By Application ID )
        Route::post('advert/self/list-applied-applications', 'listAppliedApplications');     // 08 ( Get Applied Applications List By CityZen )
        Route::post('advert/self/escalate-application', 'escalateApplication');  // 09 ( Escalate or De-escalate Application )
        Route::post('advert/self/list-escalated', 'listEscalated');  // 10 ( Special Inbox Applications )
        Route::post('advert/self/forward-next-level', 'forwordNextLevel');  // 11 ( Forward or Backward Application )
        Route::post('advert/self/comment-application', 'commentApplication');  // 12 ( Independent Comment )
        Route::post('advert/self/get-license-by-id', 'getLicenseById');  // 13 ( Get License By User ID )
        Route::post('advert/self/get-license-by-holding-no', 'getLicenseByHoldingNo');  // 14 ( Get License By Holding No )
        // Route::post('advert/self/get-license-details-by-license-no', 'getLicenseDetailso');  // 12 ( Get License Details By Licence No )
        Route::post('advert/self/view-advert-document', 'viewAdvertDocument');  // 15 ( Get Uploaded Document By Advertisement ID )
        Route::post('advert/self/view-active-document', 'viewActiveDocument');  // 16 ( Get Uploaded Document By Advertisement ID )
        Route::post('advert/self/get-details-by-license-no', 'getDetailsByLicenseNo');  // 17 ( Get Uploaded Document By Advertisement ID )
        Route::post('advert/self/view-documents-on-workflow', 'viewDocumentsOnWorkflow');  // 18 ( View Uploaded Document By Advertisement ID )
        // Route::post('advert/self/workflow-upload-document', 'workflowUploadDocument');  // 16 ( Workflow Upload Document )
        Route::post('advert/self/approved-or-reject', 'approvalOrRejection');          // 19 ( Approve or Reject )
        Route::post('advert/self/list-approved', 'listApproved');          // 20 ( Approved list for Citizen)
        Route::post('advert/self/list-rejected', 'listRejected');          // 21 ( Rejected list for Citizen)
        Route::post('advert/self/get-jsk-applications', 'getJSKApplications');          // 22 ( Get Applied Applications List By JSK )
        Route::post('advert/self/list-jsk-approved-application', 'listJskApprovedApplication');          // 23 ( Approved list for JSK)
        Route::post('advert/self/list-jsk-rejected-application', 'listJskRejectedApplication');          // 24 ( Rejected list for JSK)    
        Route::post('advert/self/generate-payment-order-id', 'generatePaymentOrderId');          // 25 ( Generate Payment Order ID)
        Route::post('advert/self/get-application-details-for-payment', 'applicationDetailsForPayment');          // 26 ( Application Details For Payments )
        // Route::post('advert/self/get-payment-details', 'getPaymentDetails');          // 19 ( Payments Details )
        Route::post('advert/self/payment-by-cash', 'paymentByCash');          // 27 ( Payment via Cash )
        Route::post('advert/self/entry-cheque-dd', 'entryChequeDd');          // 28 ( Entry Cheque or DD For Payments )
        Route::post('advert/self/clear-or-bounce-cheque', 'clearOrBounceCheque');          // 29 ( Clear Cheque or DD )
        Route::post('advert/self/verify-or-reject-doc', 'verifyOrRejectDoc');          // 30 ( Verify or Reject Document )
        Route::post('advert/self/back-to-citizen', 'backToCitizen');          // 31 ( Application Back to Citizen )
        Route::post('advert/self/list-btc-inbox', 'listBtcInbox');          // 32 ( list Back to citizen )
        // Route::post('advert/self/check-full-upload', 'checkFullUpload');          // 19 ( Application Details For Payments )
        Route::post('advert/self/reupload-document', 'reuploadDocument');          // 33 ( Reupload Rejected Document )
        Route::post('advert/self/search-by-name-or-mobile', 'searchByNameorMobile');          //34 ( Search application by name and mobile no )
        Route::post('advert/self/get-application-between-date', 'getApplicationBetweenDate');          //35 ( Get Application Between two date )
        Route::post('advert/self/get-application-financial-year-wise', 'getApplicationFinancialYearWise');          //36 ( Get Application Financial Year Wise )
        Route::post('advert/self/get-application-display-wise', 'getApplicationDisplayWise');          //37 ( Get Application Financial Year Wise )
        Route::post('advert/self/payment-collection', 'paymentCollection');          //38 ( Get Application Financial Year Wise )
    });

    /**
     * | Param Strings 
     * | Controller-02
     * | Status - Closed By Bikash on 24 Apr 2023
     */
    Route::controller(ParamController::class)->group(function () {
        Route::post('advert/crud/param-strings', 'paramStrings');                               // 01 ( Get Param String List)
        Route::post('advert/get-approval-letter', 'getApprovalLetter');                         // 02 ( Get All Approval Letter )
        Route::post('advert/crud/v1/list-document', 'listDocument');                           // 03 ( Applied Document List )
        // Route::post('crud/district-mstrs', 'districtMstrs');                                 // 04
        Route::post('advert/payment-success-failure', 'paymentSuccessFailure');                 // 05 ( Update Payment Success or Failure )
        Route::post('advert/dashboard', 'advertDashboard');                                     // 06 ( Advertisement Dashboard )
        Route::post('advert/search-by-name-or-mobile', 'searchByNameOrMobile');                 // 07 ( Search Application By Mobile or Name )
        Route::post('market/dashboard', 'marketDashboard');                                     // 08 ( Market Dashboard )
        Route::post('advert/get-payment-details', 'getPaymentDetails');                         // 09 ( Application Details For Payments )
        Route::post('send-whatsapp-notification', 'sendWhatsAppNotification');                  // 10 ( Application Details For Payments )
        Route::post('advert/application-reports', 'applicationReports');                        // 11 ( Application Reports )
        Route::post('advert/get-financial-year-master-data', 'getFinancialMasterData');         // 12 ( Get Financial Year For Search )
        Route::post('advert/advertisement-dashboard', 'advertisementDashboard');                // 13 ( Advertisement Dashboard )
    });

    /**
     * | Movable Vehicles 
     * | Controller-03
     * | Status - Closed By Bikash on 24 Apr 2023
     */
    Route::controller(VehicleAdvetController::class)->group(function () {
        Route::post('advert/vehicle/add-new', 'addNew');    // 01 ( Save Application )
        Route::post('advert/vehicle/get-application-details-for-renew', 'applicationDetailsForRenew');       // 02 ( Renew Application )
        Route::post('advert/vehicle/renewal-application', 'renewalApplication');       // 03 ( Renew Application )
        Route::post('advert/vehicle/list-inbox', 'listInbox');    // 04 ( Application Inbox Lists )
        Route::post('advert/vehicle/list-outbox', 'listOutbox');    // 05 ( Application Outbox Lists )
        Route::post('advert/vehicle/get-details-by-id', 'getDetailsById');  // 06 ( Get Application Details By Application ID )
        Route::post('advert/vehicle/list-applied-applications', 'listAppliedApplications');     // 07 ( Get Applied Applications List )
        Route::post('advert/vehicle/escalate-application', 'escalateApplication');  // 08 ( Escalate or De-escalate Application )
        Route::post('advert/vehicle/list-escalated', 'listEscalated');  // 09 ( Special Inbox Applications )
        Route::post('advert/vehicle/forward-next-level', 'forwardNextLevel');  // 10 ( Forward or Backward Application )
        Route::post('advert/vehicle/comment-application', 'commentApplication');  // 11 ( Independent Comment )
        Route::post('advert/vehicle/view-vehicle-documents', 'viewVehicleDocuments');  // 12 ( Get Uploaded Document By Application ID )
        Route::post('advert/vehicle/view-active-document', 'viewActiveDocument');  // 13 ( Get Uploaded Document By Advertisement ID )
        Route::post('advert/vehicle/view-documents-on-workflow', 'viewDocumentsOnWorkflow');  // 14 ( Get Uploaded Document By Application ID )
        Route::post('advert/vehicle/approved-or-reject', 'approvedOrReject');          // 15 ( Approve or Reject )
        Route::post('advert/vehicle/list-approved', 'listApproved');          // 16 ( Approved list for Citizen)
        Route::post('advert/vehicle/list-rejected', 'listRejected');          // 17 ( Rejected list for Citizen)
        Route::post('advert/vehicle/get-jsk-applications', 'getJSKApplications');          // 18 ( Get Applied Applications List By JSK )
        Route::post('advert/vehicle/list-jsk-approved-application', 'listjskApprovedApplication');          // 19 ( Approved list for JSK)
        Route::post('advert/vehicle/list-jsk-rejected-application', 'listJskRejectedApplication');          // 20 ( Rejected list for JSK)  
        Route::post('advert/vehicle/generate-payment-order-id', 'generatePaymentOrderId');          // 21 ( Generate Payment Order ID)
        Route::post('advert/vehicle/get-application-details-for-payment', 'getApplicationDetailsForPayment');          // 22 ( Application Details For Payments )
        // Route::post('advert/vehicle/get-payment-details', 'getPaymentDetails');          // 19 ( Application Details For Payments )
        Route::post('advert/vehicle/payment-by-cash', 'paymentByCash');          // 23 ( Payment Via Cash )
        Route::post('advert/vehicle/entry-cheque-dd', 'entryChequeDd');          // 24 ( Entry Cheque or DD For Payments )
        Route::post('advert/vehicle/clear-or-bounce-cheque', 'clearOrBounceCheque');          // 25 ( Clear or Bouns Cheque For Payments )
        Route::post('advert/vehicle/entry-zone', 'entryZone');          // 26 ( Entry Zone by Permitted Canidate )
        Route::post('advert/vehicle/verify-or-reject-doc', 'verifyOrRejectDoc');          // 27 ( Verify or Reject Document)
        Route::post('advert/vehicle/back-to-citizen', 'backToCitizen');          // 28 ( Application Back to citizen )
        Route::post('advert/vehicle/list-btc-inbox', 'listBtcInbox');          // 29 ( list Application Back to citizen )        
        // Route::post('advert/vehicle/check-full-upload', 'checkFullUpload');          // 19 ( Application Details For Payments )
        Route::post('advert/vehicle/reupload-document', 'reuploadDocument');          // 30 ( Reupload Rejected Document )
        Route::post('advert/vehicle/get-application-between-date', 'getApplicationBetweenDate');          //31 ( Get Application Between two date )
        Route::post('advert/vehicle/payment-collection', 'paymentCollection');          //32 ( Get Application Financial Year Wise )

    });

    /**
     * | Private Lands
     * | Controller-04 
     * | Status - Closed By Bikash on 24 Apr 2023
     */
    Route::controller(PrivateLandController::class)->group(function () {
        Route::post('advert/pvt-land/add-new', 'addNew'); // 01   ( Save Application )  
        Route::post('advert/pvt-land/get-application-details-for-renew', 'applicationDetailsForRenew');       // 02 ( Renew Application )
        Route::post('advert/pvt-land/renewal-application', 'renewalApplication');       // 03 ( Renew Application ) 
        Route::post('advert/pvt-land/list-inbox', 'listInbox');    // 04 ( Application Inbox Lists )
        Route::post('advert/pvt-land/list-outbox', 'listOutbox');    // 05 ( Application Outbox Lists )
        Route::post('advert/pvt-land/get-details-by-id', 'getDetailsById');  // 06 ( Get Application Details By Application ID )
        Route::post('advert/pvt-land/list-applied-applications', 'listAppliedApplications');     // 07 ( Get Applied Applications List )
        Route::post('advert/pvt-land/escalate-application', 'escalateApplication');  // 08 ( Escalate or De-escalate Application )
        Route::post('advert/pvt-land/list-escalated', 'listEscalated');  // 09 ( Special Inbox Applications )
        Route::post('advert/pvt-land/forward-next-level', 'forwardNextLevel');  // 10 ( Forward or Backward Application )
        Route::post('advert/pvt-land/comment-application', 'commentApplication');  // 11 ( Independent Comment )
        Route::post('advert/pvt-land/view-pvt-land-documents', 'viewPvtLandDocuments');  // 12 ( Get Uploaded Document By Application ID )
        Route::post('advert/pvt-land/view-active-document', 'viewActiveDocument');  // 13 ( Get Uploaded Document By Advertisement ID )
        Route::post('advert/pvt-land/view-documents-on-workflow', 'viewDocumentsOnWorkflow');  // 14 ( Get Uploaded Document By Application ID )
        Route::post('advert/pvt-land/approved-or-reject', 'approvedOrReject');          // 15 ( Approve or Reject )
        Route::post('advert/pvt-land/list-approved', 'listApproved');          // 16 ( Approved list for Citizen)
        Route::post('advert/pvt-land/list-rejected', 'listRejected');          // 17 ( Rejected list for Citizen)
        Route::post('advert/pvt-land/get-jsk-applications', 'getJSKApplications');          // 18 ( Get Applied Applications List By JSK )
        Route::post('advert/pvt-land/list-jsk-approved-application', 'listjskApprovedApplication');          // 19 ( Approved list for JSK)
        Route::post('advert/pvt-land/list-jsk-rejected-application', 'listJskRejectedApplication');          // 20 ( Rejected list for JSK)  
        Route::post('advert/pvt-land/generate-payment-order-id', 'generatePaymentOrderId');          // 21 ( Generate Payment Order ID)
        Route::post('advert/pvt-land/get-application-details-for-payment', 'getApplicationDetailsForPayment');          // 22 ( Application Details For Payments )
        Route::post('advert/pvt-land/payment-by-cash', 'paymentByCash');          // 23 ( Payment Via Cash )
        Route::post('advert/pvt-land/entry-cheque-dd', 'entryChequeDd');          // 24 ( Entry Check or DD for Payment )
        Route::post('advert/pvt-land/clear-or-bounce-cheque', 'clearOrBounceCheque');          // 25 ( Clear or Bouns Check )
        Route::post('advert/pvt-land/entry-zone', 'entryZone');          // 26 ( Zone Entry by permitted member )
        Route::post('advert/pvt-land/verify-or-reject-doc', 'verifyOrRejectDoc');          // 27 ( Verify or Reject Document )
        Route::post('advert/pvt-land/back-to-citizen', 'backToCitizen');          // 28 ( Application Back to Citizen )
        Route::post('advert/pvt-land/list-btc-inbox', 'listBtcInbox');          // 29 ( list BTC Inbox )
        Route::post('advert/pvt-land/reupload-document', 'reuploadDocument');          // 30 ( Reupload Rejected Documents )
        Route::post('advert/pvt-land/get-application-between-date', 'getApplicationBetweenDate');          //35 ( Get Application Between two date )
        Route::post('advert/pvt-land/get-application-display-wise', 'getApplicationDisplayWise');          //36 ( Get Application Financial Year Wise )
        Route::post('advert/pvt-land/payment-collection', 'paymentCollection');          //37 ( Get Application Financial Year Wise )
    });

    /**
     * | Agency 
     * | Controller-05 
     * | Status - Closed By Bikash on 24 Apr 2023
     */
    Route::controller(AgencyController::class)->group(function () {
        Route::post('advert/agency/add-new', 'addNew');             // 01   ( Save Application )
        Route::post('advert/agency/get-agency-details', 'getAgencyDetails');             // 02  ( Agency Details )
        Route::post('advert/agency/list-inbox', 'listInbox');             // 03 ( Application Inbox Lists )
        Route::post('advert/agency/list-outbox', 'listOutbox');    // 04 ( Application Outbox Lists )
        Route::post('advert/agency/get-details-by-id', 'getDetailsById');  // 05 ( Get Application Details By Application ID )
        Route::post('advert/agency/list-applied-applications', 'listAppliedApplications');     // 06 ( Get Applied Applications List )
        Route::post('advert/agency/escalate-application', 'escalateApplication');  // 07 ( Escalate or De-escalate Application )
        Route::post('advert/agency/list-escalated', 'listEscalated');  // 08 ( Special Inbox Applications )
        Route::post('advert/agency/forward-next-level', 'forwardNextLevel');  // 09 ( Forward or Backward Application )
        Route::post('advert/agency/comment-application', 'commentApplication');  // 10 ( Independent Comment )
        Route::post('advert/agency/view-agency-documents', 'viewAgencyDocuments');  // 11 ( Get Uploaded Document By Application ID )
        Route::post('advert/agency/view-active-document', 'viewActiveDocument');  // 12 ( Get Uploaded Document By Advertisement ID )
        Route::post('advert/agency/view-documents-on-workflow', 'viewDocumentsOnWorkflow');  // 13 ( Get Uploaded Document By Application ID )
        Route::post('advert/agency/approved-or-reject', 'approvedOrReject');          // 14 ( Approve or Reject )
        Route::post('advert/agency/list-approved', 'listApproved');          // 15 ( Approved list for Citizen)
        Route::post('advert/agency/list-rejected', 'listRejected');          // 16 ( Rejected list for Citizen)
        Route::post('advert/agency/get-jsk-applications', 'getJSKApplications');          // 17 ( Get Applied Applications List By JSK )
        Route::post('advert/agency/list-jsk-approved-application', 'listjskApprovedApplication');          // 18 ( Approved list for JSK)
        Route::post('advert/agency/list-jsk-rejected-application', 'listJskRejectedApplication');          // 19 ( Rejected list for JSK)  
        Route::post('advert/agency/generate-payment-order-id', 'generatePaymentOrderId');          // 20 ( Generate Payment Order ID)
        Route::post('advert/agency/get-application-details-for-payment', 'getApplicationDetailsForPayment');          // 21 ( Application Details For Payments )
        // Route::post('advert/agency/get-payment-details', 'getPaymentDetails');          // 19 ( Application Details For Payments )
        Route::post('advert/agency/renewal-agency', 'renewalAgency');          // 22 ( Application Details For Payments )
        Route::post('advert/agency/payment-by-cash', 'agencyPaymentByCash');          // 23 ( Application Details For Payments )
        Route::post('advert/agency/entry-cheque-dd', 'entryChequeDd');          // 24 ( Application Details For Payments )
        Route::post('advert/agency/clear-or-bounce-cheque', 'clearOrBounceCheque');          // 25 ( Application Details For Payments )
        Route::post('advert/agency/verify-or-reject-doc', 'verifyOrRejectDoc');          // 26 ( Application Details For Payments )
        Route::post('advert/agency/back-to-citizen', 'backToCitizen');          // 27 ( Application Details For Payments )
        Route::post('advert/agency/list-btc-inbox', 'listBtcInbox');          // 28 ( Application Details For Payments )
        // Route::post('advert/agency/check-full-upload', 'checkFullUpload');          // 19 ( Application Details For Payments )
        Route::post('advert/agency/reupload-document', 'reuploadDocument');          // 29 ( Application Details For Payments )
        Route::post('advert/agency/search-by-name-or-mobile', 'searchByNameorMobile');          //30 ( Search application by name and mobile no )
        Route::post('advert/agency/is-agency', 'isAgency'); // 31 (Get Agency Approve or not By Login Token)
        Route::post('advert/agency/get-agency-dashboard', 'getAgencyDashboard'); //32 (Get Agency Dashboard)
        Route::post('advert/agency/get-application-between-date', 'getApplicationBetweenDate');          //33 ( Get Application Between two date )
        Route::post('advert/agency/payment-collection', 'paymentCollection');          //34 ( Get Application Financial Year Wise )
        Route::post('advert/agency/is-email-available', 'isEmailAvailable');          //35 ( Check email is free for agency or not )
    });

    /**
     * | Hoarding 
     * | Controller-06 
     * | Status - Closed By Bikash on 24 Apr 2023
     */
    Route::controller(HoardingController::class)->group(function () {
        Route::post('advert/hording/get-hording-category', 'getHordingCategory');  // 01 ( Get Typology List )
        Route::post('advert/hording/list-typology', 'listTypology');  // 02 ( Get Typology List )
        Route::post('advert/hording/add-new', 'addNew');  // 03 ( Save Application For Licence )
        Route::post('advert/hording/list-inbox', 'listInbox');             // 04 ( Application Inbox Lists )
        Route::post('advert/hording/list-outbox', 'listOutbox');    // 05 ( Application Outbox Lists )
        Route::post('advert/hording/get-details-by-id', 'getDetailsById');  // 06 ( Get Application Details By Application ID )
        Route::post('advert/hording/list-applied-applications', 'listAppliedApplications');     // 07 ( Get Applied Applications List )
        Route::post('advert/hording/escalate-application', 'escalateApplication');  // 08 ( Escalate or De-escalate Application )
        Route::post('advert/hording/list-escalated', 'listEscalated');  // 09 ( Special Inbox Applications )
        Route::post('advert/hording/forward-next-level', 'forwardNextLevel');  // 10 ( Forward or Backward Application )
        Route::post('advert/hording/comment-application', 'commentApplication');  //11 ( Independent Comment )
        Route::post('advert/hording/view-hoarding-documents', 'viewHoardingDocuments');  // 12 ( Get Uploaded Document By Application ID )
        Route::post('advert/hording/view-active-document', 'viewActiveDocument');  // 13 ( Get Uploaded Document By Advertisement ID )
        Route::post('advert/hording/view-documents-on-workflow', 'viewDocumentsOnWorkflow');  // 14 ( Get Uploaded Document By Application ID )
        Route::post('advert/hording/approval-or-rejection', 'approvalOrRejection');          // 15 ( Approve or Reject )
        Route::post('advert/hording/list-approved', 'listApproved');          // 16 ( License Approved list for Citizen)
        Route::post('advert/hording/list-rejected', 'listRejected');          // 17 ( License Rejected list for Citizen)
        Route::post('advert/hording/list-unpaid', 'listUnpaid');          // 18 ( License Rejected list for Citizen)
        Route::post('advert/hording/get-jsk-applications', 'getJskApplications');          // 19 ( Get Applied Applications List By JSK )
        Route::post('advert/hording/list-jsk-approved-application', 'listJskApprovedApplication');          // 20 ( Approved list for JSK)
        Route::post('advert/hording/list-jsk-rejected-application', 'listJskRejectedApplication');          // 21 ( Rejected list for JSK)  
        Route::post('advert/hording/generate-payment-order-id', 'generatePaymentOrderId');          // 22 ( Generate Payment Order ID)
        Route::post('advert/hording/get-application-details-for-payment', 'getApplicationDetailsForPayment');          // 23 ( Application Details For Payments )
        Route::post('advert/hording/get-hording-details-for-renew', 'getHordingDetailsForRenew');          // 24 ( Application Details For Payments )
        Route::post('advert/hording/renewal-hording', 'renewalHording');          // 25 ( Application Details For Payments )
        Route::post('advert/hording/payment-by-cash', 'paymentByCash');          // 26 ( Application Details For Payments )
        Route::post('advert/hording/entry-cheque-dd', 'entryChequeDd');          // 27 ( Application Details For Payments )
        Route::post('advert/hording/clear-or-bounce-cheque', 'clearOrBounceCheque');          // 28 ( Application Details For Payments )
        Route::post('advert/hording/verify-or-reject-doc', 'verifyOrRejectDoc');          // 29 ( Application Details For Payments )
        Route::post('advert/hording/back-to-citizen', 'backToCitizen');          // 30 ( Application Details For Payments )
        Route::post('advert/hording/list-btc-inbox', 'listBtcInbox');          // 31 ( Application Details For Payments )
        Route::post('advert/hording/reupload-document', 'reuploadDocument');          // 32 ( Application Details For Payments )
        Route::post('advert/hording/get-renew-active-applications', 'getRenewActiveApplications'); // 33 (Get Agency Dashboard)

        Route::post('advert/hording/list-expired-hording', 'listExpiredHording'); // 34 (Get Expired Hording)
        Route::post('advert/hording/archived-hording', 'archivedHording'); // 35 (Archieves Hording)
        Route::post('advert/hording/list-hording-archived', 'listHordingArchived'); // 36 (list Expired Hording)
        Route::post('advert/hording/blacklist-hording', 'blacklistHording'); // 37 (Blacklist Hording)
        Route::post('advert/hording/list-hording-blacklist', 'listHordingBlacklist'); // 38 (list Blacklist Hording)
        Route::post('advert/hording/agency-dashboard-graph', 'agencyDashboardGraph'); // 39 (list Blacklist Hording)
        Route::post('advert/hording/get-application-between-date', 'getApplicationBetweenDate');          //40 ( Get Application Between two date )
        Route::post('advert/hording/get-application-financial-year-wise', 'getApplicationFinancialYearWise');          //41 ( Get Application Financial Year Wise )
        Route::post('advert/hording/payment-collection', 'paymentCollection');          //42 ( Get Application Financial Year Wise )
        Route::post('advert/hoarding/get-agency-dashboard', 'getAgencyDashboard'); //32 (Get Agency Dashboard)

    });

    /**
     * | Lodge Controller
     * | Controller-07
     * | By - Bikash Kumar
     * | Date 21-02-2023
     * | Status - Closed By Bikash on 24 Apr 2023
     */
    Route::controller(LodgeController::class)->group(function () {
        Route::post('market/lodge/add-new', 'addNew'); // 01   ( Save Application )  
        Route::post('market/lodge/list-inbox', 'listInbox');    // 02 ( Application Inbox Lists )
        Route::post('market/lodge/list-outbox', 'listOutbox');    // 03 ( Application Outbox Lists )
        Route::post('market/lodge/get-details-by-id', 'getDetailsById');  // 04 ( Get Application Details By Application ID )
        Route::post('market/lodge/list-applied-applications', 'listAppliedApplications');     // 05 ( Get Applied Applications List )
        Route::post('market/lodge/escalate-application', 'escalateApplication');  // 06 ( Escalate or De-escalate Application )
        Route::post('market/lodge/list-escalated', 'listEscalated');  // 07 ( Special Inbox Applications )
        Route::post('market/lodge/forward-next-level', 'forwardNextLevel');  // 08 ( Forward or Backward Application )
        Route::post('market/lodge/comment-application', 'commentApplication');  // 09 ( Independent Comment )
        Route::post('market/lodge/view-lodge-documents', 'viewLodgeDocuments');  // 10 ( Get Uploaded Document By Application ID )
        Route::post('market/lodge/view-active-document', 'viewActiveDocument');  // 11 ( Get Uploaded Document By Advertisement ID )
        Route::post('market/lodge/view-documents-on-workflow', 'viewDocumentsOnWorkflow');  // 12 ( Get Uploaded Document By Application ID )
        Route::post('market/lodge/approved-or-reject', 'approvedOrReject');          // 13 ( Approve or Reject )
        Route::post('market/lodge/list-approved', 'listApproved');          // 14 ( Approved list for Citizen)
        Route::post('market/lodge/list-rejected', 'listRejected');          // 15 ( Rejected list for Citizen)
        Route::post('market/lodge/generate-payment-order-id', 'generatePaymentOrderId');          // 16 ( Generate Payment Order ID)
        Route::post('market/lodge/get-application-details-for-payment', 'getApplicationDetailsForPayment');          // 17 ( Application Details For Payments )
        // Route::post('market/lodge/get-payment-details', 'getApplicationDetailsForPayment');          // 18 ( Application Details For Payments )
        Route::post('market/lodge/verify-or-reject-doc', 'verifyOrRejectDoc');          // 18 ( Application Details For Payments )
        Route::post('market/lodge/back-to-citizen', 'backToCitizen');          // 19 ( Application Details For Payments )
        Route::post('market/lodge/list-btc-inbox', 'listBtcInbox');          // 20 ( Application Details For Payments )
        // Route::post('market/lodge/check-full-upload', 'checkFullUpload');          // 19 ( Application Details For Payments )
        Route::post('market/lodge/reupload-document', 'reuploadDocument');          // 21 ( Application Details For Payments )
        Route::post('market/lodge/payment-by-cash', 'paymentByCash');          // 22 ( Application Details For Payments )
        Route::post('market/lodge/entry-cheque-dd', 'entryChequeDd');          // 23 ( Application Details For Payments )
        Route::post('market/lodge/clear-or-bounce-cheque', 'clearOrBounceCheque');          // 24 ( Application Details For Payments )

        Route::post('market/lodge/get-renew-application-details', 'getApplicationDetailsForRenew');          // 25 ( Application Details For Payments )
        Route::post('market/lodge/renew-application', 'renewApplication');          // 26 ( Application Details For Payments )
        Route::post('market/lodge/get-application-details-for-edit', 'getApplicationDetailsForEdit');          // 27 ( View Application Details For Edit )
        Route::post('market/lodge/edit-application', 'editApplication');          // 28 ( Edit Applications ) 

        Route::post('market/lodge/get-application-between-date', 'getApplicationBetweenDate');          //29 ( Get Application Between two date )
        Route::post('market/lodge/get-application-financial-year-wise', 'getApplicationFinancialYearWise');          //30 ( Get Application Financial Year Wise )
        Route::post('market/lodge/payment-collection', 'paymentCollection');          //31 ( Get Application Financial Year Wise )

        Route::post('market/lodge/rule-wise-applications', 'ruleWiseApplications');          //32 ( Get Application Rule Wise )
        Route::post('market/lodge/get-application-by-lodge-type', 'getApplicationByLodgelType');          //33 ( Get Application hostel type Wise )
    });

    /**
     * | Banquet Marriage Hall Controller
     * | Controller-08
     * | By - Bikash Kumar
     * | Date 18-02-2023
     * | Status - Closed By Bikash on 24 Apr 2023
     */
    Route::controller(BanquetMarriageHallController::class)->group(function () {
        Route::post('market/bm-hall/add-new', 'addNew'); // 01   ( Save Application )  
        Route::post('market/bm-hall/list-inbox', 'listInbox');    // 02 ( Application Inbox Lists )
        Route::post('market/bm-hall/list-outbox', 'listOutbox');    // 03 ( Application Outbox Lists )
        Route::post('market/bm-hall/get-details-by-id', 'getDetailsById');  // 04 ( Get Application Details By Application ID )
        Route::post('market/bm-hall/list-applied-applications', 'listAppliedApplications');     // 05 ( Get Applied Applications List )
        Route::post('market/bm-hall/escalate-application', 'escalateApplication');  // 06 ( Escalate or De-escalate Application )
        Route::post('market/bm-hall/list-escalated', 'listEscalated');  // 07 ( Special Inbox Applications )
        Route::post('market/bm-hall/forward-next-level', 'forwardNextLevel');  // 08 ( Forward or Backward Application )
        Route::post('market/bm-hall/comment-application', 'commentApplication');  // 09 ( Independent Comment )
        Route::post('market/bm-hall/view-bm-hall-documents', 'viewBmHallDocuments');  // 10 ( Get Uploaded Document By Application ID )
        Route::post('market/bm-hall/view-active-document', 'viewActiveDocument');  // 11 ( Get Uploaded Document By Advertisement ID )
        Route::post('market/bm-hall/view-documents-on-workflow', 'viewDocumentsOnWorkflow');  // 12 ( Get Uploaded Document By Application ID )
        Route::post('market/bm-hall/approved-or-reject', 'approvedOrReject');          // 13 ( Approve or Reject )
        Route::post('market/bm-hall/list-approved', 'listApproved');          // 14 ( Approved list for Citizen)
        Route::post('market/bm-hall/list-rejected', 'listRejected');          // 15 ( Rejected list for Citizen)
        Route::post('market/bm-hall/generate-payment-order-id', 'generatePaymentOrderId');          // 16 ( Generate Payment Order ID)
        Route::post('market/bm-hall/get-application-details-for-payment', 'getApplicationDetailsForPayment');          // 17 ( Application Details For Payments )
        // Route::post('market/bm-hall/get-payment-details', 'getApplicationDetailsForPayment');          // 18 ( Application Details For Payments )
        Route::post('market/bm-hall/verify-or-reject-doc', 'verifyOrRejectDoc');          // 18 ( Verify or Reject Documents )
        Route::post('market/bm-hall/back-to-citizen', 'backToCitizen');          // 19 ( Application Back to Citizen )
        Route::post('market/bm-hall/list-btc-inbox', 'listBtcInbox');          // 20 ( List Application Back to Citizen )
        // Route::post('market/bm-hall/check-full-upload', 'checkFullUpload');          // 21 ( Application Details For Payments )
        Route::post('market/bm-hall/reupload-document', 'reuploadDocument');          // 21 ( Reupload Document for Pending Documents)
        Route::post('market/bm-hall/payment-by-cash', 'paymentByCash');          // 22 ( Cash Payments )
        Route::post('market/bm-hall/entry-cheque-dd', 'entryChequeDd');          // 23 ( Entry Cheque or DD For Payments )
        Route::post('market/bm-hall/clear-or-bounce-cheque', 'clearOrBounceCheque');          // 24 (Clear or Bouns Cheque For Payments )

        Route::post('market/bm-hall/get-renew-application-details', 'getApplicationDetailsForRenew');          // 25 ( Get Application Details For Renew )
        Route::post('market/bm-hall/renew-application', 'renewApplication');          // 26 ( Renew Applications )
        Route::post('market/bm-hall/get-application-details-for-edit', 'getApplicationDetailsForEdit');          // 27 ( View Application Details For Edit )
        Route::post('market/bm-hall/edit-application', 'editApplication');          // 28 ( Edit Applications )

        Route::post('market/bm-hall/get-application-between-date', 'getApplicationBetweenDate');          //29 ( Get Application Between two date )
        Route::post('market/bm-hall/get-application-financial-year-wise', 'getApplicationFinancialYearWise');          //30 ( Get Application Financial Year Wise )
        Route::post('market/bm-hall/payment-collection', 'paymentCollection');          //31 ( Get Application Financial Year Wise )

        Route::post('market/bm-hall/rule-wise-applications', 'ruleWiseApplications');          //32 ( Get Application Rule Wise )
        Route::post('market/bm-hall/get-application-by-hall-type', 'getApplicationByHallType');          //32 ( Get Application Rule Wise )
        Route::post('market/bm-hall/get-application-by-organization-type', 'getApplicationByOrganizationType');          //33 ( Get Application organization type Wise )
    });

    /**
     * | Hostel Controller
     * | Controller-09
     * | By - Bikash Kumar
     * | Date 20-02-2023
     * | Status - Closed By Bikash on 24 Apr 2023
     */
    Route::controller(HostelController::class)->group(function () {
        Route::post('market/hostel/add-new', 'addNew'); // 01   ( Save Application )  
        Route::post('market/hostel/list-inbox', 'listInbox');    // 02 ( Application Inbox Lists )
        Route::post('market/hostel/list-outbox', 'listOutbox');    // 03 ( Application Outbox Lists )
        Route::post('market/hostel/get-details-by-id', 'getDetailsById');  // 04 ( Get Application Details By Application ID )
        Route::post('market/hostel/list-applied-applications', 'listAppliedApplications');     // 05 ( Get Applied Applications List )
        Route::post('market/hostel/escalate-application', 'escalateApplication');  // 06 ( Escalate or De-escalate Application )
        Route::post('market/hostel/list-escalated', 'listEscalated');  // 07 ( Special Inbox Applications )
        Route::post('market/hostel/forward-next-level', 'forwardNextLevel');  // 08 ( Forward or Backward Application )
        Route::post('market/hostel/comment-application', 'commentApplication');  // 09 ( Independent Comment )
        Route::post('market/hostel/view-hostel-documents', 'viewHostelDocuments');  // 10 ( Get Uploaded Document By Application ID )
        Route::post('market/hostel/view-active-document', 'viewActiveDocument');  // 11 ( Get Uploaded Document By Advertisement ID )
        Route::post('market/hostel/view-documents-on-workflow', 'viewDocumentsOnWorkflow');  // 12 ( Get Uploaded Document By Application ID )
        Route::post('market/hostel/approved-or-reject', 'approvedOrReject');          // 13 ( Approve or Reject )
        Route::post('market/hostel/list-approved', 'listApproved');          // 14 ( Approved list for Citizen)
        Route::post('market/hostel/list-rejected', 'listRejected');          // 15 ( Rejected list for Citizen)
        Route::post('market/hostel/generate-payment-order-id', 'generatePaymentOrderId');          // 16 ( Generate Payment Order ID)
        Route::post('market/hostel/get-application-details-for-payment', 'getApplicationDetailsForPayment');          // 17 ( Application Details For Payments )
        // Route::post('market/hostel/get-payment-details', 'getPaymentDetails');          // 18 ( Application Details For Payments )
        Route::post('market/hostel/verify-or-reject-doc', 'verifyOrRejectDoc');          // 18 ( Application Details For Payments )
        Route::post('market/hostel/back-to-citizen', 'backToCitizen');          // 19 ( Application Details For Payments )
        Route::post('market/hostel/list-btc-inbox', 'listBtcInbox');          // 20 ( Application Details For Payments )
        // Route::post('market/hostel/check-full-upload', 'checkFullUpload');          // 21 ( Application Details For Payments )
        Route::post('market/hostel/reupload-document', 'reuploadDocument');          // 21 ( Application Details For Payments )
        Route::post('market/hostel/payment-by-cash', 'paymentByCash');          // 22 ( Application Details For Payments )
        Route::post('market/hostel/entry-cheque-dd', 'entryChequeDd');          // 23 ( Application Details For Payments )
        Route::post('market/hostel/clear-or-bounce-cheque', 'clearOrBounceCheque');          // 24 ( Application Details For Payments )

        Route::post('market/hostel/get-renew-application-details', 'getApplicationDetailsForRenew');          // 25 ( Application Details For Payments )
        Route::post('market/hostel/renew-application', 'renewApplication');          // 26 ( Application Details For Payments )
        Route::post('market/hostel/get-application-details-for-edit', 'getApplicationDetailsForEdit');          // 27 ( View Application Details For Edit )
        Route::post('market/hostel/edit-application', 'editApplication');          // 28 ( Edit Applications )

        Route::post('market/hostel/get-application-between-date', 'getApplicationBetweenDate');          //29 ( Get Application Between two date )
        Route::post('market/hostel/get-application-financial-year-wise', 'getApplicationFinancialYearWise');          //30 ( Get Application Financial Year Wise )
        Route::post('market/hostel/payment-collection', 'paymentCollection');          //31 ( Get Application Financial Year Wise )


        Route::post('market/hostel/rule-wise-applications', 'ruleWiseApplications');          //32 ( Get Application Rule Wise )
        Route::post('market/hostel/get-application-by-hostel-type', 'getApplicationByHostelType');          //33 ( Get Application Hostel type Wise )
    });

    /**
     * | Dharamshala Controller
     * | Controller-10
     * | By - Bikash Kumar
     * | Date 21-02-2023
     * | Status - Closed By Bikash on 24 Apr 2023
     */
    Route::controller(DharamshalaController::class)->group(function () {
        Route::post('market/dharamshala/add-new', 'addNew'); // 01   ( Save Application )  
        Route::post('market/dharamshala/list-inbox', 'listInbox');    // 02 ( Application Inbox Lists )
        Route::post('market/dharamshala/list-outbox', 'listOutbox');    // 03 ( Application Outbox Lists )
        Route::post('market/dharamshala/get-details-by-id', 'getDetailsById');  // 04 ( Get Application Details By Application ID )
        Route::post('market/dharamshala/list-applied-applications', 'listAppliedApplications');     // 05 ( Get Applied Applications List )
        Route::post('market/dharamshala/escalate-application', 'escalateApplication');  // 06 ( Escalate or De-escalate Application )
        Route::post('market/dharamshala/list-escalated', 'listEscalated');  // 07 ( Special Inbox Applications )
        Route::post('market/dharamshala/forward-next-level', 'forwardNextLevel');  // 08 ( Forward or Backward Application )
        Route::post('market/dharamshala/comment-application', 'commentApplication');  // 09 ( Independent Comment )
        Route::post('market/dharamshala/view-dharamshala-documents', 'viewDharamshalaDocuments');  // 10 ( Get Uploaded Document By Application ID )
        Route::post('market/dharamshala/view-active-document', 'viewActiveDocument');  // 11 ( Get Uploaded Document By Advertisement ID )
        Route::post('market/dharamshala/view-documents-on-workflow', 'viewDocumentsOnWorkflow');  // 12 ( Get Uploaded Document By Application ID )
        Route::post('market/dharamshala/approved-or-reject', 'approvedOrReject');          // 13 ( Approve or Reject )
        Route::post('market/dharamshala/list-approved', 'listApproved');          // 14 ( Approved list for Citizen)
        Route::post('market/dharamshala/list-rejected', 'listRejected');          // 15 ( Rejected list for Citizen)
        Route::post('market/dharamshala/generate-payment-order-id', 'generatePaymentOrderId');          // 16 ( Generate Payment Order ID)
        Route::post('market/dharamshala/get-application-details-for-payment', 'getApplicationDetailsForPayment');          // 17 ( Application Details For Payments )
        // Route::post('market/dharamshala/get-payment-details', 'getPaymentDetails');          // 18 ( Application Details For Payments )
        Route::post('market/dharamshala/verify-or-reject-doc', 'verifyOrRejectDoc');          // 18 ( Verify or Reject Documents )
        Route::post('market/dharamshala/back-to-citizen', 'backToCitizen');          // 19 ( Application Back to Citizen )
        Route::post('market/dharamshala/list-btc-inbox', 'listBtcInbox');          // 20 ( List Application Back to Citizen )
        // Route::post('market/dharamshala/check-full-upload', 'checkFullUpload');          // 19 ( Application Details For Payments )
        Route::post('market/dharamshala/reupload-document', 'reuploadDocument');          // 21 ( Reupload Documents For Pending Documents )
        Route::post('market/dharamshala/payment-by-cash', 'paymentByCash');          // 22 ( Payment via Cash )
        Route::post('market/dharamshala/entry-cheque-dd', 'entryChequeDd');          // 23 ( Entry Cheque or DD For Payments )
        Route::post('market/dharamshala/clear-or-bounce-cheque', 'clearOrBounceCheque');          // 24 (Clear or Bouns Cheque For Payments )

        Route::post('market/dharamshala/get-renew-application-details', 'getApplicationDetailsForRenew');          // 25 ( Application Details For Renew )
        Route::post('market/dharamshala/renew-application', 'renewApplication');          // 26 ( Renew Application )
        Route::post('market/dharamshala/get-application-details-for-edit', 'getApplicationDetailsForEdit');          // 27 ( View Application Details For Edit )
        Route::post('market/dharamshala/edit-application', 'editApplication');          // 28 ( Edit Applications )

        Route::post('market/dharamshala/get-application-between-date', 'getApplicationBetweenDate');          //29 ( Get Application Between two date )
        Route::post('market/dharamshala/get-application-financial-year-wise', 'getApplicationFinancialYearWise');          //30 ( Get Application Financial Year Wise )
        Route::post('market/dharamshala/payment-collection', 'paymentCollection');          //31 ( Get Application Financial Year Wise )

        Route::post('market/dharamshala/rule-wise-applications', 'ruleWiseApplications');          //32 ( Get Application Rule Wise ).
        Route::post('market/dharamshala/get-application-by-organization-type', 'getApplicationByOrganizationType');          //32 ( Get Application Organization type Wise )
    });

    /**
     * | Bandobastee Controller
     * | Controller-11
     * | Created By - Bikash Kumar
     * | Date - 26-04-2023
     * | Status - Open
     */
    Route::controller(BandobasteeController::class)->group(function () {
        Route::post('market/bandobastee/bandobastee-master', 'bandobasteeMaster');                          // 01   ( Get Stand Category )  
        Route::post('market/bandobastee/get-stand-category', 'getStandCategory');                           // 02   ( Get Stand Category )  
        Route::post('market/bandobastee/get-stands', 'getStands');                                          // 03   ( Get Stand and Category wise ULB )  
        Route::post('market/bandobastee/add-new', 'addNew');                                                // 04   ( Save Application )  
        Route::post('market/bandobastee/list-penalty', 'listPenalty');                                      // 05   ( Get Panalty List ) 
        Route::post('market/bandobastee/list-settler', 'listSettler');                                      // 06   ( Get Stand Settler List )   
        Route::post('market/bandobastee/installment-payment', 'installmentPayment');                        // 07   ( Installment Payment )  
        Route::post('market/bandobastee/list-installment-payment', 'listInstallmentPayment');               // 08   ( Installment Payment List )  
        Route::post('market/bandobastee/get-bandobastee-category', 'getBandobasteeCategory');                              // 09   ( Bandobastee List ) 
        Route::post('market/bandobastee/add-penalty-or-performance-security', 'addPenaltyOrPerformanceSecurity');       // 10   ( Add Penalty or Performance Security Money List )  
        Route::post('market/bandobastee/list-settler-transaction', 'listSettlerTransaction');                // 11   ( Transaction List ) 
        /* ===================== Parking Api ========================================= */
        Route::post('market/bandobastee/list-parking', 'listParking');                                        // 12   ( Parking List )
        Route::post('market/bandobastee/list-parking-settler', 'listParkingSettler');                          // 13   ( Parking Settler List )
        /* ===================== Bazar Api ========================================= */
        Route::post('market/bandobastee/list-bazar', 'listBazar');                                             // 14   ( Bazar List )
        Route::post('market/bandobastee/list-bazar-settler', 'listBazarSettler');                              // 15   ( Bazar Settler List )
        /* ===================== Banquet Hall Api ========================================= */
        Route::post('market/bandobastee/list-banquet-hall', 'listBanquetHall');                                 // 16   ( Banquet Hall List )
        Route::post('market/bandobastee/list-banquet-hall-settler', 'listBanquetHallSettler');                  // 17   ( BanquetHall Settler List )

    });


    /**
     * | Search Controller
     * | Controller-12
     * | Created By - Bikash Kumar
     * | Date - 07 Aug 2023
     * | Status - Open
     */
    Route::controller(SearchController::class)->group(function () {
        Route::post('advert/search/list-all-advertisement-records', 'listAllAdvertisementRecords');                              // 01   ( All Advertisement records List  of citizen )
        Route::post('advert/search/list-all-market-records', 'listAllMarketRecords');                                     // 02   ( All Market records List  of citizen )
    });
});