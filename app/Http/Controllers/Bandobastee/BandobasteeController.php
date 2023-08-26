<?php

namespace App\Http\Controllers\Bandobastee;

use App\BLL\Advert\CalculateRate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bandobastee\StoreRequest;
use App\Models\Bandobastee\BdBanquetHall;
use App\Models\Bandobastee\BdBazar;
use App\Models\Bandobastee\BdMaster;
use App\Models\Bandobastee\BdPanalty;
use App\Models\Bandobastee\BdPanaltyMaster;
use App\Models\Bandobastee\BdParking;
use App\Models\Bandobastee\BdPayment;
use App\Models\Bandobastee\BdPenaltyMaster;
use App\Models\Bandobastee\BdSettler;
use App\Models\Bandobastee\BdSettlerTransaction;
use App\Models\Bandobastee\BdStand;
use App\Models\Bandobastee\BdStandCategory;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;

 /**
     * | Created On - 26-04-2023
     * | Created By- Bikash Kumar 
     * | Created for the Bandobastee
     * | Status - Closed, By Bikash on 10 May 2023,  Total no. of lines - 610, Total Function - 16, Total API - 15
     */

class BandobasteeController extends Controller
{
    protected $_gstAmt;
    protected $_tcsAmt;
    //Constructor
    public function __construct()
    {
        $this->_gstAmt = Config::get('workflow-constants.GST_AMT');
        $this->_tcsAmt = Config::get('workflow-constants.TCS_AMT');
    }

