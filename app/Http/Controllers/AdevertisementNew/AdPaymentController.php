<?php

namespace App\Http\Controllers\AdevertisementNew;

use App\Http\Controllers\Controller;
use App\Http\Requests\AgencyNew\AdPaymentReq;
use App\MicroServices\IdGeneration;
use App\MicroServices\IdGenerator\PrefixIdGenerator;
use App\Models\AdvertisementNew\AdApplicationAmount;
use App\Models\AdvertisementNew\AdChequeDtl;
use App\Models\AdvertisementNew\AdTran;
use App\Models\AdvertisementNew\AdTranDetail;
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


    # Class constructer 
    public function __construct()
    {
        $this->_masterDetails               = Config::get("advert.MASTER_DATA");
        $this->_propertyType                = Config::get("advert.PROP_TYPE");
        $this->_occupancyType               = Config::get("advert.PROP_OCCUPANCY_TYPE");
        $this->_workflowMasterId            = Config::get("advert.WORKFLOW_MASTER_ID");
        $this->_advertParamId               = Config::get("advert.PARAM_ID");
        $this->_advertModuleId              = Config::get('advert.RIG_MODULE_ID');
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
        # Database connectivity
        // $this->_DB_NAME     = "pgsql_property";
        // $this->_DB          = DB::connection($this->_DB_NAME);
        $this->_DB_NAME2    = "pgsql_masters";
        $this->_DB2         = DB::connection($this->_DB_NAME2);
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
            $ulbId              = $payRelatedDetails['applicationDetails']['ulb_id'];
            $wardId             = $payRelatedDetails['applicationDetails']['ward_id'];
            $tranType           = $payRelatedDetails['applicationDetails']['application_type'];
            $tranTypeId         = $payRelatedDetails['chargeCategory'];

            DB::beginTransaction();
            # Generate transaction no 
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
                "transactionNo" => $transactionNo
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
        $mAdTranDetail          = new AdTranDetail();
        $mAgencyHoarding        = new AgencyHoarding();
        $mAdTran                = new AdTran();
        $applicationId          = $activeConRequest->id;

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
        $applicationId          = $req->id;
        $confPaymentMode        = $this->_paymentMode;
        $confApplicationType    = $this->_applicationType;
        $mAgencyHoarding        = new AgencyHoarding();
        $mAdApplicationAmount   = new AdApplicationAmount();
        $mAdTran               = new AdTran();
        # Application details and Validation
        $applicationDetail = $mAgencyHoarding->getAppicationDetails($applicationId)
            // ->where('rig_vehicle_active_details.status', "<>", 0)
            // ->where('rig_active_applicants.status', "<>", 0)
            ->first();
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
            case ('PERMANANT'):
                $chargeCategory = $confApplicationType['PERMANANT'];
                break;
            case ('TEMPORARY'):
                $chargeCategory = $confApplicationType['TEMPORARY'];
                break;
        }

        # Charges for the application
        $regisCharges = $mAdApplicationAmount->getChargesbyId($applicationId)
            ->where('charge_category', $chargeCategory)
            ->where('paid_status', 0)
            ->first();

        if (is_null($regisCharges)) {
            throw new Exception("Charges not found!");
        }
        if (in_array($regisCharges->paid_status, [1, 2])) {
            throw new Exception("Payment has been done!");
        }
        if ($paymentMode == $confPaymentMode['1']) {
            if ($applicationDetail->citizen_id != authUser($req)->id) {
                throw new Exception("You are not he Autherized User!");
            }
        }

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
        $paymentMode        = $this->_offlineMode;
        $moduleId           = $this->_advertModuleId;
        $mTempTransaction   = new TempTransaction();
        $madvertChequeDtl      = new AdChequeDtl();

        if ($req['paymentMode'] != $paymentMode[3]) {                                   // Not Cash
            $chequeReqs = [
                'user_id'           => $req['userId'],
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
            'user_id'           => $req['userId'],
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

            # Get transaction details according to trans no
            $transactionDetails = $mRigTran->getTranDetailsByTranNo($request->transactionNo)->first();
            if (!$transactionDetails) {
                throw new Exception("transaction details not found! for $request->transactionNo");
            }
            # check the transaction related details in related table
            $applicationDetails = $this->getApplicationRelatedDetails($transactionDetails);
            $ulbDetails         =  $mUlbMater->getUlbDetails($transactionDetails->ulb_id);

            $returnData = [
                "transactionNo" => $transactionDetails->tran_no,
                "todayDate"     => $now->format('d-m-Y'),
                "applicationNo" => $applicationDetails->application_no,
                "applicantName" => $applicationDetails->applicant_name,
                "paidAmount"    => $transactionDetails->amount,
                "toward"        => $toward,
                "paymentMode"   => $transactionDetails->payment_mode,
                "ulb"           => $applicationDetails->ulb_name,
                "paymentDate"   => Carbon::parse($transactionDetails->tran_date)->format('d-m-Y'),
                "address"       => $applicationDetails->address,
                "tokenNo"       => $transactionDetails->token_no,
                'application_type'          => $applicationDetails->application_type,
                'advertisement_type' =>$applicationDetails->adv_type,
                "ulb_address"     => $transactionDetails->address,
                "advertiser"     => $applicationDetails->advertiser,
                "ulb_email"       => $transactionDetails->email,
                'amountInWords' => getIndianCurrency($transactionDetails->amount) . "Only /-",
                "ulbDetails"      =>  $ulbDetails


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
                'agency_hoardings.adv_type'
            )->first();
        if (!$refApplicationDetails) {
            # Second level chain
            $refApplicationDetails = $mAgencyApproveApplication->getApproveDetailById($transactionDetails->related_id)
                ->select(
                    'ulb_masters.ulb_name',
                    'agency_hoarding_approve_applications.application_no',
                    'agency_hoarding_approve_applications.address',
                    'agency_hoarding_approve_applications.adv_type',
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
}
