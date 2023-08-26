<?php

namespace App\Http\Controllers\Advertisements;

use App\Http\Controllers\Controller;
use App\Models\Advertisements\AdvActiveAgency;
use App\Models\Advertisements\AdvActivePrivateland;
use App\Models\Advertisements\AdvActiveSelfadvertisement;
use App\Models\Advertisements\AdvActiveVehicle;
use App\Models\Advertisements\AdvAgency;
use App\Models\Advertisements\AdvPrivateland;
use App\Models\Advertisements\AdvRejectedAgency;
use App\Models\Advertisements\AdvRejectedPrivateland;
use App\Models\Advertisements\AdvRejectedSelfadvertisement;
use App\Models\Advertisements\AdvRejectedVehicle;
use App\Models\Advertisements\AdvSelfadvertisement;
use App\Models\Advertisements\AdvVehicle;
use App\Models\Markets\MarActiveBanquteHall;
use App\Models\Markets\MarActiveDharamshala;
use App\Models\Markets\MarActiveHostel;
use App\Models\Markets\MarActiveLodge;
use App\Models\Markets\MarBanquteHall;
use App\Models\Markets\MarDharamshala;
use App\Models\Markets\MarHostel;
use App\Models\Markets\MarLodge;
use App\Models\Markets\MarRejectedBanquteHall;
use App\Models\Markets\MarRejectedDharamshala;
use App\Models\Markets\MarRejectedHostel;
use App\Models\Markets\MarRejectedLodge;
use Exception;
use Illuminate\Http\Request;


/**
 * | Controller - Search Controller
 * | Created By - Bikash Kumar
 * | Date - 07 Aug 2023
 * | Status - Open
 */