    /**
     * | List of Masters Data of Bandobastee
     * | Function - 01
     * | API - 01
     */
    public function bandobasteeMaster(Request $req)
    {
        try {
            // Variable initialization
            $mUlbId = $req->auth['ulb_id'];
            if ($mUlbId == '')
                throw new Exception("You Are Not Authorished !!!");
            $data = array();
            $mBdstand = new Bdstand();
            $strings = $mBdstand->masters($mUlbId);
            $data['bandobasteeCategories'] = remove_null($strings->groupBy('stand_category')->toArray());

            $mBdStandCategory = new BdStandCategory();
            $listCategory = $mBdStandCategory->listCategory();                  // Get Topology List
            $data['bandobasteeCategories']['Stand'] = $listCategory;

            $mBdMaster = new BdMaster();
            $listMaster = $mBdMaster->listMaster();                             // Get Bandobastee List
            $data['bandobasteeCategories']['BandobasteeType'] = $listMaster;

            return responseMsgs(true, "Bandobastee List", $data, "051101", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "051101", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }


    /**
     * | String Parameters values
     * | @param request $req
     * | Function - 02
     * | API - 02
     */
    public function getStandCategory(Request $req)
    {
        try {
            // Variable initialization
            $mBdStandCategory = new BdStandCategory();
            $listCategory = $mBdStandCategory->listCategory();
            return responseMsgs(true, "Category List", $listCategory, "051102", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "051102", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Get Stand List
     * | Function - 03
     * | API - 03
     */
    public function getStands(Request $req)
    {
        $mUlbId = $req->auth['ulb_id'];
        if ($mUlbId == '')
            throw new Exception("You Are Not Authorished !!!");

        $validator = Validator::make($req->all(), [
            'categoryId' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return $validator->errors();
            // return ['status' => false, 'message' =>$validator->errors()]

        }
        try {
            // Variable initialization
            $mBdStand = new BdStand();
            $listStands = $mBdStand->listStands($req->categoryId, $mUlbId);
            return responseMsgs(true, "Stand List", $listStands, "051103", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "051103", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Add New Bandobastee
     * | Function - 04
     * | API - 04
     */
    public function addNew(StoreRequest $req)
    {
        if ($req->auth['ulb_id'] == '')
            return responseMsgs(false, "Not Allowed", 'You Are Not Authorized !!', "050834", 1.0, "271ms", "POST", "", "");
        else
            $ulbId = $req->auth['ulb_id'];

        try {
            // Variable initialization
            $mBdSettler = new BdSettler();
            $mCalculateRate = new CalculateRate;
            $gst = $mCalculateRate->calculateAmount($req->baseAmount, $this->_gstAmt);          // Calculate GST Amount From BLL
            $gstAmt = ['gstAmt' => $gst];
            $req->merge($gstAmt);

            $ulbId = ['ulbId' => $ulbId];
            $req->merge($ulbId);

            $tcs = $mCalculateRate->calculateAmount($req->baseAmount, $this->_tcsAmt);          // Calculate TCS Amount From BLL
            $tcsAmt = ['tcsAmt' => $tcs];
            $req->merge($tcsAmt);

            $totalAmount = ['totalAmount' => ($tcs + $gst + $req->baseAmount)];                 // Calculate Total Amount
            $req->merge($totalAmount);

            DB::beginTransaction();
            $mBdSettler->addNew($req);       //<--------------- Model function to store 
            DB::commit();

            return responseMsgs(true, "Successfully Accepted !!", '', "0511045", "1.0", responseTime(), 'POST', $req->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "051104", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }

    /**
     * | Get Penalty Lisrt Master
     * | Function - 05
     * | API - 05
     */
    public function listPenalty(Request $req)
    {
        try {
            // Variable initialization
            $mBdPenaltyMaster = new BdPenaltyMaster();
            $listPanalty = $mBdPenaltyMaster->listPenalty();
            return responseMsgs(true, "Category List", $listPanalty, "051105", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "051105", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }


    /**
     * | Get Stand Settler List
     * | Function - 06
     * | API - 06
     */
    public function listSettler(Request $req)
    {
        if ($req->auth['ulb_id'] == '')
            return responseMsgs(false, "Not Allowed", 'You Are Not Authorized !!', "051106", 1.0, "271ms", "POST", "", "");
        else
            $ulbId = $req->auth['ulb_id'];
        try {
            // Variable initialization
            $mBdSettler = new BdSettler();
            $listSettler = $mBdSettler->listSettler($ulbId);
            $list = $listSettler->map(function ($settler) {
                $totalAmt = $this->totalInstallment($settler->id);
                $settler->paid_amount = $totalAmt['installment_amount'];
                $settler->performance_security_amount = $totalAmt['performance_security_amount'];
                $settler->due_amount = $settler->total_amount - ($totalAmt['installment_amount'] + $settler->emd_amount);
                $settler->total_penalty = $totalAmt['total_penalty'];
                $settler->rest_performance_security = ($totalAmt['performance_security_amount'] - $totalAmt['total_penalty']);
                return $settler;
            });
            return responseMsgs(true, "Stand Settler List", $list, "051106", "1.0",responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "051106", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Calculate All type of price
     * | Function - 07
     */
    public function totalInstallment($id)
    {
        $mBdPayment = new BdPayment();
        $priceList['installment_amount'] = $mBdPayment->totalInstallment($id);
        $mBdSettlerTransaction = new BdSettlerTransaction();
        $priceList['performance_security_amount'] = $mBdSettlerTransaction->performanceSecurity($id, "false");
        $priceList['total_penalty'] = $mBdSettlerTransaction->performanceSecurity($id, "true");
        // print_r($priceList); die;
        return $priceList;
    }

    /**
     * | Settler Installment Payment
     * | Function - 08
     * | API - 07
     */
    public function installmentPayment(Request $req)
    {
        $validator = Validator::make($req->all(), [
            // 'ulbId' => 'required|integer',
            'settlerId' => 'required|integer',
            'installmentAmount' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }

        if ($req->auth['ulb_id'] == '')
            return responseMsgs(false, "Not Allowed", 'You Are Not Authorized !!', "051107", 1.0, "271ms", "POST", "", "");
        else
            $ulbId = $req->auth['ulb_id'];
        try {
            // Variable initialization
            $mBdPayment = new BdPayment();
            $mBdStand = BdSettler::find($req->settlerId);
            $ulbId = ['ulbId' => $mBdStand->ulb_id];
            $req->request->add($ulbId);

            $installmentDate = ['installmentDate' => Carbon::now()->format('Y-m-d')];
            $req->request->add($installmentDate);
            // return $req;
            $listSettler = $mBdPayment->installmentPayment($req);
            return responseMsgs(true, "Payment Successfully", '', "051107", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "051107", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }


    /**
     * | Get Settler Installment Payment List
     * | Function - 09
     * | API - 08
     */
    public function listInstallmentPayment(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'settlerId' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }

        if ($req->auth['ulb_id'] == '')
            return responseMsgs(false, "Not Allowed", 'You Are Not Authorized !!', "051108", 1.0, "271ms", "POST", "", "");
        else
            $ulbId = $req->auth['ulb_id'];
        try {
            // Variable initialization
            $mBdPayment = new BdPayment();

            $listInstallment = $mBdPayment->listInstallmentPayment($req->settlerId)->map(function ($val) {
                $val->installment_date = Carbon::parse($val->installment_date)->format('d-m-Y');
                return $val;
            });
            return responseMsgs(true, "Payment Successfully", $listInstallment, "051108", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "051108", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Get Bandobastee Type
     * | Function - 10
     * | API - 09
     */
    public function getBandobasteeCategory(Request $req)
    {
        if ($req->auth['ulb_id'] == '')
            return responseMsgs(false, "Not Allowed", 'You Are Not Authorized !!', "051109", 1.0, "271ms", "POST", "", "");
        else
            $ulbId = $req->auth['ulb_id'];
        try {
            // Variable initialization
            $mBdMaster = new BdMaster();

            $list = $mBdMaster->listMaster();
            return responseMsgs(true, "Bandobastee List", $list, "051109", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "051109", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Add Penalty and performamnce Security Money
     * | Function - 11
     * | API - 10
     */
    public function addPenaltyOrPerformanceSecurity(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'settlerId' => 'required|integer',
            'amount' => 'required|numeric',
            'isPenalty' => 'required|boolean',
            'remarks' => 'nullable|string',
            'penaltyType' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            // Variable initialization
            $mBdSettlerTransaction = new BdSettlerTransaction();

            $mBdStand = BdSettler::find($req->settlerId);
            $ulbId = ['ulbId' => $mBdStand->ulb_id];
            $req->request->add($ulbId);

            $res = $mBdSettlerTransaction->addTransaction($req);

            return responseMsgs(true, "Added Successfully", "", "051110", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "051110", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }


    /**
     * | Get Settler Transaction 
     * | Function - 12
     * | API - 11
     */
    public function listSettlerTransaction(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'settlerId' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return ['status' => false, 'message' => $validator->errors()];
        }
        try {
            // Variable initialization
            $mBdSettlerTransaction = new BdSettlerTransaction();

            $credit = 0;
            $debit = 0;
            $list = $mBdSettlerTransaction->listSettlerTransaction($req->settlerId);
            $ps = collect();
            $pty = collect();
            foreach ($list as $l) {
                if ($l['is_penalty'] == NULL) {
                    $credit += $l['amount'];
                    $ps->push($l);
                } else {
                    $debit += $l['amount'];
                    $pty->push($l);
                }
            }
            $availableBalance = $credit - $debit;
            // return $ps->first()->amount;
            return responseMsgs(true, "Settler Transaction Details", ['performanceSecurityAmt' => $ps->first()->amount, 'penalty' => $pty, 'availableBalance' => $availableBalance], "051111", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "051111", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }


    /**
     * | Get Parking List
     * | Function - 13
     * | API - 12
     */
    public function listParking(Request $req)
    {
        if ($req->auth['ulb_id'] == '')
            return responseMsgs(false, "Not Allowed", 'You Are Not Authorized !!', "051112", 1.0, "271ms", "POST", "", "");
        else
            $ulbId = $req->auth['ulb_id'];
        try {
            // Variable initialization
            $mBdParking = new BdParking();

            $list = $mBdParking->listParking($ulbId);
            return responseMsgs(true, "Parking List", $list, "051112", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "051112", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Get Parking Settler List
     * | Function - 14
     * | API - 13
     */
    public function listParkingSettler(Request $req)
    {
        if ($req->auth['ulb_id'] == '')
            return responseMsgs(false, "Not Allowed", 'You Are Not Authorized !!', "051113", 1.0, "271ms", "POST", "", "");
        else
            $ulbId = $req->auth['ulb_id'];
        try {
            // Variable initialization
            $mBdSettler = new BdSettler();

            $listSettler = $mBdSettler->listParkingSettler($ulbId);
            $list = $listSettler->map(function ($settler) {
                $totalAmt = $this->totalInstallment($settler->id);
                $settler->paid_amount = $totalAmt['installment_amount'];
                $settler->performance_security_amount = $totalAmt['performance_security_amount'];
                $settler->due_amount = $settler->total_amount - ($totalAmt['installment_amount'] + $settler->emd_amount);
                $settler->total_penalty = $totalAmt['total_penalty'];
                $settler->rest_performance_security = ($totalAmt['performance_security_amount'] - $totalAmt['total_penalty']);
                return $settler;
            });
            return responseMsgs(true, "Parking Settler List", $list, "051113", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "051113", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Get Bazar List
     * | Function - 15
     * | API - 14
     */
    public function listBazar(Request $req)
    {
        if ($req->auth['ulb_id'] == '')
            return responseMsgs(false, "Not Allowed", 'You Are Not Authorized !!', "051114", 1.0, "271ms", "POST", "", "");
        else
            $ulbId = $req->auth['ulb_id'];
        try {
            // Variable initialization
            $mBdBazar = new BdBazar();

            $list = $mBdBazar->listBazar($ulbId);
            return responseMsgs(true, "Bazar List", $list, "051114", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "051114", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Get Bazar Settler List
     * | Function - 16
     * | API - 15
     */
    public function listBazarSettler(Request $req)
    {
        if ($req->auth['ulb_id'] == '')
            return responseMsgs(false, "Not Allowed", 'You Are Not Authorized !!', "051115", 1.0, "271ms", "POST", "", "");
        else
            $ulbId = $req->auth['ulb_id'];
        try {
            // Variable initialization
            $mBdSettler = new BdSettler();
            $listSettler = $mBdSettler->listBazarSettler($ulbId);
            $list = $listSettler->map(function ($settler) {
                $totalAmt = $this->totalInstallment($settler->id);
                $settler->paid_amount = $totalAmt['installment_amount'];
                $settler->performance_security_amount = $totalAmt['performance_security_amount'];
                $settler->due_amount = $settler->total_amount - ($totalAmt['installment_amount'] + $settler->emd_amount);
                $settler->total_penalty = $totalAmt['total_penalty'];
                $settler->rest_performance_security = ($totalAmt['performance_security_amount'] - $totalAmt['total_penalty']);
                return $settler;
            });
            return responseMsgs(true, "Bazar Settler List", $list, "051115", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "051115", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }


    /**
     * | Get Banquet Hall List
     */
    public function listBanquetHall(Request $req)
    {
        if ($req->auth['ulb_id'] == '')
            return responseMsgs(false, "Not Allowed", 'You Are Not Authorized !!', "050834", 1.0, "271ms", "POST", "", "");
        else
            $ulbId = $req->auth['ulb_id'];
        try {
            // Variable initialization
            $mBdBanquetHall = new BdBanquetHall();
            $list = $mBdBanquetHall->listBanquetHall($ulbId);
            return responseMsgs(true, "Data Fetch Successfully", $list, "050201", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050201", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Get Banquet Hall Settler List
     */
    public function listBanquetHallSettler(Request $req)
    {
        if ($req->auth['ulb_id'] == '')
            return responseMsgs(false, "Not Allowed", 'You Are Not Authorized !!', "050834", 1.0, "271ms", "POST", "", "");
        else
            $ulbId =$req->auth['ulb_id'];
        try {
            // Variable initialization
            $mBdSettler = new BdSettler();
            $list = $mBdSettler->listBanquetHallSettler($ulbId);
            return responseMsgs(true, "Data Fetch Successfully", $list, "050201", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050201", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }


    // public function listSettler1(Request $req)
    // {
    //     if (authUser()->ulb_id == '')
    //         return responseMsgs(false, "Not Allowed", 'You Are Not Authorized !!', "050834", 1.0, "271ms", "POST", "", "");
    //     else
    //         $ulbId = authUser()->ulb_id;
    //     try {
    //         // Variable initialization
    //         $startTime = microtime(true);
    //         $mBdSettler = new BdSettler();
    //         $listSettler = $mBdSettler->listSettler($ulbId);
    //         // $listSettler = $listSettler->where('ulb_id', $ulbId);
    //         $endTime = microtime(true);
    //         $executionTime = $endTime - $startTime;
    //         return responseMsgs(true, "Settler List", $listSettler, "050201", "1.0", "$executionTime Sec", "POST", $req->deviceId ?? "");
    //     } catch (Exception $e) {
    //         return responseMsgs(false, $e->getMessage(), "", "050201", "1.0", "", "POST", $req->deviceId ?? "");
    //     }
    // }
}