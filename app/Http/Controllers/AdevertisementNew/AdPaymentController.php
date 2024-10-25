<?php

namespace App\Http\Controllers\AdevertisementNew;

use App\BLL\PayWithEasebuzzLib;
use App\Http\Controllers\Controller;
use App\Http\Requests\AgencyNew\AdPaymentReq;
use App\MicroServices\IdGeneration;
use App\MicroServices\IdGenerator\PrefixIdGenerator;
use App\Models\AdvertisementNew\AdApplicationAmount;
use App\Models\AdvertisementNew\AdChequeDtl;
use App\Models\AdvertisementNew\AdDirectApplicationAmount;
use App\Models\AdvertisementNew\AdTran;
use App\Models\AdvertisementNew\AdTranDetail;
use App\Models\AdvertisementNew\AdvEasebuzzPayRequest;
use App\Models\AdvertisementNew\AdvEasebuzzPayResponse;
use App\Models\AdvertisementNew\AgencyHoarding;
use App\Models\AdvertisementNew\AgencyHoardingApproveApplication;
use App\Models\AdvertisementNew\AgencyHoardingRejectedApplication;
use App\Models\AdvertisementNew\AgencyMaster;
use App\Models\Payment\TempTransaction;
use App\Models\UlbMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Exception;

class AdPaymentController extends Controller
{
    //

    private $_masterDetails;
    private $_propertyType;
    private $_occupancyType;
    private $_workflowMasterId;
    private $_advertParamId;
    private $_advertModuleId;
    private $_userType;
    private $_advertWfRoles;
    private $_docReqCatagory;
    private $_dbKey;
    private $_fee;
    private $_applicationType;
    private $_applyMode;
    private $_tranType;
    private $_tableName;
    protected $_DB_NAME;
    protected $_DB;
    protected $_DB_NAME2;
    protected $_DB2;
    private $_paymentMode;
    private $_PaymentUrl;
    private $_apiKey;
    private $_offlineVerificationModes;
    private $_offlineMode;

    private $_AgencyHoarding;
    private $_AdApplicationAmount;
    protected $_AdvEasebuzzPayRequest;
    protected $_AdvEasebuzzPayResponse;
    protected $_paymentModes;


    # Class constructer 
    public function __construct()
    {
        $this->_masterDetails               = Config::get("advert.MASTER_DATA");
        $this->_propertyType                = Config::get("advert.PROP_TYPE");
        $this->_occupancyType               = Config::get("advert.PROP_OCCUPANCY_TYPE");
        $this->_workflowMasterId            = Config::get("advert.WORKFLOW_MASTER_ID");
        $this->_advertParamId               = Config::get("advert.PARAM_ID");
        $this->_advertModuleId              = Config::get('advert.ADVERTISEMENT_MODULE_ID');
        $this->_userType                    = Config::get("advert.REF_USER_TYPE");
        $this->_advertWfRoles               = Config::get("advert.ROLE_LABEL");
        $this->_docReqCatagory              = Config::get("advert.DOC_REQ_CATAGORY");
        $this->_dbKey                       = Config::get("advert.DB_KEYS");
        $this->_fee                         = Config::get("advert.FEE_CHARGES");
        $this->_applicationType             = Config::get("advert.APPLICATION_TYPE");
        $this->_applyMode                   = Config::get("advert.APPLY_MODE");
        $this->_tranType                    = Config::get("advert.TRANSACTION_TYPE");
        $this->_tableName                   = Config::get("advert.TABLE_NAME");
        $this->_paymentMode                 = Config::get("advert.PAYMENT_MODE");
        $this->_PaymentUrl                  = Config::get('constants.95_PAYMENT_URL');
        $this->_apiKey                      = Config::get('advert.API_KEY_PAYMENT');
        $this->_offlineVerificationModes    = Config::get("advert.VERIFICATION_PAYMENT_MODES");
        $this->_offlineMode                 = Config::get("advert.OFFLINE_PAYMENT_MODE");
        $this->_paymentModes                = Config::get('advert.OFFLINE_PAYMENT_MODE');
        # Database connectivity
        // $this->_DB_NAME     = "pgsql_property";
        // $this->_DB          = DB::connection($this->_DB_NAME);
        $this->_DB_NAME2    = "pgsql_masters";
        $this->_DB2         = DB::connection($this->_DB_NAME2);

        $this->_AgencyHoarding = new AgencyHoardingApproveApplication();
        $this->_AdApplicationAmount   = new AdApplicationAmount();
        $this->_AdvEasebuzzPayRequest = new AdvEasebuzzPayRequest();
        $this->_AdvEasebuzzPayResponse = new AdvEasebuzzPayResponse();
    }


