<?php

namespace App\Http\Controllers\Params;

use App\Http\Controllers\Controller;
use App\MicroServices\DocumentUpload;
use App\Models\Advertisements\AdvActiveAgency;
use App\Models\Advertisements\AdvActiveHoarding;
use App\Models\Advertisements\AdvActivePrivateland;
use App\Models\Advertisements\AdvActiveSelfadvertisement;
use App\Models\Markets\MarRejectedDharamshala;
use App\Models\Param\RefAdvParamstring;
use App\Models\Advertisements\AdvActiveSelfadvetdocument;
use App\Models\Advertisements\AdvActiveVehicle;
use App\Models\Advertisements\AdvAgency;
use App\Models\Advertisements\AdvAgencyLicense;
use App\Models\Advertisements\AdvAgencyRenewal;
use App\Models\Advertisements\AdvHoarding;
use App\Models\Advertisements\AdvHoardingRenewal;
use App\Models\Advertisements\AdvPrivateland;
use App\Models\Advertisements\AdvPrivatelandRenewal;
use App\Models\Advertisements\AdvRejectedAgency;
use App\Models\Advertisements\AdvRejectedHoarding;
use App\Models\Advertisements\AdvRejectedPrivateland;
use App\Models\Advertisements\AdvRejectedSelfadvertisement;
use App\Models\Advertisements\AdvRejectedVehicle;
use App\Models\Advertisements\AdvSelfadvertisement;
use App\Models\Advertisements\AdvSelfadvetRenewal;
use App\Models\Advertisements\AdvTypologyMstr;
use App\Models\Advertisements\AdvVehicle;
use App\Models\Advertisements\AdvVehicleRenewal;
use App\Models\Advertisements\RefRequiredDocument;
use App\Models\Advertisements\WfActiveDocument;
use App\Models\Markets\MarActiveBanquteHall;
use App\Models\Markets\MarActiveDharamshala;
use App\Models\Markets\MarActiveHostel;
use App\Models\Markets\MarActiveLodge;
use App\Models\Markets\MarBanquteHall;
use App\Models\Markets\MarBanquteHallRenewal;
use App\Models\Markets\MarDharamshala;
use App\Models\Markets\MarDharamshalaRenewal;
use App\Models\Markets\MarHostel;
use App\Models\Markets\MarHostelRenewal;
use App\Models\Markets\MarLodge;
use App\Models\Markets\MarLodgeRenewal;
use App\Models\Markets\MarRejectedBanquteHall;
use App\Models\Markets\MarRejectedHostel;
use App\Models\Markets\MarRejectedLodge;
use App\Models\Param\AdvMarTransaction;
use App\Models\Workflows\WfRoleusermap;
use App\Models\Workflows\WfWorkflow;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;

use App\Service\WhatsappServiceInterface;
use Ramsey\Collection\Collection;

class ParamController extends Controller
{

    protected $_selfAdvt;
    protected $_pvtLand;
    protected $_movableVehicle;
    protected $_agency;
    protected $_hording;
    protected $_banquetHall;
    protected $_hostel;
    protected $_lodge;
    protected $_dharamshala;
    protected $_advtModuleId;
    protected $_marketModuleId;
    protected $_ulbLogoUrl;

    //Constructor
    public function __construct()
    {
        $this->_selfAdvt = Config::get('workflow-constants.ADVERTISEMENT_WF_MASTER_ID');
        $this->_pvtLand = Config::get('workflow-constants.PRIVATE_LAND_WF_MASTER_ID');
        $this->_movableVehicle = Config::get('workflow-constants.VEHICLE_WF_MASTER_ID');
        $this->_agency = Config::get('workflow-constants.AGENCY_WF_MASTER_ID');
        $this->_hording = Config::get('workflow-constants.HORDING_WF_MASTER_ID');
        $this->_banquetHall = Config::get('workflow-constants.BANQUTE_HALL_WF_MASTER_ID');
        $this->_hostel = Config::get('workflow-constants.HOSTEL_WF_MASTER_ID');
        $this->_lodge = Config::get('workflow-constants.LODGE_WF_MASTER_ID');
        $this->_dharamshala = Config::get('workflow-constants.DHARAMSHALA_WF_MASTER_ID');
        $this->_advtModuleId = Config::get('workflow-constants.ADVERTISMENT_MODULE_ID');
        $this->_marketModuleId = Config::get('workflow-constants.MARKET_MODULE_ID');
        $this->_ulbLogoUrl = Config::get('constants.ULB_LOGO_URL');
    }

