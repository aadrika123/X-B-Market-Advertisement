<?php

namespace App\MicroServices;

use App\MicroServices\IdGenerator\PrefixIdGenerator;
use Illuminate\Support\Facades\Config;

class IdGeneration
{
    public function generateTransactionNo($ulbId)
    {
        $tranParamId = Config::get("constants.PARAM_IDS");
        $idGeneration = new PrefixIdGenerator($tranParamId['TRN'], $ulbId);
        $transactionNo = $idGeneration->generate();
        $transactionNo = str_replace('/', '-', $transactionNo);
        return $transactionNo;
    }
}
