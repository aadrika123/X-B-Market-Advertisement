<?php

/**
 * | Created On-14-06-2023 
 * | Author-Anshu Kumar
 * | Change By - Bikash Kumar
 * | Created for the Shop and tolls collections routes
 */

use App\Http\Controllers\Master\CircleController;
use App\Http\Controllers\Master\MarketController;
use App\Http\Controllers\Rentals\ShopController;
use App\Http\Controllers\Rentals\TollsController;
use Illuminate\Support\Facades\Route;


Route::controller(ShopController::class)->group(function () {
    Route::get('rental/shop-payment-reciept/{tranId}', 'shopPaymentReciept');                                   // 22 Get Shop Payment Receipt
    Route::post('rental/update-webhook-data', 'updateWebhookData');                                             // 23 Update webhook Data After Payment is Success
    Route::get('rental/get-shop-demand-reciept/{shopId}/{fyYear}', 'getShopDemandReciept');                     // 35 Get Shop Demand Reciept
    Route::get('rental/get-shop-details/{shopId}', 'getShopDetails');                                           // 39 Get Shop Details
    Route::get('rental/get-payment-amount-of-shop/{shopId}/{fyYear}', 'getPaymentAmountofShop');                // 40 Calculate Shop Amount According to Financial Year Wise
    Route::get('rental/get-generate-referal-url-payment/{shopId}/{fyYear}', 'getGenerateReferalUrlForPayment'); // 41 Generate Refferal Url for Without Login Payment
    Route::get('rental/get-search-shop-by-mobile-no/{mobileNo}', 'getsearchShopByMobileNo');                    // 42 Search Shop By Mobile No Without Login
    Route::post('rental/get-search-shop-by-mobile-no-name-shop', 'getsearchShopByMobileNoNameShopNo');          // 54 Search Shop By Mobile No, Name, or Shop No Without Login
    Route::post('rental/update-from-pinelab-data', 'updateFromPinelabData');                                    // 55 Search Shop By Mobile No, Name, or Shop No Without Login
    Route::post('rental/sendSms', 'sendSms');


});