    /**
     * | String Parameters values
     * | @param request $req
     * | Function - 01
     */
    public function paramStrings(Request $req)
    {
        $redis = Redis::connection();
        try {
            // Variable initialization
            $startTime = microtime(true);
            $mUlbId = $req->ulbId;
            $data = json_decode(Redis::get('adv_param_strings'));      // Get Value from Redis Cache Memory
            if (!$data) {                                                        // If Cache Memory is not available
                $data = array();
                $mParamString = new RefAdvParamstring();
                $strings = $mParamString->masters();
                $data['paramCategories'] = remove_null($strings->groupBy('param_category')->toArray());

                // $mAdvTypologyMstr = new AdvTypologyMstr();
                // $typologyList = $mAdvTypologyMstr->listTypology();                  // Get Topology List

                // $data['paramCategories']['typology'] = $typologyList;

                $redis->set('adv_param_strings' . $mUlbId, json_encode($data));      // Set Key on Param Strings
            }
            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            return responseMsgs(true, "Param Strings", $data, "050201", "1.0", "$executionTime Sec", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050201", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Get Document Masters from our localstorage db
     * | Function - 02
     */
    public function documentMstrs()
    {
        $startTime = microtime(true);
        $documents = json_decode(file_get_contents(storage_path() . "/local-db/advDocumentMstrs.json", true));
        $documents = remove_null($documents);
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        return responseMsgs(true, "Document Masters", $documents, "050202", "1.0", $executionTime . " Sec", "POST");
    }

    /**
     * | All Document List
     * | Function - 03
     */
    public function listDocument()
    {
        $mRefRequiredDocument = new RefRequiredDocument();
        $listDocs = $mRefRequiredDocument->listDocument($this->_advtModuleId, $this->_marketModuleId);
        $documentList = array();
        foreach ($listDocs as $key => $val) {
            $alldocs = explode("#", $val['requirements']);
            foreach ($alldocs as $kinn => $valinn) {
                $arr = explode(',', $valinn);
                $documentList[$val['code']][$kinn]['docType'] = $arr[0];
                $documentList[$val['code']][$kinn]['docCode'] = $arr[1];
                $documentList[$val['code']][$kinn]['docVal'] = ucwords(strtolower(str_replace('_', ' ', $arr[1])));
                $documentList[$val['code']][$kinn]['document_name'] = ucwords(strtolower(str_replace('_', ' ', $arr[1])));
                $documentList[$val['code']][$kinn]['code'] = $val['code'];
            }
        }
        return $documentList;
    }

    /**
     * | Get Document Masters from our localstorage db
     * | Function - 04
     */
    public function districtMstrs()
    {
        $startTime = microtime(true);
        $districts = json_decode(file_get_contents(storage_path() . "/local-db/districtMstrs.json", true));
        $districts = remove_null($districts);
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        return responseMsgs(true, "District Masters", $districts, "050204", "1.0", $executionTime . " Sec", "POST");
    }

    public function metaReqs($req)
    {
        $metaReqs = [
            'verified_by' => $req['roleId'],
            'verify_status' => $req['verifyStatus'],
            'remarks' => $req['remarks'],
            'verified_on' => Carbon::now()->format('Y-m-d')
        ];
        return $metaReqs;
    }

    /**
     * Summary of payment Success Failure of all Types of Advertisment 
     * @return void
     * @param request $req
     * | Function - 05
     */
    public function paymentSuccessFailure(Request $req)
    {
        try {
            // Variable initialization
            $startTime = microtime(true);
            DB::beginTransaction();
            $updateData = [
                'payment_date' => Carbon::now(),
                'payment_status' => 1,
                'payment_id' => $req->paymentId,
                'payment_details' => $req->all(),
            ];
            $wfworkflowMasterId = $this->getWorkflowMasterId($req->workflowId);

            if ($wfworkflowMasterId == $this->_selfAdvt) { // Self Advertisement Payment

                $mAdvSelfadvertisement = AdvSelfadvertisement::find($req->id);

                $mAdvSelfadvertisement->payment_date = Carbon::now();
                $mAdvSelfadvertisement->payment_mode = "Online";
                $mAdvSelfadvertisement->payment_status = 1;
                $mAdvSelfadvertisement->payment_id = $req->paymentId;
                $mAdvSelfadvertisement->payment_details = $req->all();

                if ($mAdvSelfadvertisement->renew_no == NULL) {
                    $mAdvSelfadvertisement->valid_from = Carbon::now();
                    $mAdvSelfadvertisement->valid_upto = Carbon::now()->addYears(1)->subDay(1);
                } else {
                    $details = AdvSelfadvetRenewal::select('valid_upto')
                        ->where('license_no', $mAdvSelfadvertisement->license_no)
                        ->orderByDesc('id')
                        ->skip(1)->first();
                    $mAdvSelfadvertisement->valid_from = $details->valid_upto;
                    $mAdvSelfadvertisement->valid_upto = Carbon::createFromFormat('Y-m-d', $details->valid_upto)->addYears(1)->subDay(1);
                }
                $mAdvSelfadvertisement->save();

                $updateData['payment_amount'] = $req->amount;
                $updateData['demand_amount'] = $mAdvSelfadvertisement->demand_amount;
                $updateData['valid_from'] = $mAdvSelfadvertisement->valid_from;
                $updateData['valid_upto'] = $mAdvSelfadvertisement->valid_upto;
                $updateData['payment_mode'] = "Online";
                // update in Renewals Table
                DB::table('adv_selfadvet_renewals')
                    ->where('id', $mAdvSelfadvertisement->last_renewal_id)
                    ->update($updateData);

                $appDetails = AdvSelfadvertisement::find($req->id);
                $mAdvMarTransaction = new AdvMarTransaction();
                $mAdvMarTransaction->addTransaction($appDetails, $this->_advtModuleId, "Advertisement", "Online");
            } elseif ($wfworkflowMasterId == $this->_movableVehicle) { // Movable Vechicles Payment
                $mAdvVehicle = AdvVehicle::find($req->id);

                $mAdvVehicle->payment_date = Carbon::now();
                $mAdvVehicle->payment_mode = "Online";
                $mAdvVehicle->payment_status = 1;
                $mAdvVehicle->payment_id = $req->paymentId;
                $mAdvVehicle->payment_details = $req->all();

                if ($mAdvVehicle->renew_no == NULL) {
                    $mAdvVehicle->valid_from = Carbon::now();
                    $mAdvVehicle->valid_upto = Carbon::now()->addYears(1)->subDay(1);
                } else {
                    $details = AdvVehicleRenewal::select('valid_upto')
                        ->where('license_no', $mAdvVehicle->license_no)
                        ->orderByDesc('id')
                        ->skip(1)->first();
                    $mAdvVehicle->valid_from = $details->valid_upto;
                    $mAdvVehicle->valid_upto = Carbon::createFromFormat('Y-m-d', $details->valid_upto)->addYears(1)->subDay(1);
                }
                $mAdvVehicle->save();

                $updateData['payment_amount'] = $req->amount;
                $updateData['valid_from'] = $mAdvVehicle->valid_from;
                $updateData['valid_upto'] = $mAdvVehicle->valid_upto;
                $updateData['payment_mode'] = "Online";
                // update in Renewals Table
                DB::table('adv_vehicle_renewals')
                    ->where('id', $mAdvVehicle->last_renewal_id)
                    ->update($updateData);

                $appDetails = AdvVehicle::find($req->id);
                $mAdvMarTransaction = new AdvMarTransaction();
                $mAdvMarTransaction->addTransaction($appDetails, $this->_advtModuleId, "Advertisement", "Online");
            } elseif ($wfworkflowMasterId ==  $this->_agency) { // Agency Apply Payment

                $mAdvAgency = AdvAgency::find($req->id);

                $mAdvAgency->payment_date = Carbon::now();
                $mAdvAgency->payment_status = 1;
                $mAdvAgency->payment_mode = "Online";
                $mAdvAgency->payment_id = $req->paymentId;
                $mAdvAgency->payment_details = $req->all();

                if ($mAdvAgency->renew_no == NULL) {
                    $mAdvAgency->valid_from = Carbon::now();
                    $mAdvAgency->valid_upto = Carbon::now()->addYears(5)->subDay(1);
                } else {
                    $details = AdvAgencyRenewal::select('valid_upto')
                        ->where('license_no', $mAdvAgency->license_no)
                        ->orderByDesc('id')
                        ->skip(1)->first();
                    $mAdvAgency->valid_from = $details->valid_upto;
                    $mAdvAgency->valid_upto = Carbon::createFromFormat('Y-m-d', $details->valid_upto)->addYears(5)->subDay(1);
                }
                $mAdvAgency->save();

                $updateData['payment_amount'] = $req->amount;
                $updateData['demand_amount'] = $mAdvAgency->demand_amount;
                $updateData['valid_from'] = $mAdvAgency->valid_from;
                $updateData['valid_upto'] = $mAdvAgency->valid_upto;
                $updateData['payment_mode'] = "Online";
                // update in Renewals Table
                DB::table('adv_agency_renewals')
                    ->where('id', $mAdvAgency->last_renewal_id)
                    ->update($updateData);

                $appDetails = AdvAgency::find($req->id);
                $mAdvMarTransaction = new AdvMarTransaction();
                $mAdvMarTransaction->addTransaction($appDetails, $this->_advtModuleId, "Advertisement", "Online");
            } elseif ($wfworkflowMasterId == $this->_pvtLand) { // Private Land Payment

                $mAdvPrivateland = AdvPrivateland::find($req->id);
                $mAdvPrivateland->payment_date = Carbon::now();
                $mAdvPrivateland->payment_mode = "Online";
                $mAdvPrivateland->payment_status = 1;
                $mAdvPrivateland->payment_id = $req->paymentId;
                $mAdvPrivateland->payment_details = $req->all();

                if ($mAdvPrivateland->renew_no == NULL) {
                    $mAdvPrivateland->valid_from = Carbon::now();
                    $mAdvPrivateland->valid_upto = Carbon::now()->addYears(1)->subDay(1);
                } else {
                    $details = AdvPrivatelandRenewal::select('valid_upto')
                        ->where('license_no', $mAdvPrivateland->license_no)
                        ->orderByDesc('id')
                        ->skip(1)->first();
                    $mAdvPrivateland->valid_from = $details->valid_upto;
                    $mAdvPrivateland->valid_upto = Carbon::createFromFormat('Y-m-d', $details->valid_upto)->addYears(1)->subDay(1);
                }
                $mAdvPrivateland->save();

                $updateData['payment_amount'] = $req->amount;
                $updateData['demand_amount'] = $mAdvPrivateland->demand_amount;
                $updateData['valid_from'] = $mAdvPrivateland->valid_from;
                $updateData['valid_upto'] = $mAdvPrivateland->valid_upto;
                $updateData['payment_mode'] = "Online";
                // update in Renewals Table
                DB::table('adv_privateland_renewals')
                    ->where('id', $mAdvPrivateland->last_renewal_id)
                    ->update($updateData);
                $appDetails = AdvPrivateland::find($req->id);
                $mAdvMarTransaction = new AdvMarTransaction();
                $mAdvMarTransaction->addTransaction($appDetails, $this->_advtModuleId, "Advertisement", "Online");
            } elseif ($wfworkflowMasterId == $this->_hording) { // Hording Apply Payment

                $mAdvHoarding = AdvHoarding::find($req->id);
                $mAdvHoarding->payment_date = Carbon::now();
                $mAdvHoarding->payment_status = 1;
                $mAdvHoarding->payment_mode = "Online";
                $mAdvHoarding->payment_id = $req->paymentId;
                $mAdvHoarding->payment_details = $req->all();

                if ($mAdvHoarding->renew_no == NULL) {
                    $mAdvHoarding->valid_from = Carbon::now();
                    $mAdvHoarding->valid_upto = Carbon::now()->addYears(1)->subDay(1);
                } else {
                    $details = AdvHoardingRenewal::select('valid_upto')
                        ->where('license_no', $mAdvHoarding->license_no)
                        ->orderByDesc('id')
                        ->skip(1)->first();
                    $mAdvHoarding->valid_from = $details->valid_upto;
                    $mAdvHoarding->valid_upto = Carbon::createFromFormat('Y-m-d', $details->valid_upto)->addYears(1)->subDay(1);
                }
                $mAdvHoarding->save();

                $updateData['payment_amount'] = $req->amount;
                $updateData['demand_amount'] = $mAdvHoarding->demand_amount;
                $updateData['valid_from'] = $mAdvHoarding->valid_from;
                $updateData['valid_upto'] = $mAdvHoarding->valid_upto;
                $updateData['payment_mode'] = "Online";
                // update in Renewals Table
                DB::table('adv_hoarding_renewals')
                    ->where('id', $mAdvHoarding->last_renewal_id)
                    ->update($updateData);
                $appDetails = AdvHoarding::find($req->id);
                $mAdvMarTransaction = new AdvMarTransaction();
                $mAdvMarTransaction->addTransaction($appDetails, $this->_advtModuleId, "Advertisement", "Online");
            } elseif ($wfworkflowMasterId == $this->_banquetHall) { // Hording Apply Payment

                $mMarBanquteHall = MarBanquteHall::find($req->id);
                $mMarBanquteHall->payment_date = Carbon::now();
                $mMarBanquteHall->payment_status = 1;
                $mMarBanquteHall->payment_mode = "Online";
                $mMarBanquteHall->payment_id = $req->paymentId;
                $mMarBanquteHall->payment_details = $req->all();

                if ($mMarBanquteHall->renew_no == NULL) {
                    $mMarBanquteHall->valid_from = Carbon::now();
                    $mMarBanquteHall->valid_upto = Carbon::now()->addYears(1)->subDay(1);
                } else {
                    $details = MarBanquteHallRenewal::select('valid_upto')
                        ->where('license_no', $mMarBanquteHall->license_no)
                        ->orderByDesc('id')
                        ->skip(1)->first();
                    $mMarBanquteHall->valid_from = $details->valid_upto;
                    // $mMarBanquteHall->valid_upto = date("Y-m-d",strtotime("+1 Years -1 days", $details->valid_upto));
                    $mMarBanquteHall->valid_upto = Carbon::createFromFormat('Y-m-d', $details->valid_upto)->addYears(1)->subDay(1);
                }
                $mMarBanquteHall->save();

                $updateData['payment_amount'] = $req->amount;
                $updateData['demand_amount'] = $mMarBanquteHall->demand_amount;
                $updateData['valid_from'] = $mMarBanquteHall->valid_from;
                $updateData['valid_upto'] = $mMarBanquteHall->valid_upto;
                $updateData['payment_mode'] = "Online";
                // update in Renewals Table
                DB::table('mar_banqute_hall_renewals')
                    ->where('id', $mMarBanquteHall->last_renewal_id)
                    ->update($updateData);

                $appDetails = MarBanquteHall::find($req->id);
                $mAdvMarTransaction = new AdvMarTransaction();
                $mAdvMarTransaction->addTransaction($appDetails, $this->_advtModuleId, "Market", "Online");
            } elseif ($wfworkflowMasterId == $this->_hostel) { // Hostel Apply Payment

                $mMarHostel = MarHostel::find($req->id);
                $mMarHostel->payment_date = Carbon::now();
                $mMarHostel->payment_status = 1;
                $mMarHostel->payment_mode = "Online";
                $mMarHostel->payment_id = $req->paymentId;
                $mMarHostel->payment_details = $req->all();

                if ($mMarHostel->renew_no == NULL) {
                    $mMarHostel->valid_from = Carbon::now();
                    $mMarHostel->valid_upto = Carbon::now()->addYears(1)->subDay(1);
                } else {
                    $details = MarHostelRenewal::select('valid_upto')
                        ->where('license_no', $mMarHostel->license_no)
                        ->orderByDesc('id')
                        ->skip(1)->first();
                    $mMarHostel->valid_from = $details->valid_upto;
                    $mMarHostel->valid_upto = Carbon::createFromFormat('Y-m-d', $details->valid_upto)->addYears(1)->subDay(1);
                }
                $mMarHostel->save();

                $updateData['payment_amount'] = $req->amount;
                $updateData['demand_amount'] = $mMarHostel->demand_amount;
                $updateData['valid_from'] = $mMarHostel->valid_from;
                $updateData['valid_upto'] = $mMarHostel->valid_upto;
                $updateData['payment_mode'] = "Online";
                // update in Renewals Table
                DB::table('mar_hostel_renewals')
                    ->where('id', $mMarHostel->last_renewal_id)
                    ->update($updateData);
                $appDetails = MarHostel::find($req->id);
                $mAdvMarTransaction = new AdvMarTransaction();
                $mAdvMarTransaction->addTransaction($appDetails, $this->_advtModuleId, "Market", "Online");
            } elseif ($wfworkflowMasterId == $this->_lodge) { // Lodge Apply Payment

                $mMarLodge = MarLodge::find($req->id);
                $mMarLodge->payment_date = Carbon::now();
                $mMarLodge->payment_status = 1;
                $mMarLodge->payment_mode = "Online";
                $mMarLodge->payment_id = $req->paymentId;
                $mMarLodge->payment_details = $req->all();

                if ($mMarLodge->renew_no == NULL) {
                    $mMarLodge->valid_from = Carbon::now();
                    $mMarLodge->valid_upto = Carbon::now()->addYears(1)->subDay(1);
                } else {
                    $details = MarLodgeRenewal::select('valid_upto')
                        ->where('license_no', $mMarLodge->license_no)
                        ->orderByDesc('id')
                        ->skip(1)->first();
                    $mMarLodge->valid_from = $details->valid_upto;
                    $mMarLodge->valid_upto = Carbon::createFromFormat('Y-m-d', $details->valid_upto)->addYears(1)->subDay(1);
                    // $mMarLodge = $mMarLodge->valid_upto;
                }
                $mMarLodge->save();

                $updateData['payment_amount'] = $req->amount;
                $updateData['demand_amount'] = $mMarLodge->demand_amount;
                $updateData['valid_from'] = $mMarLodge->valid_from;
                $updateData['valid_upto'] = $mMarLodge->valid_upto;
                $updateData['payment_mode'] = "Online";
                // update in Renewals Table
                DB::table('mar_lodge_renewals')
                    ->where('id', $mMarLodge->last_renewal_id)
                    ->update($updateData);
                $appDetails = MarLodge::find($req->id);
                $mAdvMarTransaction = new AdvMarTransaction();
                $mAdvMarTransaction->addTransaction($appDetails, $this->_advtModuleId, "Market", "Online");
            } elseif ($wfworkflowMasterId == $this->_dharamshala) { // Dharamshala Apply Payment
                $mMarDharamshala = MarDharamshala::find($req->id);
                $mMarDharamshala->payment_date = Carbon::now();
                $mMarDharamshala->payment_status = 1;
                $mMarDharamshala->payment_mode = "Online";
                $mMarDharamshala->payment_id = $req->paymentId;
                $mMarDharamshala->payment_details = $req->all();

                if ($mMarDharamshala->renew_no == NULL) {
                    $mMarDharamshala->valid_from = Carbon::now();
                    $mMarDharamshala->valid_upto = Carbon::now()->addYears(1)->subDay(1);
                } else {
                    $details = MarDharamshalaRenewal::select('valid_upto')
                        ->where('license_no', $mMarDharamshala->license_no)
                        ->orderByDesc('id')
                        ->skip(1)->first();
                    $mMarDharamshala->valid_from = $details->valid_upto;
                    $mMarDharamshala->valid_upto = Carbon::createFromFormat('Y-m-d', $details->valid_upto)->addYears(1)->subDay(1);
                }
                $mMarDharamshala->save();
                $a = $mMarDharamshala->valid_upto;
                $updateData['payment_amount'] = $req->amount;
                $updateData['demand_amount'] = $mMarDharamshala->demand_amount;
                $updateData['valid_from'] = $mMarDharamshala->valid_from;
                $updateData['valid_upto'] = $mMarDharamshala->valid_upto;
                $updateData['payment_mode'] = "Online";
                // update in Renewals Table
                DB::table('mar_dharamshala_renewals')
                    ->where('id', $mMarDharamshala->last_renewal_id)
                    ->update($updateData);
                $appDetails = MarDharamshala::find($req->id);
                $mAdvMarTransaction = new AdvMarTransaction();
                $mAdvMarTransaction->addTransaction($appDetails, $this->_advtModuleId, "Market", "Online");
            }
            DB::commit();
            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            $msg = "Payment Accepted Successfully !!!";
            return responseMsgs(true, $msg, "", '050205', 01, "$executionTime Sec", 'POST', $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", '050205', 01, "", 'POST', $req->deviceId);
        }
    }

    /**
     * | Get Payment Details for all workflow
     * | Function - 06
     */
    public function getPaymentDetails(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'paymentId' => 'required|string',
            'workflowId' => 'required|integer'
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            // Variable initialization
            $startTime = microtime(true);
            $wfworkflowMasterId = $this->getWorkflowMasterId($req->workflowId);
            // Get Advertesement Payment Details
            if ($wfworkflowMasterId == $this->_selfAdvt) {
                $mAdvSelfadvertisement = new AdvSelfadvertisement();
                $paymentDetails = $mAdvSelfadvertisement->getPaymentDetails($req->paymentId);
                $paymentDetails->ulbLogo = $this->_ulbLogoUrl . $paymentDetails->ulbLogo;
                $paymentDetails->inWords = getIndianCurrency($paymentDetails->payment_amount) . " Only /-";
                $paymentDetails->paymentAgainst = "Self Advertisement Tax";
            } elseif ($wfworkflowMasterId == $this->_pvtLand) {
                $mAdvPrivateland = new AdvPrivateland();
                $paymentDetails = $mAdvPrivateland->getPaymentDetails($req->paymentId);
                $paymentDetails->ulbLogo = $this->_ulbLogoUrl . $paymentDetails->ulbLogo;
                $paymentDetails->inWords = getIndianCurrency($paymentDetails->payment_amount) . " Only /-";
                $paymentDetails->paymentAgainst = "Private Land Tax";
            } elseif ($wfworkflowMasterId ==  $this->_movableVehicle) {
                $mAdvVehicle = new AdvVehicle();
                $paymentDetails = $mAdvVehicle->getPaymentDetails($req->paymentId);
                $paymentDetails->ulbLogo = $this->_ulbLogoUrl . $paymentDetails->ulbLogo;
                $paymentDetails->inWords = getIndianCurrency($paymentDetails->payment_amount) . " Only /-";
                $paymentDetails->paymentAgainst = "Movable Vehicle Tax";
            } elseif ($wfworkflowMasterId == $this->_agency) {
                $mAdvAgency = new AdvAgency();
                $paymentDetails = $mAdvAgency->getPaymentDetails($req->paymentId);
                $paymentDetails->ulbLogo = $this->_ulbLogoUrl . $paymentDetails->ulbLogo;
                $paymentDetails->inWords = getIndianCurrency($paymentDetails->payment_amount) . " Only /-";
                $paymentDetails->paymentAgainst = "Agency Tax";
            } elseif ($wfworkflowMasterId == $this->_hording) {
                $mAdvHoarding = new AdvHoarding();
                $paymentDetails = $mAdvHoarding->getPaymentDetails($req->paymentId);
                $paymentDetails->ulbLogo = $this->_ulbLogoUrl . $paymentDetails->ulbLogo;
                $paymentDetails->inWords = getIndianCurrency($paymentDetails->payment_amount) . " Only /-";
                $paymentDetails->paymentAgainst = "Hoarding Tax";
            }

            // Get Market Payment Details
            elseif ($wfworkflowMasterId == $this->_banquetHall) {
                $mMarBanquteHall = new MarBanquteHall();
                $paymentDetails = $mMarBanquteHall->getPaymentDetails($req->paymentId);
                $paymentDetails->ulbLogo = $this->_ulbLogoUrl . $paymentDetails->ulbLogo;
                $paymentDetails->inWords = getIndianCurrency($paymentDetails->payment_amount) . " Only /-";
                $paymentDetails->paymentAgainst = "Marriage / Banquet Hall Tax";
            } elseif ($wfworkflowMasterId == $this->_hostel) {
                $mMarHostel = new MarHostel();
                $paymentDetails = $mMarHostel->getPaymentDetails($req->paymentId);
                $paymentDetails->ulbLogo = $this->_ulbLogoUrl . $paymentDetails->ulbLogo;
                $paymentDetails->inWords = getIndianCurrency($paymentDetails->payment_amount) . " Only /-";
                $paymentDetails->paymentAgainst = "Hostel Tax";
            } elseif ($wfworkflowMasterId == $this->_lodge) {
                $mMarLodge = new MarLodge();
                $paymentDetails = $mMarLodge->getPaymentDetails($req->paymentId);
                $paymentDetails->ulbLogo = $this->_ulbLogoUrl . $paymentDetails->ulbLogo;
                $paymentDetails->inWords = getIndianCurrency($paymentDetails->payment_amount) . " Only /-";
                $paymentDetails->paymentAgainst = "Lodge Tax";
            } elseif ($wfworkflowMasterId == $this->_dharamshala) {
                $mMarDharamshala = new MarDharamshala();
                $paymentDetails = $mMarDharamshala->getPaymentDetails($req->paymentId);
                $paymentDetails->ulbLogo = $this->_ulbLogoUrl . $paymentDetails->ulbLogo;
                $paymentDetails->inWords = getIndianCurrency($paymentDetails->payment_amount) . " Only /-";
                $paymentDetails->paymentAgainst = "Dharamshala Tax";
            }
            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            if (empty($paymentDetails)) {
                throw new Exception("Payment Details Not Found By Given Paymenst Id !!!");
            } else {
                return responseMsgs(true, 'Data Fetched',  $paymentDetails, "050206", "1.0", "$executionTime Sec", "POST", $req->deviceId);
            }
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", '050206', 01, "", 'POST', $req->deviceId);
        }
    }


    /**
     * | Advertisement Dashboard
     * | Function - 07
     */
    public function advertDashboard(Request $req)
    {
        // return $req;
        $userDetails = authUserDetails($req);
        $ulbId = $userDetails['ulb_id'];
        try {
            // Variable initialization
            $startTime = microtime(true);

            $mAdvActiveSelfadvertisement = new AdvActiveSelfadvertisement();
            $pendingList = $mAdvActiveSelfadvertisement->allPendingList()->where('ulb_id', $ulbId)->values();              // Find Self Advertisement Approve Applications
            $advert['selfPendingApplications'] = $pendingList;

            $madvSelfAdvertisement = new AdvSelfadvertisement();
            $approveList = $madvSelfAdvertisement->allApproveList()->where('ulb_id', $ulbId)->values();              // Find Self Advertisement Approve Applications
            $advert['selfApprovedApplications'] = $approveList;

            $mAdvRejectedSelfadvertisement = new AdvRejectedSelfadvertisement();
            $rejectList = $mAdvRejectedSelfadvertisement->rejectedApplication()->where('ulb_id', $ulbId)->values();  // Find Self Advertisement Rejected Applications
            $advert['selfRejectedApplications'] = $rejectList;

            $mAdvActivePrivateland = new AdvActivePrivateland();
            $pendingList = $mAdvActivePrivateland->allPendingList()->where('ulb_id', $ulbId)->values();                 // Find Pvt Land Approve Applications
            $advert['pvtLandPendingApplications'] = $pendingList;

            $mAdvPrivateland = new AdvPrivateland();
            $pvtapproveList = $mAdvPrivateland->allApproveList()->where('ulb_id', $ulbId)->values();                 // Find Pvt Land Approve Applications
            $advert['pvtLandApprovedApplications'] = $pvtapproveList;

            $mAdvRejectedPrivateland = new AdvRejectedPrivateland();
            $pvtRejectList = $mAdvRejectedPrivateland->rejectedApplication()->where('ulb_id', $ulbId)->values();     // Find Pvt Land Rejected Applications
            $advert['pvtLandRejectedApplications'] = $pvtRejectList;

            $mAdvActiveVehicle = new AdvActiveVehicle();
            $pendingList = $mAdvActiveVehicle->allPendingList()->where('ulb_id', $ulbId)->values();                // Find Vehicle Approve Applications
            $advert['vehiclePendingApplications'] = $pendingList;

            $mAdvVehicle = new AdvVehicle();
            $vehicleApproveList = $mAdvVehicle->allApproveList()->where('ulb_id', $ulbId)->values();                // Find Vehicle Approve Applications
            $advert['vehicleApprovedApplications'] = $vehicleApproveList;

            $mAdvRejectedVehicle = new AdvRejectedVehicle();
            $vehicleRejectList = $mAdvRejectedVehicle->rejectedApplication()->where('ulb_id', $ulbId)->values();    // Find Vehicle Rejected Applications
            $advert['vehicleRejectedApplications'] = $vehicleRejectList;

            $mAdvActiveAgency = new AdvActiveAgency();
            $pendingList = $mAdvActiveAgency->allPendingList()->where('ulb_id', $ulbId)->values();                  // Find Agency Approve Applications
            $advert['agencyPendingApplications'] = $pendingList;

            $mAdvAgency = new AdvAgency();
            $agencyApproveList = $mAdvAgency->allApproveList()->where('ulb_id', $ulbId)->values();                  // Find Agency Approve Applications
            $advert['agencyApprovedApplications'] = $agencyApproveList;

            $mAdvRejectedAgency = new AdvRejectedAgency();
            $agencyRejectList = $mAdvRejectedAgency->rejectedApplication()->where('ulb_id', $ulbId)->values();      // Find Agency Rejected Applications
            $advert['agencyRejectedApplications'] = $agencyRejectList;

            $mAdvActiveHoarding = new AdvActiveHoarding();
            $hoardingPendingList = $mAdvActiveHoarding->allPendingList()->where('ulb_id', $ulbId)->values();              // Find Hoarding Approve Applications
            $advert['hoardingPendingApplications'] = $hoardingPendingList;

            $mAdvHoarding = new AdvHoarding();
            $hoardingApproveList = $mAdvHoarding->allApproveList()->where('ulb_id', $ulbId)->values();              // Find Hoarding Approve Applications
            $advert['hoardingApprovedApplications'] = $hoardingApproveList;

            $mAdvRejectedHoarding = new AdvRejectedHoarding();
            $hoardingRejectList = $mAdvRejectedHoarding->rejectedApplication()->where('ulb_id', $ulbId)->values();  // Find Hoarding Rejected Applications
            $advert['hoardingRejectedApplications'] = $hoardingRejectList;

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;

            return responseMsgs(true, 'Data Fetched',  $advert, "050207", "1.0", "$executionTime Sec", "POST");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", '050207', 01, "", 'POST', '');
        }
    }

    /**
     * | Market Dashboard
     * | Function - 08
     */
    public function marketDashboard(Request $req)
    {
        $userDetails = authUserDetails($req);
        $ulbId = $userDetails['ulb_id'];
        try {
            // Variable initialization
            $startTime = microtime(true);

            $mMarActiveBanquteHall = new MarActiveBanquteHall();
            $pendingList = $mMarActiveBanquteHall->allPendingList()->where('ulb_id', $ulbId)->values();                              // Find Banquet Hall Approve Applications
            $market['banquetPendingApplications'] = $pendingList;

            $mMarBanquteHall = new MarBanquteHall();
            $approveList = $mMarBanquteHall->allApproveList()->where('ulb_id', $ulbId)->values();                              // Find Banquet Hall Approve Applications
            $market['banquetApprovedApplications'] = $approveList;

            $mMarRejectedBanquteHall = new MarRejectedBanquteHall();
            $rejectList = $mMarRejectedBanquteHall->rejectedApplication()->where('ulb_id', $ulbId)->values();                  // Find Banquet Hall Rejected Applications
            $market['banquetRejectedApplications'] = $rejectList;

            $mMarActiveHostel = new MarActiveHostel();
            $hostelPendingList = $mMarActiveHostel->allPendingList()->where('ulb_id', $ulbId)->values();                             // Find Hostel Approve Applications
            $market['hostelPendingApplications'] = $hostelPendingList;

            $mMarHostel = new MarHostel();
            $hostelapproveList = $mMarHostel->allApproveList()->where('ulb_id', $ulbId)->values();                             // Find Hostel Approve Applications
            $market['hostelApprovedApplications'] = $hostelapproveList;

            $mMarRejectedHostel = new MarRejectedHostel();
            $hostelRejectList = $mMarRejectedHostel->rejectedApplication()->where('ulb_id', $ulbId)->values();                 // Find Hostel Rejected Applications
            $market['hostelRejectedApplications'] = $hostelRejectList;

            $mMarActiveLodge = new MarActiveLodge();
            $lodgePendingList = $mMarActiveLodge->allPendingList()->where('ulb_id', $ulbId)->values();                               // Find Lodge Approve Applications
            $market['lodgePendingApplications'] = $lodgePendingList;

            $mMarLodge = new MarLodge();
            $lodgeApproveList = $mMarLodge->allApproveList()->where('ulb_id', $ulbId)->values();                               // Find Lodge Approve Applications
            $market['lodgeApprovedApplications'] = $lodgeApproveList;

            $mMarRejectedLodge = new MarRejectedLodge();
            $lodgeRejectList = $mMarRejectedLodge->rejectedApplication()->where('ulb_id', $ulbId)->values();                   // Find Lodge Rejected Applications
            $market['lodgeRejectedApplications'] = $lodgeRejectList;

            $mMarActiveDharamshala = new MarActiveDharamshala();
            $dharamshalaPendingList = $mMarActiveDharamshala->allPendingList()->where('ulb_id', $ulbId)->values();                  // Find Dharamshala Approve Applications
            $market['dharamshalaPendingApplications'] = $dharamshalaPendingList;

            $mMarDharamshala = new MarDharamshala();
            $dharamshalaApproveList = $mMarDharamshala->allApproveList()->where('ulb_id', $ulbId)->values();                  // Find Dharamshala Approve Applications
            $market['dharamshalaApprovedApplications'] = $dharamshalaApproveList;

            $mMarRejectedDharamshala = new MarRejectedDharamshala();
            $dharamshalaRejectList = $mMarRejectedDharamshala->rejectedApplication()->where('ulb_id', $ulbId)->values();      // Find Dharamshala Rejected Applications
            $market['dharamshalaRejectedApplications'] = $dharamshalaRejectList;

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;

            return responseMsgs(true, 'Data Fetched',  $market, "050208", "1.0", "$executionTime Sec", "POST");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", '050208', 01, "", 'POST', '');
        }
    }

    
    /**
     * | Get Advertisement Dashboard
     * | Function - 09
     */
    public function advertisementDashboard(Request $req)
    {
        try {
            $dashboardReport['date']=Carbon::now()->format('d-m-Y');
            $advert = $this->advertDashboard($req)->original['data'];
            $market = $this->marketDashboard($req)->original['data'];
            $dashboardReport['selfApproved']=$advert['selfApprovedApplications']->where('date',$dashboardReport['date'])->count();
            $dashboardReport['selfPending']=$advert['selfPendingApplications']->where('date',$dashboardReport['date'])->count();
            $dashboardReport['selfRejected']=$advert['selfRejectedApplications']->where('date',$dashboardReport['date'])->count();
            
            $dashboardReport['plApproved']=$advert['pvtLandApprovedApplications']->where('date',$dashboardReport['date'])->count();
            $dashboardReport['plPending']=$advert['pvtLandPendingApplications']->where('date',$dashboardReport['date'])->count();
            $dashboardReport['plRejected']=$advert['pvtLandRejectedApplications']->where('date',$dashboardReport['date'])->count();
            
            $dashboardReport['vclApproved']=$advert['vehicleApprovedApplications']->where('date',$dashboardReport['date'])->count();
            $dashboardReport['vclPending']=$advert['vehiclePendingApplications']->where('date',$dashboardReport['date'])->count();
            $dashboardReport['vclRejected']=$advert['vehicleRejectedApplications']->where('date',$dashboardReport['date'])->count();
            
            $dashboardReport['agApproved']=$advert['agencyApprovedApplications']->where('date',$dashboardReport['date'])->count();
            $dashboardReport['agPending']=$advert['agencyPendingApplications']->where('date',$dashboardReport['date'])->count();
            $dashboardReport['agRejected']=$advert['agencyRejectedApplications']->where('date',$dashboardReport['date'])->count();
            
            $dashboardReport['horApproved']=$advert['hoardingApprovedApplications']->where('date',$dashboardReport['date'])->count();
            $dashboardReport['horPending']=$advert['hoardingPendingApplications']->where('date',$dashboardReport['date'])->count();
            $dashboardReport['horRejected']=$advert['hoardingRejectedApplications']->where('date',$dashboardReport['date'])->count();
            
            $dashboardReport['bqApproved']=$market['banquetApprovedApplications']->where('date',$dashboardReport['date'])->count();
            $dashboardReport['bqPending']=$market['banquetPendingApplications']->where('date',$dashboardReport['date'])->count();
            $dashboardReport['bqRejected']=$market['banquetRejectedApplications']->where('date',$dashboardReport['date'])->count();
            
            $dashboardReport['hsApproved']=$market['hostelApprovedApplications']->where('date',$dashboardReport['date'])->count();
            $dashboardReport['hsPending']=$market['hostelPendingApplications']->where('date',$dashboardReport['date'])->count();
            $dashboardReport['hsRejected']=$market['hostelRejectedApplications']->where('date',$dashboardReport['date'])->count();
            
            $dashboardReport['ldApproved']=$market['lodgeApprovedApplications']->where('date',$dashboardReport['date'])->count();
            $dashboardReport['ldPending']=$market['lodgePendingApplications']->where('date',$dashboardReport['date'])->count();
            $dashboardReport['ldRejected']=$market['lodgeRejectedApplications']->where('date',$dashboardReport['date'])->count();
            
            $dashboardReport['dsApproved']=$market['dharamshalaApprovedApplications']->where('date',$dashboardReport['date'])->count();
            $dashboardReport['dsPending']=$market['dharamshalaPendingApplications']->where('date',$dashboardReport['date'])->count();
            $dashboardReport['dsRejected']=$market['dharamshalaRejectedApplications']->where('date',$dashboardReport['date'])->count();

            $dashboardReport['totalAdvertApproved']=$dashboardReport['selfApproved'] + $dashboardReport['plApproved'] + $dashboardReport['vclApproved'] + $dashboardReport['agApproved'] + $dashboardReport['horApproved'];
            $dashboardReport['totalAdvertPending']=$dashboardReport['selfPending'] + $dashboardReport['plPending'] + $dashboardReport['vclPending'] + $dashboardReport['agPending'] + $dashboardReport['horPending'];
            $dashboardReport['totalAdvertRejected']=$dashboardReport['selfRejected'] + $dashboardReport['plRejected'] + $dashboardReport['vclRejected'] + $dashboardReport['agRejected'] + $dashboardReport['horRejected'];
            $dashboardReport['totalAdvertApplication']=$dashboardReport['totalAdvertApproved'] + $dashboardReport['totalAdvertPending'] + $dashboardReport['totalAdvertRejected'];

            $dashboardReport['totalMarketApproved']=$dashboardReport['bqApproved'] + $dashboardReport['hsApproved'] + $dashboardReport['ldApproved'] + $dashboardReport['dsApproved'];
            $dashboardReport['totalMarketPending']=$dashboardReport['bqPending'] + $dashboardReport['hsPending'] + $dashboardReport['ldPending'] + $dashboardReport['dsPending'];
            $dashboardReport['totalMarketRejected']=$dashboardReport['bqRejected'] + $dashboardReport['hsRejected'] + $dashboardReport['ldRejected'] + $dashboardReport['dsRejected'];
            $dashboardReport['totalMarketApplication']=$dashboardReport['totalMarketApproved'] + $dashboardReport['totalMarketPending'] + $dashboardReport['totalMarketRejected'];

            $dashboardReport['date']=Carbon::now()->format('d-m-Y');

            // $dashboardReport['advert']=$advert;
            // $dashboardReport['market']=$market;
            return responseMsgs(true, "Application Fetched Successfully", $dashboardReport, "050209", 1.0, responseTime(), "POST", "", "");
        } catch (Exception $e) {
            return responseMsgs(false, "Application Not Fetched", $e->getMessage(), "050209", 1.0, "", "POST", "", "");
        }
    }

    /**
     * | All Application Search application by name or mobile
     * | Function - 10
     */
    public function searchByNameorMobile(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'filterBy' => 'required|in:mobileNo,entityName,ownerName',
            'parameter' => $req->filterBy == 'mobileNo' ? 'required|digits:10' : 'required|string',
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            // Variable initialization
            $startTime = microtime(true);

            $madvSelfAdvertisement = new AdvSelfadvertisement();
            $approveList = collect($madvSelfAdvertisement->allApproveList());              // Find Self Advertisement Approve Applications


            $mAdvPrivateland = new AdvPrivateland();
            $pvtapproveList = collect($mAdvPrivateland->allApproveList());                 // Find Pvt Land Approve Applications
            $merged = $approveList->merge($pvtapproveList);

            $mAdvVehicle = new AdvVehicle();
            $vehicleApproveList = $mAdvVehicle->allApproveList();                // Find Vehicle Approve Applications
            $merged = $merged->merge($vehicleApproveList);


            $mAdvAgency = new AdvAgency();
            $agencyApproveList = $mAdvAgency->allApproveList();                  // Find Agency Approve Applications
            $merged = $merged->merge($agencyApproveList);

            $mAdvHoarding = new AdvHoarding();
            $hoardingApproveList = $mAdvHoarding->allApproveList();              // Find Hoarding Approve Applications
            $merged = $merged->merge($hoardingApproveList);

            $mMarBanquteHall = new MarBanquteHall();
            $banquetApproveList = $mMarBanquteHall->allApproveList();              // Find Banquet Hall Approve Applications
            $merged = $merged->merge($banquetApproveList);

            $mMarLodge = new MarLodge();
            $lodgeApproveList = $mMarLodge->allApproveList();              // Find Lodge Approve Applications
            $merged = $merged->merge($lodgeApproveList);

            $mMarHostel = new MarHostel();
            $hostelApproveList = $mMarHostel->allApproveList();              // Find Hostel Approve Applications
            $merged = $merged->merge($hostelApproveList);

            $mMarDharamshala = new MarDharamshala();
            $dharamshalaApproveList = $mMarDharamshala->allApproveList();              // Find Dharamshala Approve Applications
            $merged = $merged->merge($dharamshalaApproveList);


            // $merged = $merged->where('payment_staus', '0');
            if ($req->filterBy == 'mobileNo') {
                $merged = $merged->where('mobile_no', $req->parameter);
            }
            if ($req->filterBy == 'entityName') {
                $merged = $merged->where('entity_name', $req->parameter);
            }
            if ($req->filterBy == 'ownerName') {
                $merged = $merged->where('owner_name', $req->parameter);
            }

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;

            return responseMsgs(true, "Application Fetched Successfully", $merged->values(), "050210", 1.0, "$executionTime Sec", "POST", "", "");
        } catch (Exception $e) {
            return responseMsgs(false, "Application Not Fetched", $e->getMessage(), "050210", 1.0, "", "POST", "", "");
        }
    }


    /**
     * | Get Payment Details For Application
     * | Function - 11
     */
    public function getApprovalLetter(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'applicationId' => 'required|integer',
            'workflowId' => 'required|integer'
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            // Variable initialization
            $startTime = microtime(true);
            $wfworkflowMasterId = $this->getWorkflowMasterId($req->workflowId);
            // Get Advertesement Reciept Details
            if ($wfworkflowMasterId == $this->_selfAdvt) {
                $mAdvSelfadvertisement = new AdvSelfadvertisement();
                $recieptDetails = $mAdvSelfadvertisement->getApprovalLetter($req->applicationId);
            } elseif ($wfworkflowMasterId == $this->_pvtLand) {
                $mAdvPrivateland = new AdvPrivateland();
                $recieptDetails = $mAdvPrivateland->getApprovalLetter($req->applicationId);
            } elseif ($wfworkflowMasterId ==  $this->_movableVehicle) {
                $mAdvVehicle = new AdvVehicle();
                $recieptDetails = $mAdvVehicle->getApprovalLetter($req->applicationId);
            } elseif ($wfworkflowMasterId == $this->_agency) {
                $mAdvAgency = new AdvAgency();
                $recieptDetails = $mAdvAgency->getApprovalLetter($req->applicationId);
            } elseif ($wfworkflowMasterId == $this->_hording) {
                $mAdvHoarding = new AdvHoarding();
                $recieptDetails = $mAdvHoarding->getApprovalLetter($req->applicationId);
            }

            //Created On : 23/6/2023,  Changes By - Anchal
            elseif ($wfworkflowMasterId == $this->_banquetHall) {
                $mMarBanquteHall = new MarBanquteHall();
                $recieptDetails = $mMarBanquteHall->getApprovalLetter($req->applicationId);
            } elseif ($wfworkflowMasterId == $this->_dharamshala) {
                $mMarDharamshala = new MarDharamshala();
                $recieptDetails = $mMarDharamshala->getApprovalLetter($req->applicationId);
            } elseif ($wfworkflowMasterId == $this->_hostel) {
                $mMarHostel = new MarHostel();
                $recieptDetails = $mMarHostel->getApprovalLetter($req->applicationId);
            } elseif ($wfworkflowMasterId == $this->_lodge) {
                $mMarLodge = new MarLodge();
                $recieptDetails = $mMarLodge->getApprovalLetter($req->applicationId);
            }


            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;

            return responseMsgs(true, "Approval Fetched Successfully !!", $recieptDetails, "050211", 1.0, "$executionTime Sec", "POST", "", "");
        } catch (Exception $e) {
            return responseMsgs(false, "Approval Not Fetched", $e->getMessage(), "050211", 1.0, "271ms", "POST", "", "");
        }
    }


    public function sendWhatsAppNotification(WhatsappServiceInterface $notification_service)
    {
        $notification_service->sendWhatsappNotification();
    }

    /**
     * | Get Financial year Master data
     * | Function - 12
     */
    public function getFinancialMasterData(Request $req)
    {
        try {
            $startTime = microtime(true);
            $financialYear = RefAdvParamstring::select('id', 'string_parameter')->where('param_category_id', '1017')->orderBy('string_parameter')->get();
            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            return responseMsgs(true, "Approval Fetched Successfully !!", $financialYear, "050212", 1.0, "$executionTime Sec", "POST", "", "");
        } catch (Exception $e) {
            return responseMsgs(false, "Approval Not Fetched", $e->getMessage(), "050212", 1.0, "271ms", "POST", "", "");
        }
    }


    /**
     * | Get Workflow Master Id 
     * | Function - 13
     */
    public function getWorkflowMasterId($workflowId)
    {
        return WfWorkflow::select('wf_master_id')->where('id', $workflowId)->first()->wf_master_id;
    }
}
