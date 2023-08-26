<?php

/**
 * | Config Constants for master parameter for Pet Module
 * | Created by: Sam Kerketta
 * | Created on: 14-06-2023
 */

return [
    "MASTER_DATA" => [
        "OWNER_TYPE_MST" => [
            "Owner"     => 1,
            "Tenant"    => 2
        ],
        "PET_GENDER" => [
            "Male"      => 1,
            "Female"    => 2
        ],
        "REGISTRATION_THROUGH" =>
        [
            "Holding" => 1,
            "Saf"     => 2
        ],
    ],
    "API_END_POINTS" => [
        "get_prop_detils" => 229,
    ],
    "HTTP_HEADERS" => [
        "JSON" => "application/json",
    ],
    "PROP_TYPE" => [
        "VACANT_LAND" => 4
    ],
    "PROP_OCCUPANCY_TYPE" => [
        1 =>  "SELF_OCCUPIED",
        2 =>  "TENANTED",
    ],
    "WORKFLOW_MASTER_ID" => 31,
    "PET_MODULE_ID" => 9,
    "ROLE_LABEL" => [
        "BO" => 11,
        "DA" => 6,
        "SI" => 9
    ],
    "APPLY_MODE" =>
    [
        "ONLINE"    => 1,
        "JSK"       => 2,
    ],
    "REF_USER_TYPE" => [
        "1" => "Citizen",
        "2" => "JSK",
        "3" => "TC",
        "4" => "Pseudo",
        "5" => "Employee"
    ],
    "PARAM_ID" => [
        "REGISTRATION" => 34,
        "TRANSACTION" => 37
    ],
    "PET_RELATIVE_PATH" => [
        "REGISTRATION" => 'Uploads/Pet/Application',
    ],
    "DOC_REQ_CATAGORY" => [
        "1" => "R",
        "2" => "OR",
        "3" => "O"
    ],
    "DB_KEYS" => [
        "1" => "citizen_id",
    ],
    "FEE_CHARGES" => [
        "REGISTRATION_RENEWAL" => 1
    ],
    "APPLICATION_TYPE" => [                                       // related to TRANSACTION_TYPE
        "NEW_APPLY" => 1,
        "RENEWAL"   => 2
    ],

    'PAYMENT_MODE' => [
        '1' => 'ONLINE',
        '3' => 'CASH',
        '4' => 'CHEQUE',
        '5' => 'DD',
        '6' => 'NEFT'
    ],

    "TRANSACTION_TYPE" => [                                     // Realted to APPLICATION_TYPE                          
        "New_Apply" => 1,
        "Renewal" => 2
    ],

    "PET_TYPE" => [
        "DOG" => 1,
        "CAT" => 2
    ],
    "VERIFICATION_PAYMENT_MODES" => [           // The Verification payment modes which needs the verification
        "CHEQUE",
        "DD",
        "NEFT"
    ],
    "OFFLINE_PAYMENT_MODE" => [
        "CHEQUE",
        "DD",
        "NEFT",
        "CASH"
    ],
    "API_KEY_PAYMENT" => "eff41ef6-d430-4887-aa55-9fcf46c72c99",
    "END_POINT_PAYMENT" => "api/payment/generate-orderid",

    "TABLE_NAME" => [
        "1" => "pet_active_details",
    ],
];
