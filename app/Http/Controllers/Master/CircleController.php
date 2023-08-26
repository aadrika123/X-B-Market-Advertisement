<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Master\MCircle;
use Illuminate\Support\Facades\Validator;
use Exception;

class CircleController extends Controller
{
    private $_mCircle;

    public function __construct()
    {
        $this->_mCircle = new MCircle();
    }

    // Add records
    public function store(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'circleName' => 'required|string'
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);

        try {
            $exists = $this->_mCircle->getCircleNameByUlbId($req->circleName, $req->auth['ulb_id']);
            if (collect($exists)->isNotEmpty())
                throw new Exception("Circle According To Ulb Id Already Existing");

            $metaReqs = [
                'circle_name' => $req->circleName,
                'ulb_id' => $req->auth['ulb_id']
            ];

            $this->_mCircle->create($metaReqs);
            return responseMsgs(true, "Successfully Saved", [$metaReqs], "055201", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055201", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        }
    }

    // Edit Records
    public function edit(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'id'         => 'required|integer',
            'circleName' => 'required|string'
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);

        try {
            $exists = $this->_mCircle->getCircleNameByUlbId($req->circleName, $req->auth['ulb_id']);
            if (collect($exists) && $exists->where('id', '!=', $req->id)->isNotEmpty())
                throw new Exception("Circle According To Ulb Id Already Existing");

            $metaReqs = [
                'circle_name' => $req->circleName,
                'ulb_id' => $req->auth['ulb_id']
            ];

            $circle = $this->_mCircle->findOrFail($req->id);
            $circle->update($metaReqs);
            return responseMsgs(true, "Successfully Saved", [$metaReqs], "055202", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055202", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        }
    }

    //find by Ulb Id
    public function getCircleByUlb(Request $req)
    {
        try {
            $circle = $this->_mCircle->getCircleByUlbId($req->auth['ulb_id']);
            if (collect($circle)->isEmpty())
                throw new Exception("No Data Found");
            return responseMsgs(true, "", $circle, "055203", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055203", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    //retireve all records
    public function retireveAll(Request $req)
    {
        try {
            $circle = $this->_mCircle->getAllActive();
            if (collect($circle)->isEmpty())
                throw new Exception("No Data Found");
            return responseMsgs(true, "", $circle, "055204", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055204", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    //delete records
    public function delete(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'id'  => 'required|integer',
            'isActive' => 'required|bool'
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);

        try {
            if (isset($req->isActive)) {
                $isActive = $req->isActive == false ? 0 : 1;
                $metaReqs = [
                    'is_active' => $isActive
                ];
            }
            $Shops = $this->_mCircle::findOrFail($req->id);
            $Shops->update($metaReqs);
            return responseMsgs(true, "Status Updated Successfully", [], "055205", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "055205", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }
}
