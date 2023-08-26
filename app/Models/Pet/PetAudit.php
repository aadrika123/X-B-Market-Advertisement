<?php

namespace App\Models\Pet;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PetAudit extends Model
{
    use HasFactory;

    /**
     * | Save Audit data  
     */
    public function saveAuditData($req, $tableName)
    {
        $mPetAudit = new PetAudit();
        $mPetAudit->json_data = $req;
        $mPetAudit->table_name = $tableName;
        $mPetAudit->save();
    }
}
