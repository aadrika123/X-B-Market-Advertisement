<?php

namespace App\Traits;

use App\Models\Markets\MarketPriceMstrs;
use App\Models\Workflows\WfRole;
use App\Models\Workflows\WfRoleusermap;
use App\Models\Workflows\WfWardUser;
use Illuminate\Support\Facades\Config;
use App\Models\Workflows\WfWorkflow;
use App\Models\Workflows\WfWorkflowrolemap;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * | Workflow Masters Trait
 */

trait WorkflowTrait
{
    /**
     * | Get Ulb Workflow Id By Ulb Id
     * | @param Bearer bearer token from request
     * | @param ulbId 
     * | @param workflowId 
     */
    public function getUlbWorkflowId($bearer, $ulbId, $wfMasterId)
    {
        $baseUrl = Config::get('constants.AUTH_URL');
        $workflows = Http::withHeaders([
            "Authorization" => "Bearer $bearer",
            "contentType" => "application/json"

        ])->post($baseUrl . 'api/workflow/get-ulb-workflow', [
            "ulbId" => $ulbId,
            "workflowMstrId" => $wfMasterId
        ])->json();
        return $workflows;
    }

    /**
     * | Get Roles by Logged In user Id
     * | @param userId Logged In UserId
     */
    public function getRoleByUserId($userId)
    {
          $roles = WfRoleusermap::select('id', 'wf_role_id', 'user_id')
            ->where('user_id', $userId)
            ->where('is_suspended', false)
            ->get();
        return $roles;
    }

     /**
     * | get Ward By Logged in User Id
     * -------------------------------------------
     * | @param userId > Current Logged In User Id
     */
    public function getWardByUserId($userId)
    {
        $occupiedWard = WfWardUser::select('id', 'ward_id')
            ->where('user_id', $userId)
            ->get();
        return $occupiedWard;
    }

         /**
     * | get workflow role Id by logged in User Id
     * -------------------------------------------
     * @param userId > current Logged in User
     */
    public function getRoleIdByUserId($userId)
    {
        $roles = WfRoleusermap::select('id', 'wf_role_id', 'user_id')
            ->where('user_id', $userId)
            ->where('is_suspended', false)
            ->get();
        return $roles;
    }
    public function getRole($request)
    {
        $userId = authUser($request)->id;
        // DB::enableQueryLog();
        $role = WfRoleusermap::select(
            'wf_workflowrolemaps.*',
            'wf_roleusermaps.user_id'
        )
            ->join('wf_workflowrolemaps', 'wf_workflowrolemaps.wf_role_id', 'wf_roleusermaps.wf_role_id')
            ->where('user_id', $userId)
            ->where('wf_workflowrolemaps.workflow_id', $request->workflowId)
            ->first();
        // return (DB::getQueryLog());

        return remove_null($role);
    }
     /**
     * | Get Initiator Id While Sending to level Pending For the First Time
     * | @param mixed $wfWorkflowId > Workflow Id of Modules
     * | @var string $query
     */
    public function getInitiatorId(int $wfWorkflowId)
    {
        $query = "SELECT 
                    r.id AS role_id,
                    r.role_name AS role_name,
                    w.forward_role_id
                    FROM wf_roles r
                    INNER JOIN (SELECT * FROM wf_workflowrolemaps WHERE workflow_id=$wfWorkflowId) w ON w.wf_role_id=r.id
                    WHERE w.is_initiator=TRUE 
                    ";
        return $query;
    }


    /**
     * | Get Finisher Id while approve or reject application
     * | @param wfWorkflowId ulb workflow id 
     */
    public function getFinisherId(int $wfWorkflowId)
    {
        $query = "SELECT 
                    r.id AS role_id,
                    r.role_name AS role_name 
                    FROM wf_roles r
                    INNER JOIN (SELECT * FROM wf_workflowrolemaps WHERE workflow_id=$wfWorkflowId) w ON w.wf_role_id=r.id
                    WHERE w.is_finisher=TRUE ";
        return $query;
    }
}