class SearchController extends Controller
{
    /**
     * | Advertisement Records List Applied Application, List Approved Application, List Rejected Application
     * | Function - 01
     * | API - 01
     */
    public function listAllAdvertisementRecords(Request $req)
    {
        try {
            // Variable Initialization
            $citizenId = $req->auth['id'];
            $userType = $req->auth['user_type'];

            $mAdvSelfadvertisements = new AdvSelfadvertisement();
            $applications = $mAdvSelfadvertisements->listApproved($citizenId, $userType);                         // Self Advertisement Approved List
            $data1['self']['listApproved'] = $applications;

            $selfAdvets = new AdvActiveSelfadvertisement();
            $applications = $selfAdvets->listAppliedApplications($citizenId);                                    //<-------  Get Applied Applications of Self Advertisements
            $data1['self']['listApplied'] = $applications;

            $mAdvRejectedSelfadvertisement = new AdvRejectedSelfadvertisement();
            $applications = $mAdvRejectedSelfadvertisement->listRejected($citizenId);                            // List Rejected Application Self Advertisements
            $data1['self']['listRejected'] = $applications;

            $mAdvVehicle = new AdvVehicle();
            $applications = $mAdvVehicle->listApproved($citizenId, $userType);                                  //  List Applied Application of Vehicle Advertisements
            $data1['vehicle']['listApproved'] = $applications;

            $mvehicleAdvets = new AdvActiveVehicle();
            $applications = $mvehicleAdvets->listAppliedApplications($citizenId);                                //  Approved List Application of Vehicle Advertisements
            $data1['vehicle']['listApplied'] = $applications;

            $mAdvRejectedVehicle = new AdvRejectedVehicle();
            $applications = $mAdvRejectedVehicle->listRejected($citizenId);                                      // List Rejected Application Vehicle Advertisements
            $data1['vehicle']['listRejected'] = $applications;

            $mAdvPrivateland = new AdvPrivateland();
            $applications = $mAdvPrivateland->listApproved($citizenId, $userType);                                //  List Approved Application of Private Land Advertisements
            $data1['privateLand']['listApproved'] = $applications;

            $mAdvActivePrivateland = new AdvActivePrivateland();
            $applications = $mAdvActivePrivateland->listAppliedApplications($citizenId);                          // List Applied Application of Private Land
            $data1['privateLand']['listApplied'] = $applications;

            $mAdvRejectedPrivateland = new AdvRejectedPrivateland();
            $applications = $mAdvRejectedPrivateland->listRejected($citizenId);                                  // List Rejected Application Private Land Advertisements
            $data1['privateLand']['listRejected'] = $applications;

            $mAdvAgency = new AdvAgency();
            $applications = $mAdvAgency->listApproved($citizenId, $userType);                                   //  List Approved Application of Agency
            $data1['agency']['listApproved'] = $applications;

            $mAdvActiveAgency = new AdvActiveAgency();
            $applications = $mAdvActiveAgency->listAppliedApplications($citizenId);                             //  List Applied Application of Agency
            $data1['agency']['listApplied'] = $applications;

            $mAdvRejectedAgency = new AdvRejectedAgency();
            $applications = $mAdvRejectedAgency->listRejected($citizenId);                                     //  List Rejected Application of Agency
            $data1['agency']['listRejected'] = $applications;

            return responseMsgs(true, "Records Fetch Successfully !!!", $data1, "050120", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050120", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }


    /**
     * | Market Records List Applied Application, List Approved Application, List Rejected Application
     * | Function - 02
     * | API - 02
     */
    public function listAllMarketRecords(Request $req)
    {
        try {
            // Variable initialization
            $citizenId = $req->auth['id'];
            $userType = $req->auth['user_type'];

            $mMarBanquteHall = new MarBanquteHall();
            $applications = $mMarBanquteHall->listApproved($citizenId, $userType);                                                // List Approved Application of Banquest/Marriage Hall
            $data1['banquet']['listApproved'] = $applications;

            $mMarActiveBanquteHall = new MarActiveBanquteHall();
            $applications = $mMarActiveBanquteHall->listAppliedApplications($citizenId);                                         // List Applied Application of Banquet/Marriage Hall
            $data1['banquet']['listApplied'] = $applications;

            $mMarRejectedBanquteHall = new MarRejectedBanquteHall();
            $applications = $mMarRejectedBanquteHall->listRejected($citizenId);                                                  // List Rejected Application of Banquet/Marriage Hall
            $data1['banquet']['listRejected']= $applications;

            $mMarDharamshala = new MarDharamshala();
            $applications = $mMarDharamshala->listApproved($citizenId, $userType);                                              // List Approved Application of Dharmashala
            $data1['dharmashala']['listApproved']= $applications;

            $mMarActiveDharamshala = new MarActiveDharamshala();
            $applications = $mMarActiveDharamshala->listAppliedApplications($citizenId);                                        // List Applied Application of Dharmashala
            $data1['dharmashala']['listApplied']= $applications;

            $mMarRejectedDharamshala = new MarRejectedDharamshala();
            $applications = $mMarRejectedDharamshala->listRejected($citizenId);                                                 // List Rejected Application of Dharamshala
            $data1['dharmashala']['listRejected'] = $applications;

            $mMarHostel = new MarHostel();
            $applications = $mMarHostel->listApproved($citizenId, $userType);                                                  // List Approved Application of Hostel
            $data1['hostel']['listApproved'] = $applications;

            $mMarActiveHostel =new MarActiveHostel();
            $applications = $mMarActiveHostel->listAppliedApplications($citizenId);                                            // List Application of Hostel
            $data1['hostel']['listApplied'] = $applications;

            $mMarRejectedHostel = new MarRejectedHostel();
            $applications = $mMarRejectedHostel->listRejected($citizenId);                                                     // List Rejected Application of Hostel
            $data1['hostel']['listRejected'] = $applications;

            $mMarLodge = new MarLodge();
            $applications = $mMarLodge->listApproved($citizenId, $userType);                                                   // List Approved Application of Lodge 
            $data1['lodge']['listApproved'] = $applications;

            $mMarActiveLodge = new MarActiveLodge;
            $applications = $mMarActiveLodge->listAppliedApplications($citizenId);                                             // List Applied Application of Lodge
            $data1['lodge']['listApplied'] = $applications;

            $mMarRejectedLodge = new MarRejectedLodge();
            $applications = $mMarRejectedLodge->listRejected($citizenId);                                                      // List Rejected Application of Lodge
            $data1['lodge']['listRejected'] = $applications;

         return responseMsgs(true, "Records Fetch Successfully !!!", $data1, "050120", "1.0", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "050120", "1.0", "", 'POST', $req->deviceId ?? "");
        }
    }
}