    public function initPayment(Request $request)
    {
        try {
            $user = Auth()->user();
            $rules = [
                "id" => "required|exists:" . $this->_AgencyHoarding->getConnectionName() . "." . $this->_AgencyHoarding->getTable() . ",id,status,true,approve,1",

            ];
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return validationErrorV2($validator);
            }
            $Hoarding = $this->_AgencyHoarding->find($request->id);
            # Charges for the application
            $regisCharges = collect($this->checkParamForPayment($request, $this->_paymentMode[1]));

            if (($regisCharges)->isEmpty()) {
                throw new Exception("Charges not found!");
            }
            if ($regisCharges["refRoundAmount"] <= 0) {
                throw new Exception("Payment Already Clear");
            }
            $data = [
                "userId" => $user && $user->getTable() == "users" ? $user->id : null,
                "applicationId" => $Hoarding->id,
                "applicationNo" => $Hoarding->application_no,
                "moduleId" => 14,
                "email" => ($Hoarding->email_id ?? "test@gmail.com"),
                "phone" => ($Hoarding->mobile_no ? $Hoarding->mobile_no : "1234567890"),
                "amount" => $regisCharges["refRoundAmount"],
                "firstname" => preg_match('/^[a-zA-Z0-9&\-._ \'()\/,@]+$/', $Hoarding->advertiser) ? $Hoarding->advertiser : "test user",
                "frontSuccessUrl" => $request->frontSuccessUrl,
                "frontFailUrl" => $request->frontFailUrl,
            ];

            $easebuzzObj = new PayWithEasebuzzLib();
            $result =  $easebuzzObj->initPayment($data);
            if (!$result["status"]) {
                throw new Exception("Payment Not Initiated Due To Internal Server Error");
            }

            $data["url"] = $result["data"];
            $data = collect($data)->merge($regisCharges)->merge($result);
            $request->merge($data->toArray());
            $this->_AdvEasebuzzPayRequest->related_id = $Hoarding->id;
            $this->_AdvEasebuzzPayRequest->order_id = $data["txnid"] ?? "";
            $this->_AdvEasebuzzPayRequest->demand_amt = $demand["amount"] ?? "0";
            $this->_AdvEasebuzzPayRequest->payable_amount = $data["amount"] ?? "0";
            $this->_AdvEasebuzzPayRequest->penalty_amount = 0;
            $this->_AdvEasebuzzPayRequest->rebate_amount = 0;
            $this->_AdvEasebuzzPayRequest->request_json = json_encode($request->all(), JSON_UNESCAPED_UNICODE);
            $this->_AdvEasebuzzPayRequest->save();
            return responseMsg(true, "Payment Initiated", remove_null($data));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    public function easebuzzHandelResponse(Request $request)
    {
        try {
            $requestData = $this->_AdvEasebuzzPayRequest->where("order_id", $request->txnid)->where("status", 2)->first();
            if (!$requestData) {
                throw new Exception("Request Data Not Found");
            }
            $requestPayload = json_decode($requestData->request_json, true);
            $request->merge($requestPayload);
            $request->merge([
                "paymentMode" => $this->_paymentMode[1],
                "id" => $requestData->related_id,
                "paymentGatewayType" => $request->payment_source,
            ]);
            $newRequest = new AdPaymentReq($request->all());
            $respnse = $this->offlinePayment($newRequest);
            $tranId = $respnse->original["data"]["tranId"];
            $request->merge(["tranId" => $tranId]);
            $this->_AdvEasebuzzPayResponse->request_id = $requestData->id;
            $this->_AdvEasebuzzPayResponse->related_id = $requestData->related_id;
            $this->_AdvEasebuzzPayResponse->module_id = $request->moduleId;
            $this->_AdvEasebuzzPayResponse->order_id = $request->txnid;
            $this->_AdvEasebuzzPayResponse->payable_amount = $requestData->payable_amount;
            $this->_AdvEasebuzzPayResponse->payment_id = $request->easepayid;
            $this->_AdvEasebuzzPayResponse->tran_id = $request->tranId;
            $this->_AdvEasebuzzPayResponse->error_message = $request->error_message;
            $this->_AdvEasebuzzPayResponse->user_id = $request->userId;
            $this->_AdvEasebuzzPayResponse->response_data = json_encode($request->all(), JSON_UNESCAPED_UNICODE);
            $this->_AdvEasebuzzPayResponse->save();
            $requestData->status = 1;
            $requestData->update();

            return $respnse;
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    /**
     * | Pay the registration charges in offline mode 
        | Serial no :
        | Under construction 
     */
    public function offlinePayment(AdPaymentReq $req)
    {
        $validated = Validator::make(
            $req->all(),
            ['remarks' => 'nullable',]
        );
        if ($validated->fails())
            return validationError($validated);

        try {

            # Variable declaration
            $section                    = 0;
            $receiptIdParam             = Config::get("advert.PARAM_ID.RECEIPT");
            $user                       = authUser($req);
            $todayDate                  = Carbon::now();
            $epoch                      = strtotime($todayDate);
            $offlineVerificationModes   = $this->_offlineVerificationModes;
            $mAdTran                    = new AdTran();

            # Check the params for checking payment method
            $payRelatedDetails  = $this->checkParamForPayment($req, $req->paymentMode);
            $ulbId              = $payRelatedDetails['applicationDetails']['ulb_id'] ?? 2;
            $wardId             = $payRelatedDetails['applicationDetails']['ward_id'];
            $tranType           = $payRelatedDetails['applicationDetails']['application_type'];
            $tranTypeId         = $payRelatedDetails['chargeCategory'];


            DB::beginTransaction();
            # Generate transaction no 
            // return $receiptIdParam;
            $idGeneration  = new PrefixIdGenerator($receiptIdParam,  $ulbId, $section, 0);
            $transactionNo = $idGeneration->generate();
            # Water Transactions
            $req->merge([
                'empId'         => $user->id,
                'userType'      => $user->user_type,
                'todayDate'     => $todayDate->format('Y-m-d'),
                'tranNo'        => $transactionNo,
                'ulbId'         => $ulbId,
                'isJsk'         => true,
                'wardId'        => $wardId,
                'tranType'      => $tranType,                                                              // Static
                'tranTypeId'    => $tranTypeId,
                'amount'        => $payRelatedDetails['refRoundAmount'],
                'roundAmount'   => $payRelatedDetails['regAmount'],
                'tokenNo'       => $payRelatedDetails['applicationDetails']['ref_application_id'] . $epoch              // Here 
            ]);

            # Save the Details of the transaction
            $RigTrans = $mAdTran->saveTranDetails($req);

            # Save the Details for the Cheque,DD,nfet
            if (in_array($req['paymentMode'], $offlineVerificationModes)) {
                $req->merge([
                    'chequeDate'    => $req['chequeDate'],
                    'tranId'        => $RigTrans['transactionId'],
                    'applicationNo' => $payRelatedDetails['applicationDetails']['chargeCategory'],
                    'workflowId'    => $payRelatedDetails['applicationDetails']['workflow_id'],
                    'ref_ward_id'   => $payRelatedDetails['applicationDetails']['ward_id']
                ]);
                $this->postOtherPaymentModes($req);
            }
            $this->saveAdvertRequestStatus($req, $offlineVerificationModes, $payRelatedDetails['advertCharges'], $RigTrans['transactionId'], $payRelatedDetails['applicationDetails']);
            $payRelatedDetails['applicationDetails']->payment_status = 1;
            $payRelatedDetails['applicationDetails']->save();
            DB::commit();
            $returnData = [
                "transactionNo" => $transactionNo,
                'tranId'        => $RigTrans['transactionId'],
            ];
            return responseMsgs(true, "Paymet done!", $returnData, "", "01", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), [], "", "01", ".ms", "POST", $req->deviceId);
        }
    }

    /**
     * | Save the status in active consumer table, transaction, 
        | Serial No :
        | Working
     */
    public function saveAdvertRequestStatus($request, $offlinePaymentVerModes, $charges, $waterTransId, $activeConRequest)
    {
        $mAdTranDetail                 = new AdTranDetail();
        $mAgencyHoarding               = new AgencyHoarding();
        $mAgencyApproveHoarding        = new AgencyHoardingApproveApplication();
        $mAdTran                       = new AdTran();
        $applicationId                 = $activeConRequest->id;
        //   return   $approve                       = $mAgencyApproveHoarding->getApproveApplication($applicationId);

        if (in_array($request['paymentMode'], $offlinePaymentVerModes)) {
            $charges->paid_status = 2;                                                      // Static
            $refReq = [
                "payment_status" => 2,                                                      // Update Application Payment Status // Static
            ];
            $tranReq = [
                "verify_status" => 2
            ];                                                                              // Update Charges Paid Status // Static
            $mAdTran->saveStatusInTrans($waterTransId, $tranReq);
            $mAgencyHoarding->saveApplicationStatus($applicationId, $refReq);
        } else {
            $charges->paid_status = 1;                                                      // Update Charges Paid Status // Static
            $refReq = [
                "payment_status"    => 1,
                "current_role_id"   => $activeConRequest->initiator_role_id
            ];
            $mAgencyHoarding->saveApplicationStatus($applicationId, $refReq);
        }
        $charges->save();                                                                   // ❕❕ Save Charges ❕❕

        $refTranDetails = [
            "id"            => $applicationId,
            "refChargeId"   => $charges->id,
            "roundAmount"   => $request->roundAmount,
            "tranTypeId"    => $request->tranTypeId
        ];
        $mAgencyApproveHoarding->saveApproveApplicationStatus($applicationId, $refReq);
        # Save Trans Details                                                   
        $mAdTranDetail->saveTransDetails($waterTransId, $refTranDetails);
    }

    /**
     * | Check the details and the function for the payment 
     * | return details for payment process
     * | @param req
        | Serial No: 
        | Under Construction
     */
    // public function checkParamForPayment($req, $paymentMode)
    // {
    //     $applicationId          = $req->id;
    //     $confPaymentMode        = $this->_paymentMode;
    //     $confApplicationType    = $this->_applicationType;
    //     $mAgencyHoarding        = new AgencyHoarding();
    //     $mAdApplicationAmount   = new AdApplicationAmount();
    //     $mAdTran               = new AdTran();
    //     $chargeCategory        = 3;

    //     # Application details and Validation
    //     $applicationDetail = $mAgencyHoarding->getAppicationDetails($applicationId)
    //         // ->where('advert_vehicle_active_details.status', "<>", 0)
    //         // ->where('advert_active_applicants.status', "<>", 0)
    //         ->first();
    //     if (is_null($applicationDetail)) {
    //         throw new Exception("Application details not found for ID:$applicationId!");
    //     }
    //     if ($applicationDetail->payment_status != 0) {
    //         throw new Exception("payment is updated for application");
    //     }
    //     if ($applicationDetail->citizen_id && $applicationDetail->doc_upload_status == false) {
    //         throw new Exception("All application related document not uploaded!");
    //     }

    //     # Application type hence the charge type
    //     switch ($applicationDetail->application_type) {
    //         case (0):
    //             $chargeCategory = $confApplicationType['PERMANANT'];
    //             break;
    //         case (1):
    //             $chargeCategory = $confApplicationType['TEMPORARY'];
    //             break;
    //     }

    //     # Charges for the application
    //     $regisCharges = $mAdApplicationAmount->getChargesbyId($applicationId)
    //         ->where('charge_category', $chargeCategory)
    //         ->where('paid_status', 0)
    //         ->first();

    //     if (is_null($regisCharges)) {
    //         throw new Exception("Charges not found!");
    //     }
    //     if (in_array($regisCharges->paid_status, [1, 2])) {
    //         throw new Exception("Payment has been done!");
    //     }
    //     if ($paymentMode == $confPaymentMode['1']) {
    //         if ($applicationDetail->citizen_id != authUser($req)->id) {
    //             throw new Exception("You are not he Autherized User!");
    //         }
    //     }

    //     # Transaction details
    //     $transDetails = $mAdTran->getTranDetails($applicationId, $chargeCategory)->first();
    //     if ($transDetails) {
    //         throw new Exception("Transaction has been Done!");
    //     }

    //     return [
    //         "applicationDetails"    => $applicationDetail,
    //         "advertCharges"            => $regisCharges,
    //         "chargeCategory"        => $chargeCategory,
    //         "chargeId"              => $regisCharges->id,
    //         "regAmount"             => $regisCharges->amount,
    //         "refRoundAmount"        => round($regisCharges->amount)
    //     ];
    // }

    public function checkParamForPayment($req, $paymentMode)
    {
        // $user = Auth()->user();
        $applicationId                 = $req->id;
        $confPaymentMode               = $this->_paymentMode;
        $confApplicationType           = $this->_applicationType;
        $mAgencyHoarding               = new AgencyHoarding();
        $mAgencyApproveHoarding        = new AgencyHoardingApproveApplication();
        $mAdApplicationAmount          = new AdApplicationAmount();
        $mAdDirectApplicationAmount          = new AdDirectApplicationAmount();
        $mAdTran                       = new AdTran();

        # Application details a nd Validation
        $applicationDetail = $mAgencyHoarding->getAppicationDetails($applicationId)
            ->first();

        if ($applicationDetail == null) {
            $applicationDetail = $mAgencyApproveHoarding->getApproveDetail($applicationId);
        }
        if (is_null($applicationDetail)) {
            throw new Exception("Application details not found for ID:$applicationId!");
        }
        if ($applicationDetail->payment_status != 0) {
            throw new Exception("payment is updated for application");
        }
        if ($applicationDetail->citizen_id && $applicationDetail->doc_upload_status == false) {
            throw new Exception("All application related document not uploaded!");
        }

        # Application type hence the charge type
        switch ($applicationDetail->application_type) {
            case ('PERMANENT'):
                $chargeCategory = $confApplicationType['PERMANENT'];
                break;
            case ('TEMPORARY'):
                $chargeCategory = $confApplicationType['TEMPORARY'];
                break;
        }

        # Charges for the application
        if ($applicationDetail->direct_hoarding == 0) {
            $regisCharges = $mAdApplicationAmount->getChargesbyId($applicationId)
                ->where('charge_category', $chargeCategory)
                ->where('paid_status', 0)
                ->first();
        } else {
            #charges for direct application 
            $regisCharges = $mAdDirectApplicationAmount->getChargesbyId($applicationId)
                ->where('charge_category', $chargeCategory)
                ->where('paid_status', 0)
                ->first();
        }
        if (is_null($regisCharges)) {
            throw new Exception("Charges not found!");
        }
        if (in_array($regisCharges->paid_status, [1, 2])) {
            throw new Exception("Payment has been done!");
        }
        // if ($paymentMode == $confPaymentMode['1']) {
        //     if (($user && $user->getTable() != "users") && $applicationDetail->user_id != authUser($req)->id) {
        //         throw new Exception("You are not he Autherized User!");
        //     }
        // }

        # Transaction details
        $transDetails = $mAdTran->getTranDetails($applicationId, $chargeCategory)->first();
        if ($transDetails) {
            throw new Exception("Transaction has been Done!");
        }

        return [
            "applicationDetails"    => $applicationDetail,
            "advertCharges"         => $regisCharges,
            "chargeCategory"        => $chargeCategory,
            "chargeId"              => $regisCharges->id,
            "regAmount"             => $regisCharges->amount,
            "refRoundAmount"        => round($regisCharges->amount)
        ];
    }


    /**
     * | Post Other Payment Modes for Cheque,DD,Neft
     * | @param req
        | Serial No : 0
        | Working
        | Common function
     */
    public function postOtherPaymentModes($req)
    {
        $cash        = Config::get("advert.OFFLINE_PAYMENT_MODE.4");
        $moduleId           = $this->_advertModuleId;
        $mTempTransaction   = new TempTransaction();
        $madvertChequeDtl      = new AdChequeDtl();

        if (($req['paymentMode']) != $cash) {                                   // Not Cash
            $chequeReqs = [
                'user_id'           => $req['empId'],
                'application_id'    => $req['id'],
                'transaction_id'    => $req['tranId'],
                'cheque_date'       => $req['chequeDate'],
                'bank_name'         => $req['bankName'],
                'branch_name'       => $req['branchName'],
                'cheque_no'         => $req['chequeNo']
            ];
            $madvertChequeDtl->postChequeDtl($chequeReqs);
        }

        $tranReqs = [
            'transaction_id'    => $req['tranId'],
            'application_id'    => $req['id'],
            'module_id'         => $moduleId,
            'workflow_id'       => $req['workflowId'],
            'transaction_no'    => $req['tranNo'],
            'application_no'    => $req['applicationNo'],
            'amount'            => $req['amount'],
            'payment_mode'      => strtoupper($req['paymentMode']),
            'cheque_dd_no'      => $req['chequeNo'],
            'bank_name'         => $req['bankName'],
            'tran_date'         => $req['todayDate'],
            'user_id'           => $req['empId'],
            'ulb_id'            => $req['ulbId'],
            'ward_no'           => $req['ref_ward_id']
        ];
        $mTempTransaction->tempTransaction($tranReqs);
    }


    /**
     * | Get data for payment Receipt
        | Serial No :
        | Under Con
     */
    public function generatePaymentReceipt(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'transactionNo' => 'required|',
            ]
        );
        if ($validated->fails())
            return validationError($validated);
        try {
            $now            = Carbon::now();
            $toward         = "Hoarding Payment Receipt";
            $mRigTran       = new AdTran();
            $mUlbMater      = new UlbMaster();
            $madvertChequeDtl      = new AdChequeDtl();
            $mPaymentModes      = $this->_paymentModes;

            # Get transaction details according to trans no
            $transactionDetails = $mRigTran->getTranDetailsByTranNo($request->transactionNo)->first();
            if (!$transactionDetails) {
                throw new Exception("transaction details not found! for $request->transactionNo");
            }
            if (!in_array($transactionDetails['payment_mode'], [$mPaymentModes['4'], $mPaymentModes['5']])) {
                $chequeDetails = $madvertChequeDtl->getChequeDtlsByTransId($transactionDetails->id)->first();
                if ($chequeDetails->status == 2) {
                    $chequeStatus = 'Note:This is Your Provisional Receipt';
                }
            }
            # check the transaction related details in related table
            $applicationDetails = $this->getApplicationRelatedDetails($transactionDetails);
            $fromDate = Carbon::parse($applicationDetails->from_date);
            $toDate = Carbon::parse($applicationDetails->to_date);
            $numberOfDays = $toDate->diffInDays($fromDate);
            $ulbDetails         =  $mUlbMater->getUlbDetails($transactionDetails->ulb_id);

            $returnData = [
                "transactionNo" => $transactionDetails->tran_no,
                "todayDate"     => $now->format('d-m-Y'),
                "applicationNo" => $applicationDetails->application_no,
                'mobile_no'     => $applicationDetails->mobile_no,
                "total_nodays"    => $numberOfDays,
                "applicantName" => $applicationDetails->advertiser,
                "paidAmount"    => $transactionDetails->amount,
                "toward"        => $toward,
                "paymentMode"   => $transactionDetails->payment_mode,
                "ulb"           => $applicationDetails->ulb_name,
                "paymentDate"   => Carbon::parse($transactionDetails->tran_date)->format('d-m-Y'),
                "address"       => $applicationDetails->address,
                "tokenNo"       => $transactionDetails->token_no,
                'application_type'          => $applicationDetails->application_type,
                'advertisement_type' => $applicationDetails->adv_type,
                "ulb_address"     => $transactionDetails->address,
                "advertiser"     => $applicationDetails->advertiser,
                "ulb_email"       => $transactionDetails->email,
                'amountInWords' => getIndianCurrency($transactionDetails->amount) . "Only /-",
                "bankName"      => $chequeDetails->bank_name   ?? null,                                    // in case of cheque,dd,nfts
                "branchName"    => $chequeDetails['branch_name'] ?? null,                                  // in case of chque,dd,nfts
                "chequeNo"      => $chequeDetails['cheque_no']   ?? null,                                   // in case of chque,dd,nfts
                "chequeDate"    => $chequeDetails['cheque_date'] ?? null,
                "chequeStatus"    => $chequeStatus ?? null,
                "ulbDetails"      =>  $ulbDetails,


            ];
            return responseMsgs(true, 'payment Receipt!', $returnData, "", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }

    /**
     * | Serch application from every registration table
        | Serial No 
        | Working
     */
    public function getApplicationRelatedDetails($transactionDetails)
    {
        $mAgencyHoardings     = new AgencyHoarding();
        $mAgencyApproveApplication   = new AgencyHoardingApproveApplication();
        $mAgencyRejectedApplications   = new AgencyHoardingRejectedApplication();

        # first level chain
        $refApplicationDetails = $mAgencyHoardings->getApplicationById($transactionDetails->related_id)
            ->select(
                'ulb_masters.ulb_name',
                'agency_hoardings.application_no',
                'agency_hoardings.address',
                'agency_hoardings.application_type',
                'agency_hoardings.advertiser',
                'agency_hoardings.adv_type',
                'agency_hoardings.from_date',
                'agency_hoardings.to_date',
                'agency_hoardings.mobile_no'
            )->first();
        if (!$refApplicationDetails) {
            # Second level chain
            $refApplicationDetails = $mAgencyApproveApplication->getApproveDetailById($transactionDetails->related_id)
                ->select(
                    'ulb_masters.ulb_name',
                    'agency_hoarding_approve_applications.application_no',
                    'agency_hoarding_approve_applications.address',
                    'agency_hoarding_approve_applications.adv_type',
                    'agency_hoarding_approve_applications.advertiser',
                    'agency_hoarding_approve_applications.application_type',
                    'agency_hoarding_approve_applications.from_date',
                    'agency_hoarding_approve_applications.mobile_no',
                    'agency_hoarding_approve_applications.to_date',
                )->first();
        }

        if (!$refApplicationDetails) {
            # Fourth level chain
            $refApplicationDetails = $mAgencyRejectedApplications->getRejectedApplicationById($transactionDetails->related_id)
                ->select(
                    'ulb_masters.ulb_name',
                    'agency_hoarding_rejected_application.application_no',
                    'agency_hoarding_rejected_application.address',
                    'agency_hoarding_rejected_application.adv_type',
                )->first();
        }
        # Check the existence of final data
        if (!$refApplicationDetails) {
            throw new Exception("application details not found!");
        }
        return $refApplicationDetails;
    }
    /**
     * |written by prity 
     */
    public function listCollection(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'fromDate' => 'nullable|date_format:Y-m-d',
            'toDate' => 'nullable|date_format:Y-m-d|after_or_equal:fromDate',
            'advertisement_type' => 'nullable|in:TEMPORARY,PERMANANT'
        ]);
        if ($validator->fails()) {
            return  $validator->errors();
        }
        try {
            $perPage = $req->perPage ? $req->perPage : 10;
            if (!isset($req->fromDate))
                $fromDate = Carbon::now()->format('Y-m-d');
            else
                $fromDate = $req->fromDate;
            if (!isset($req->toDate))
                $toDate = Carbon::now()->format('Y-m-d');
            else
                $toDate = $req->toDate;
            $mAdvPayment = new AdTran();
            $data = $mAdvPayment->Tran($fromDate, $toDate);
            if ($req->advertisement_type) {
                $data = $data->where('ad_trans.tran_type', $req->advertisement_type);
            }
            $paginator = $data->paginate($perPage);
            $list = [
                "current_page" => $paginator->currentPage(),
                "last_page" => $paginator->lastPage(),
                "data" => $paginator->items(),
                "total" => $paginator->total(),
                'collectAmount' => $paginator->sum('amount')
            ];
            return responseMsgs(true, "Advertisement Collection List Fetch Succefully !!!", $list, "055017", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055017", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | water Collection report 
     */
    public function tcCollectionReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'advType' => 'nullable',
            'marketId' => 'nullable|integer',
            'fromDate' => 'nullable|date_format:Y-m-d',
            'toDate' => 'nullable|date_format:Y-m-d|after_or_equal:fromDate',
            'paymentMode'  => 'nullable'
        ]);

        if ($validator->fails()) {
            return $validator->errors();
        }
        try {

            // $refUser        = authUser($request);
            // $ulbId          = $refUser->ulb_id;
            $advType = null;
            $userId = null;
            $shopCategoryId = null;
            $paymentMode = null;
            $now                        = Carbon::now()->format('Y-m-d');
            $fromDate = $uptoDate       = Carbon::now()->format("Y-m-d");
            $fromDate = $uptoDate       = Carbon::now()->format("Y-m-d");
            $now                        = Carbon::now();
            $currentDate                = Carbon::now()->format('Y-m-d');
            $currentDate                = $now->format('Y-m-d');
            $zoneId = $wardId = null;
            $currentYear                = collect(explode('-', $request->fiYear))->first() ?? $now->year;
            $currentFyear               = $request->fiYear ?? getFinancialYears($currentDate);
            $startOfCurrentYear         = Carbon::createFromDate($currentYear, 4, 1);           // Start date of current financial year
            $startOfPreviousYear        = $startOfCurrentYear->copy()->subYear();               // Start date of previous financial year
            $previousFinancialYear      = getFinancialYears($startOfPreviousYear);



            if ($request->fromDate) {
                $fromDate = $request->fromDate;
            }
            if ($request->uptoDate) {
                $uptoDate = $request->uptoDate;
            }
            if ($request->wardId) {
                $wardId = $request->wardId;
            }

            if ($request->userId) {
                $userId = $request->userId;
            }

            # In Case of any logged in TC User
            // if ($refUser->user_type == "TC") {
            //     $userId = $refUser->id;
            // }

            if ($request->marketId) {
                $paymentMode = $request->paymentMode;
            }
            if ($request->marketId) {
                $marketId = $request->marketId;
            }
            if ($request->shopCategoryId) {
                $shopCategoryId = $request->shopCategoryId;
            }

            // DB::enableQueryLog();
            $data = DB::select(DB::raw("SELECT 
              subquery.tran_id,
              subquery.tran_no,
              subquery.amount,
              subquery.user_name,
              subquery.tran_date,
              subquery.payment_mode,
              subquery.tran_type,
              subquery.name
     FROM (
         SELECT 
                ad_trans.id as tran_id,
                ad_trans.tran_date,
                ad_trans.tran_no,
                agency_hoardings.shop_owner_name,
                ad_trans.amount,
                users.user_name,
                users.name
        
        FROM ad_trans 
        -- LEFT JOIN ulb_ward_masters ON ulb_ward_masters.id=water_trans.ward_id
        LEFT JOIN agency_hoardings ON agency_hoardings.id=ad_trans.
        -- JOIN water_consumer_demands ON water_consumer_demands.consumer_id=water_trans.related_id
        JOIN mar_shop_demands on mar_shop_demands.shop_id = mar_shop_payments.shop_id
        LEFT JOIN users ON users.id=mar_shop_payments.user_id
       -- and tran_type = 'Demand Collection'
        and ad_trans.payment_date between '$fromDate' and '$uptoDate'
                    " . ($advType ? " AND  mar_shops.shop_category_id = $advType" : "") . "
                     " . ($paymentMode ? " AND mar_shop_payments.pmt_mode = $paymentMode" : "") . "
                     " . ($userId ? " AND water_trans.emp_dtl_id = $userId" : "") . "
                    " . ($paymentMode ? " AND mar_shop_payments.payment_mode = '$paymentMode'" : "") . "
        GROUP BY 
               ad_trans.id,
                ad_trans.tran_date,
                ad_trans.tran_no,
                ad_trans.amount,
                users.user_name,
                users.name
        
     ) AS subquery"));
            $refData = collect($data);

            $refDetailsV2 = [
                "array" => $data,
                "sum_current_coll" => roundFigure($refData->pluck('current_collections')->sum() ?? 0),
                "sum_arrear_coll" => roundFigure($refData->pluck('arrear_collections')->sum() ?? 0),
                "sum_total_coll" => roundFigure($refData->pluck('total_collections')->sum() ?? 0),
                "sum_current_coll_bot" => roundFigure($refData->pluck('current_collections_bot')->sum() ?? 0),
                "sum_current_coll_city" => roundFigure($refData->pluck('current_collections_city')->sum() ?? 0),
                "sum_current_coll_gp" => roundFigure($refData->pluck('current_collections_gp')->sum() ?? 0),
                "sum_arrear_coll_bot" => roundFigure($refData->pluck('arrear_collections_bot')->sum() ?? 0),
                "sum_arrear_coll_city" => roundFigure($refData->pluck('arrear_collections_city')->sum() ?? 0),
                "sum_arrear_coll_gp" => roundFigure($refData->pluck('arrear_collections_gp')->sum() ?? 0),
                "totalAmount"   =>  roundFigure($refData->pluck('amount')->sum() ?? 0),
                "totalColletion" => $refData->pluck('tran_id')->count(),
                "currentDate"  => $currentDate
            ];
            return responseMsgs(true, "collection Report", $refDetailsV2);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $request->all());
        }
    }
}
