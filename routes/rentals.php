<?php

/**
 * | Created On-14-06-2023 
 * | Author-Anshu Kumar
 * | Created for the Shop and tolls collections routes
 */

use App\Http\Controllers\Master\CircleController;
use App\Http\Controllers\Master\MarketController;
use App\Http\Controllers\Rentals\ShopController;
use App\Http\Controllers\Rentals\TollsController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['checkToken']], function () {
    /**
     * | Shops (50)
     */
    Route::controller(ShopController::class)->group(function () {
        Route::post('shop-payments', 'shopPayment');                               // 01
        Route::post('crud/shop/store', 'store');                                   // 02
        Route::post('crud/shop/edit', 'edit');                                     // 03
        Route::post('crud/shop/show-by-id', 'show');                               // 04
        Route::post('crud/shop/retrieve-all', 'retrieve');                         // 05
        Route::post('crud/shop/retrieve-all-active', 'retrieveAllActive');         // 06
        Route::post('crud/shop/delete', 'delete');
        Route::post('rental/list-ulb-wise-circle', 'listUlbWiseCircle');
        Route::post('rental/list-circle-wise-market', 'listCircleWiseMarket');
        Route::post('rental/list-shop-by-market-id', 'listShopByMarketId');
        Route::post('rental/list-shop', 'listShop');
        Route::post('rental/get-shop-detail-by-id', 'getShopDetailById');
        Route::post('rental/get-shop-collection-summary', 'getShopCollectionSummary');
        Route::post('rental/get-tc-collection', 'getTcCollection');
        // Route::post('rental/abc', 'abc');
        Route::post('rental/pay', 'shopPayment');
        Route::post('rental/shop-payment-by-admin', 'shopPaymentByAdmin');
        Route::post('rental/get-shop-payment-reciept', 'getPaymentReciept');
        Route::post('rental/list-shop-type', 'listShopType');
        Route::post('rental/shop-master', 'shopMaster');
        Route::post('rental/test', 'test');
        Route::post('rental/get-financial-year', 'getFinancialYear');
        Route::post('rental/search-shop-for-payment', 'searchShopForPayment');
        Route::post('rental/calculate-shop-rate-financial-wise', 'calculateShopRateFinancialwise');
        Route::post('rental/shop-payment-reciept', 'shopPaymentReciept');
        Route::post('rental/entry-check-or-dd', 'entryCheckOrDD');
        Route::post('rental/list-uncleared-check-dd', 'listEntryCheckorDD');
        Route::post('rental/clear-bounce-cheque-or-dd', 'clearOrBounceChequeOrDD');
        Route::post('rental/list-shop-collection', 'listShopCollection');
        Route::post('rental/edit-shop-data', 'editShopData');                                     // 03
    });

    /**
     * | Tolls(51)
     */
    Route::controller(TollsController::class)->group(function () {
        Route::post('toll-payments', 'tollPayments');                            //01
        Route::post('crud/toll/insert', 'store');                                //02
        Route::post('crud/toll/edit', 'edit');                                   //03
        Route::post('crud/toll/show-by-id', 'show');                             //04 
        Route::post('crud/toll/retrieve-all', 'retrieve');                       //05 
        Route::post('crud/toll/retrieve-all-active', 'retrieveActive');          //06
        Route::post('crud/toll/delete', 'delete');
        Route::post('rental/get-toll-collection-summary', 'gettollCollectionSummary');
        Route::post('rental/list-toll-by-market-id', 'listTollByMarketId');
        // Route::post('rental/list-shop', 'listShop');
        Route::post('rental/get-toll-detail-by-id', 'getTollDetailtId');
        Route::post('rental/toll-payment-by-admin', 'tollPaymentByAdmin');
        Route::post('rental/get-toll-price-list', 'getTollPriceList');
        Route::post('rental/get-toll-payment-reciept', 'getPaymentReciept');
    });


    /**
     * |Circle(52)
     */

    /**
     * | Created On-16-06-2023 
     * | Author-Ashutosh Kumar
     */
    Route::controller(CircleController::class)->group(function () {
        Route::post('v1/crud/circle/insert', 'store');                            //01
        Route::post('v1/crud/circle/update', 'edit');                             //02
        Route::post('v1/crud/circle/list-circle-by-ulbId', 'getCircleByUlb');     //03
        Route::post('v1/crud/circle/list-all-circle', 'retireveAll');             //04
        Route::post('v1/crud/circle/delete', 'delete');                           //05
    });

    /**
     * |Market(53)
     */
    Route::controller(MarketController::class)->group(function () {
        Route::post('v1/crud/market/insert', 'store');                                //01
        Route::post('v1/crud/market/update', 'edit');                                 //02
        Route::post('v1/crud/market/list-market-by-circleId', 'getMarketByCircleId'); //03
        Route::post('v1/crud/market/list-all-market', 'retireveAll');                 //04
        Route::post('v1/crud/market/delete', 'delete');                               //05
        Route::post('rental/list-construction', 'listConstruction');                               //05

    });
});