// Route::controller(TollsController::class)->group(function () {
//     Route::get('rental/get-toll-payment-reciept', 'getPaymentReciept');
// });
Route::group(['middleware' => ['checkToken']], function () {
    /**
     * | Shops (50)
     * | Created By - Bikash Kumar
     * | Status - Closed (27 sep 2023)
     */
    Route::controller(ShopController::class)->group(function () {
        Route::post('shop-payments', 'shopPayment');                                                            // 01  Shop Payments
        Route::post('crud/shop/store', 'store');                                                                // 02  Store Shop Data
        Route::post('crud/shop/edit', 'edit');                                                                  // 03  Edit Shop Details
        Route::post('crud/shop/show-by-id', 'show');                                                            // 04  Get Shop Details By Id 
        Route::post('crud/shop/delete', 'delete');                                                              // 05  Active or De-Active Shop
        Route::post('rental/list-ulb-wise-circle', 'listUlbWiseCircle');                                        // 06  List All Circle ULB wise ( Circle i.e. Zone )
        Route::post('rental/list-circle-wise-market', 'listCircleWiseMarket');                                  // 07  List Circle Wise Market
        Route::post('rental/list-shop', 'listShop');                                                            // 08  List All Shop
        Route::post('rental/get-shop-collection-summary', 'getShopCollectionSummary');                          // 09  Get List of Shop Collection
        Route::post('rental/get-tc-collection', 'getTcCollection');                                             // 10  Get TC Collection
        Route::post('rental/shop-master', 'shopMaster');                                                        // 11  Get Shop Master Data
        Route::post('rental/search-shop-for-payment', 'searchShopForPayment');                                  // 12  Search Shop Data For Payment
        Route::post('rental/calculate-shop-rate-financial-wise', 'calculateShopRateFinancialwise');             // 13  Calculate Shop Amount According to Financial Year Wise
        Route::post('rental/entry-check-or-dd', 'entryCheckOrDD');                                              // 14  Entry Cheque or DD Details
        Route::post('rental/list-uncleared-check-dd', 'listEntryCheckorDD');                                    // 15  List Entry Cheque/DD Details Data  
        Route::post('rental/clear-bounce-cheque-or-dd', 'clearOrBounceChequeOrDD');                             // 16  Update Data After Cheque is clear or bounce 
        Route::post('rental/list-shop-collection', 'listShopCollection');                                       // 17  List Shop Collection 
        Route::post('rental/edit-shop-data', 'editShopData');                                                   // 18  Edit Shop Details Data
        Route::post('rental/dcb-reports', 'dcbReports');                                                        // 19  List DCB Reports 
        Route::post('rental/shop-wise-dcb', 'shopWiseDcb');                                                     // 20  List Shop wise DCB Reports    
        Route::post('rental/generate-referal-url-for-payment', 'generateReferalUrlForPayment');                 // 21  Generate Referal Url For Payment 
        // Route::post('rental/list-unverified-cash-payment', 'listUnverifiedCashPayment');                        // 24  List of UnVerified Cash Payment    
        Route::post('rental/verified-cash-payment', 'verifiedCashPayment');                                     // 25  Verified Cash Payment    
        Route::post('rental/list-cash-verification', 'listCashVerification');                                   // 26  List Cash Verification  
        Route::post('rental/list-details-cash-verification', 'listDetailCashVerification');                     // 27  List Details Cash Verification User wise 
        Route::post('rental/update-cheque-detail', 'updateChequeDeails');                                       // 28  Update cheque details       
        Route::post('rental/shop-details-for-edit', 'shopDetailsForEdit');                                      // 29  Shop Details For Edit      
        Route::post('rental/generate-shop-demand', 'generateShopDemand');                                       // 30  Generate Shop Demand   
        Route::post('rental/get-shop-collection-tc-wise', 'getShopCollectionTcWise');                           // 31  Get Shop Collection TC Wise 
        Route::post('rental/get-shop-collection-by-tc-id', 'getShopCollectionByTcId');                          // 32  Get Shop Collection TC Wise   
        Route::post('rental/shop-payment-reciept-bt-print', 'shopPaymentRecieptBluetoothPrint');                // 33  Get Shop Payment Receipt For Bluetooth Printer
        Route::post('rental/search-shop-by-mobile-no', 'searchShopByMobileNo');                                 // 34  Search Shop By Mobile No
        Route::post('rental/shop-report-by-payment-mode-summary', 'shopReportSummaryByPaymentMode');            // 36  Shop Report Summary by payment mode wise
        Route::post('rental/shop-collection-summary', 'shopCollectionSummary');                                 // 37  Shop Collection Summary
        Route::post('rental/dcb-reports-arrear-current', 'dcbReportsArrearCurrent');                            // 38  DCB Reports Arrear Current 
        Route::post('rental/search-transaction-by-transaction-no', 'searchTransactionByTransactionNo');         // 43  Search Transaction By Transaction No
        Route::post('rental/transaction-deactivation', 'transactionDeactivation');                              // 44  Deactive Transaction
        Route::post('rental/list-deactivation-transaction', 'listDeactiveTransaction');                         // 45  List Deactive Transaction
        Route::post('rental/bulk-demand-reciept', 'bulkDemandReciept');                                         // 46  Bulk Demand Receipt
        Route::post('rental/tc-wise-collection-details', 'tcwisecollectionDetails');                            // 47  Tc Wise Collection  
        Route::post('rental/tc-wise-collection', 'tcwisecollection');                                           // 48  Tc Wise Collection Details
        Route::post('rental/bulk-payment-reciept', 'bulkPaymentReciept');                                       // 50  Bulk Payment Reciept
        Route::post('rental/search-demand-for-update', 'searchDemandForUpdate');                                // 51  Search Demand For Update
        Route::post('rental/update-shop-demand', 'UpdateShopDemand');                                           // 52  Update Shop Demand
        Route::post('rental/balance-sheet-financial-year-wise', 'dcbFinancialYearWise');                        // 53  DCB Financial Year Wise
    });

    /**
     * | Tolls(51)
     */
    Route::controller(TollsController::class)->group(function () {
        Route::post('toll-payments', 'tollPayments');                                                           // 01 ( Toll Payments )
        Route::post('crud/toll/insert', 'store');                                                               // 02 ( Add Toll )
        Route::post('crud/toll/list-toll', 'listToll');                                                         // 03 ( Get List Toll )
        Route::post('crud/toll/edit', 'edit');                                                                  // 04 ( Edit Toll ) 
        Route::post('crud/toll/show-by-id', 'show');                                                            // 05 ( Get Toll Deails By Id )
        Route::post('crud/toll/retrieve-all', 'retrieve');                                                      // 06 ( Get All Toll active and Deactive )
        Route::post('crud/toll/retrieve-all-active', 'retrieveActive');                                         // 07 ( Get All Active Toll )
        Route::post('crud/toll/delete', 'delete');                                                              // 08 ( Toll Active or Deactive )    
        Route::post('rental/get-toll-collection-summary', 'gettollCollectionSummary');                          // 09 ( Get List of Toll COllection Summery )                           
        Route::post('rental/list-toll-by-market-id', 'listTollByMarketId');                                     // 10 ( Get list toll according to given market Id )    
        Route::post('rental/search-toll-by-name-or-mobile', 'searchTollByNameOrMobile');                        // 11 ( Search Toll by Mobile or Name )
        Route::post('rental/get-toll-detail-by-id', 'getTollDetailtId');                                        // 12 ( Get Toll Details By Id )
        Route::post('rental/toll-payment-by-admin', 'tollPaymentByAdmin');                                      // 13 ( Toll Payment By Admin )
        Route::post('rental/get-toll-price-list', 'getTollPriceList');                                          // 14 ( Get TOll Price List )
        Route::post('rental/get-toll-payment-reciept', 'getPaymentReciept');                                    // 15 ( Get Toll Payment Reciept )   
        Route::post('rental/search-toll', 'searchToll');                                                        // 16 ( Search Toll by Mobile or Name )
        Route::post('rental/calculate-toll-price', 'calculateTollPrice');                                       // 17 ( Calculate Toll Price )
        Route::post('rental/tc-wise-toll-collection', 'getTCWiseTollCollectionSummary');                        // 18 ( TC Wise Toll COllection Summary )
        Route::post('rental/get-all-tc-wise-collection-reports', 'getAllTcWiseCollectionReports');              // 19 ( Collection All TC wise Collection Reports )
        Route::post('rental/get-circle-market-date-wise-report', 'getCircleMarketDateWiseReports');             // 20 ( Get Date,Market,circle,Date Wise Reports )
        Route::post('rental/generate-referal-url-for-toll-payment', 'generateReferalUrlForTollPayment');        // 21 ( Generate Referal Url For Payment ) 
    });


    /**
     * |Circle(52)
     */

    /**
     * | Created On-16-06-2023 
     * | Author - Ashutosh Kumar
     * | Change By - Bikash Kumar
     * | Status - Closed By Bikash Kumar on 03 Oct 2023
     */
    Route::controller(CircleController::class)->group(function () {
        Route::post('v1/crud/circle/insert', 'store');                                                          // 01  Add Circle 
        Route::post('v1/crud/circle/update', 'edit');                                                           // 02  Update Circle Name 
        Route::post('v1/crud/circle/list-circle-by-ulbId', 'getCircleByUlb');                                   // 03  Get Circle List By Ulb ID
        Route::post('v1/crud/circle/list-all-circle', 'retireveAll');                                           // 04  Get All Circle List
        Route::post('v1/crud/circle/delete', 'delete');                                                         // 05  Delete Circle
    });

    /**
     * | Market(53)
     * | Author - Ashutosh Kumar
     * | Change By - Bikash Kumar
     * | Status - Closed By Bikash Kumar on 03 Oct 2023
     */
    Route::controller(MarketController::class)->group(function () {
        Route::post('v1/crud/market/insert', 'store');                                                          // 01  Add Market
        Route::post('v1/crud/market/update', 'edit');                                                           // 02  Update Market
        Route::post('v1/crud/market/list-market-by-circleId', 'getMarketByCircleId');                           // 03  List Market By Circle Id
        Route::post('v1/crud/market/list-all-market', 'retireveAll');                                           // 04  List All Market
        Route::post('v1/crud/market/delete', 'delete');                                                         // 05  Delete Market
        Route::post('rental/list-construction', 'listConstruction');                                            // 06  List Construction

    });
});
