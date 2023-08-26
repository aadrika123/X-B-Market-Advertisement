<?php

namespace App\Models\Workflows;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WfWorkflowrolemap extends Model
{
    use HasFactory;

    /**
     * | Get Ulb Workflows By Role Ids
     */
    public function getWfByRoleId($roleIds)
    {
        return WfWorkflowrolemap::select('workflow_id')
            ->whereIn('wf_role_id', $roleIds)
            ->get();
    }

    /**
     * | Get Workflow Forward and Backward Ids
     */
    public function getWfBackForwardIds($req)
    {
        return WfWorkflowrolemap::select('forward_role_id', 'backward_role_id')
            ->where('workflow_id', $req->workflowId)
            ->where('wf_role_id', $req->roleId)
            ->where('is_suspended', false)
            ->firstOrFail();
    }
}
