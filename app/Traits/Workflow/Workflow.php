<?php

namespace App\Traits\Workflow;

use App\Models\Workflows\WfRole;
use App\Models\Workflows\WfRoleusermap;
use App\Models\Workflows\WfWardUser;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * | Created By : Sam Kerketta
 * | Created On : 19-06-2023
 * | Trait for Workflow 
 */

trait Workflow
{
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
                    WHERE w.is_finisher=TRUE 
                ";
        return $query;
    }

    public function getUserRoll($user_id, $ulb_id, $workflow_id)
    {
        try {
            // DB::enableQueryLog();
            $data = WfRole::select(
                DB::raw(
                    "wf_roles.id as role_id,wf_roles.role_name,
                                            wf_workflowrolemaps.is_initiator, wf_workflowrolemaps.is_finisher,
                                            wf_workflowrolemaps.forward_role_id,forword.role_name as forword_name,
                                            wf_workflowrolemaps.backward_role_id,backword.role_name as backword_name,
                                            wf_workflowrolemaps.allow_full_list,wf_workflowrolemaps.can_escalate,
                                            wf_workflowrolemaps.serial_no,wf_workflowrolemaps.is_btc,
                                            wf_workflowrolemaps.can_upload_document,
                                            wf_workflowrolemaps.can_verify_document,
                                            wf_workflowrolemaps.can_backward,
                                            wf_workflows.id as workflow_id,wf_masters.workflow_name,
                                            ulb_masters.id as ulb_id, ulb_masters.ulb_name,
                                            ulb_masters.ulb_type"
                )
            )
                ->join("wf_roleusermaps", function ($join) {
                    $join->on("wf_roleusermaps.wf_role_id", "=", "wf_roles.id")
                        ->where("wf_roleusermaps.is_suspended", "=", FALSE);
                })
                ->join("users", "users.id", "=", "wf_roleusermaps.user_id")
                ->join("wf_workflowrolemaps", function ($join) {
                    $join->on("wf_workflowrolemaps.wf_role_id", "=", "wf_roleusermaps.wf_role_id")
                        ->where("wf_workflowrolemaps.is_suspended", "=", FALSE);
                })
                ->leftjoin("wf_roles AS forword", "forword.id", "=", "wf_workflowrolemaps.forward_role_id")
                ->leftjoin("wf_roles AS backword", "backword.id", "=", "wf_workflowrolemaps.backward_role_id")
                ->join("wf_workflows", function ($join) {
                    $join->on("wf_workflows.id", "=", "wf_workflowrolemaps.workflow_id")
                        ->where("wf_workflows.is_suspended", "=", FALSE);
                })
                ->join("wf_masters", function ($join) {
                    $join->on("wf_masters.id", "=", "wf_workflows.wf_master_id")
                        ->where("wf_masters.is_suspended", "=", FALSE);
                })
                ->join("ulb_masters", "ulb_masters.id", "=", "wf_workflows.ulb_id")
                ->where("wf_roles.is_suspended", false)
                ->where("wf_roleusermaps.user_id", $user_id)
                ->where("wf_workflows.ulb_id", $ulb_id)
                ->where("wf_workflows.wf_master_id", $workflow_id)
                ->orderBy("wf_roleusermaps.id", "desc")
                ->first();
            // dd(DB::getQueryLog());
            return $data;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
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
}
