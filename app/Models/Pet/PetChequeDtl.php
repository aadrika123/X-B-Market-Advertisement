<?php

namespace App\Models\Pet;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PetChequeDtl extends Model
{
    use HasFactory;

    /**
     * | Save the cheque details 
     */
    public function postChequeDtl($req)
    {
        $mPetChequeDtl = new PetChequeDtl();
        $mPetChequeDtl->application_id     =  $req['application_id'] ?? null;
        $mPetChequeDtl->transaction_id     =  $req['transaction_id'];
        $mPetChequeDtl->cheque_date        =  $req['cheque_date'];
        $mPetChequeDtl->bank_name          =  $req['bank_name'];
        $mPetChequeDtl->branch_name        =  $req['branch_name'];
        $mPetChequeDtl->cheque_no          =  $req['cheque_no'];
        $mPetChequeDtl->user_id            =  $req['user_id'];
        $mPetChequeDtl->save();
    }
}
