<?php

/**
 * | Created On-14-02-2022 
 * | Created By-Anshu Kumar
 * | Created for- Payment Constants Masters
 */
return [
    'PAYMENT_MODE' => [
        '1' => 'ONLINE',
        '2' => 'NETBANKING',
        '3' => 'CASH',
        '4' => 'CHEQUE',
        '5' => 'DD',
        '6' => 'NEFT',
        '7' => 'ONLINE_R',
    ],
    
    "EASEBUZZ_ENV"=>env("EASEBUZZ_ENV","test"),
    "EASEBUZZ_SALT"=>env("EASEBUZZ_SALT","RDBCE6SNO"),
    "EASEBUZZ_MERCHANT_KEY"=>env("EASEBUZZ_MERCHANT_KEY","BFTG4OT2L"),
];
