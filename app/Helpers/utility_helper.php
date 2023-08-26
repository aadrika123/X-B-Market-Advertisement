<?php

/**
 * | Created On-15-12-2022 
 * | Created By-Anshu Kumar
 * | Helper Functions
 */

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

if (!function_exists("responseMsg")) {
    function responseMsg($status, $message, $data)
    {
        $response = ['status' => $status, "message" => $message, "data" => $data];
        return response()->json($response, 200);
    }
}

/**
 * | Response Msg Version2 with apiMetaData
 */
if (!function_exists("responseMsgs")) {
    function responseMsgs($status, $msg, $data, $apiId = null, $version = null, $queryRunTime = null, $action = null, $deviceId = null)
    {
        return response()->json([
            'status' => $status,
            'message' => $msg,
            'meta-data' => [
                'apiId' => $apiId,
                'version' => $version,
                'responseTime' => $queryRunTime,
                'epoch' => Carbon::now()->format('Y-m-d H:i:m'),
                'action' => $action,
                'deviceId' => $deviceId
            ],
            'data' => $data
        ]);
    }
}

if (!function_exists("print_var")) {
    function print_var($data = '')
    {
        echo "<pre>";
        print_r($data);
        echo ("</pre>");
    }
}

if (!function_exists("objToArray")) {
    function objToArray(object $data)
    {
        $arrays = $data->toArray();
        return $arrays;
    }
}

if (!function_exists('remove_null')) {
    function remove_null($data)
    {
        if (is_object($data)) {
            $filtered = collect($data)->map(function ($val) {
                if (is_null($val)) {
                    $val = '';
                }
                return $val;
            });
            return $filtered;
        }

        $filtered = collect($data)->map(function ($value) {
            return collect($value)->map(function ($val) {
                if (is_array($val) || $val instanceof stdClass) {   // Check the function is in array form or std class
                    return collect($val)->map(function ($vals) {
                        if (is_null($vals)) {
                            $vals = '';
                        }
                        return $vals;
                    });
                }

                if (is_null($val)) {
                    $val = '';
                }
                return $val;
            });
        });
        return $filtered;
    }
}


if (!function_exists('getIndianCurrency')) {
    // function getIndianCurrency(float $number)
    // {
    //     $decimal = round($number - ($no = floor($number)), 2) * 100;
    //     $hundred = null;
    //     $digits_length = strlen($no);
    //     $i = 0;
    //     $str = array();
    //     $words = array(
    //         0 => '', 1 => 'One', 2 => 'Two',
    //         3 => 'Three', 4 => 'Four', 5 => 'Five', 6 => 'Six',
    //         7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
    //         10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve',
    //         13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen',
    //         16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen',
    //         19 => 'Nineteen', 20 => 'Twenty', 30 => 'Thirty',
    //         40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty',
    //         70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety'
    //     );
    //     $digits = array('', 'hundred', 'thousand', 'lakh', 'crore');
    //     while ($i < $digits_length) {
    //         $divider = ($i == 2) ? 10 : 100;
    //         $number = floor($no % $divider);
    //         $no = floor($no / $divider);
    //         $i += $divider == 10 ? 1 : 2;
    //         if ($number) {
    //             $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
    //             $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
    //             $str[] = ($number < 21) ? $words[$number] . ' ' . $digits[$counter] . $plural . ' ' . $hundred : $words[floor($number / 10) * 10] . ' ' . $words[$number % 10] . ' ' . $digits[$counter] . $plural . ' ' . $hundred;
    //         } else $str[] = null;
    //     }
    //     $Rupees = implode('', array_reverse($str));
    //     $paise = ($decimal > 0) ? "." . ($words[$decimal / 10] . " " . $words[$decimal % 10]) . ' Paise' : '';
    //     return ($Rupees ? $Rupees . 'Rupees ' : 'Zero Rupee') . $paise;
    // }
    function getIndianCurrency(float $number)
    {
        $decimal = round($number - ($no = floor($number)), 2) * 100;
        $decimal_part = $decimal;
        $hundred = null;
        $hundreds = null;
        $digits_length = strlen($no);
        $decimal_length = strlen($decimal);
        $i = 0;
        $str = array();
        $str2 = array();
        $words = array(
            0 => '', 1 => 'one', 2 => 'two',
            3 => 'three', 4 => 'four', 5 => 'five', 6 => 'six',
            7 => 'seven', 8 => 'eight', 9 => 'nine',
            10 => 'ten', 11 => 'eleven', 12 => 'twelve',
            13 => 'thirteen', 14 => 'fourteen', 15 => 'fifteen',
            16 => 'sixteen', 17 => 'seventeen', 18 => 'eighteen',
            19 => 'nineteen', 20 => 'twenty', 30 => 'thirty',
            40 => 'forty', 50 => 'fifty', 60 => 'sixty',
            70 => 'seventy', 80 => 'eighty', 90 => 'ninety'
        );
        $digits = array('', 'hundred', 'thousand', 'lakh', 'crore');

        while ($i < $digits_length) {
            $divider = ($i == 2) ? 10 : 100;
            $number = floor($no % $divider);
            $no = floor($no / $divider);
            $i += $divider == 10 ? 1 : 2;
            if ($number) {
                $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
                $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
                $str[] = ($number < 21) ? $words[$number] . ' ' . $digits[$counter] . $plural . ' ' . $hundred : $words[floor($number / 10) * 10] . ' ' . $words[$number % 10] . ' ' . $digits[$counter] . $plural . ' ' . $hundred;
            } else $str[] = null;
        }

        $d = 0;
        while ($d < $decimal_length) {
            $divider = ($d == 2) ? 10 : 100;
            $decimal_number = floor($decimal % $divider);
            $decimal = floor($decimal / $divider);
            $d += $divider == 10 ? 1 : 2;
            if ($decimal_number) {
                $plurals = (($counter = count($str2)) && $decimal_number > 9) ? 's' : null;
                $hundreds = ($counter == 1 && $str2[0]) ? ' and ' : null;
                @$str2[] = ($decimal_number < 21) ? $words[$decimal_number] . ' ' . $digits[$decimal_number] . $plural . ' ' . $hundred : $words[floor($decimal_number / 10) * 10] . ' ' . $words[$decimal_number % 10] . ' ' . $digits[$counter] . $plural . ' ' . $hundred;
            } else $str2[] = null;
        }

        $Rupees = implode('', array_reverse($str));
        $paise = implode('', array_reverse($str2));
        $paise = ($decimal_part > 0) ? $paise . ' Paise' : '';
        return ucfirst(($Rupees ? $Rupees . ' Rupees' : '')) . $paise;
    }
}

// get days from two dates
if (!function_exists('dateDiff')) {
    function dateDiff(string $date1, string $date2)
    {
        $date1 = Carbon::parse($date1);
        $date2 = Carbon::parse($date2);

        return $date1->diffInDays($date2);
    }
}


// get days from two months
if (!function_exists('monthDiff')) {
    function monthDiff(string $date1, string $date2)
    {
        $date1 = Carbon::parse($date1);
        $date2 = Carbon::parse($date2);

        return $date1->diffInMonths($date2);
    }
}

// Get Authenticated users list
if (!function_exists('authUser')) {
    function authUser($req)
    {
        $auth = $req->auth;
        if (!$auth)
            throw new Exception("Auth Not Available");
        if (is_array($auth))
            return (object)$auth;
        else
            return json_decode($req->auth);
    }
}

/**
 * | To throw Validation Error
 */
if (!function_exists("validationError")) {
    function validationError($validator)
    {
        return responseMsg(false, 'Validation Error', $validator->errors()->all());
    }
}

// getClientIpAddress
if (!function_exists('getClientIpAddress')) {
    function getClientIpAddress()
    {
        // Get real visitor IP behind CloudFlare network
        if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
            $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
            $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
        }

        // Sometimes the `HTTP_CLIENT_IP` can be used by proxy servers
        $ip = @$_SERVER['HTTP_CLIENT_IP'];
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }

        // Sometimes the `HTTP_X_FORWARDED_FOR` can contain more than IPs 
        $forward_ips = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        if ($forward_ips) {
            $all_ips = explode(',', $forward_ips);

            foreach ($all_ips as $ip) {
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'];
    }

    /**
     * | Flip Constants
     */
    if (!function_exists('flipConstants')) {
        function flipConstants($constant)
        {
            $chunk = collect($constant)->chunk(1);
            $flip = $chunk->map(function ($a) {
                return collect($a)->flip();
            });
            $flip = $flip->collapse();
            return $flip;
        }
    }
    /**
     * | Api Response time for the the apis
     */

    if (!function_exists("responseTime")) {
        function responseTime()
        {
            $responseTime = (microtime(true) - LARAVEL_START) * 1000;
            return round($responseTime, 2) . " ms";
        }
    }

    /**
     * | Api Response time for the the apis
     */

    if (!function_exists("paginator")) {
        function paginator($orm, $req)
        {
            $perPage = $req->perPage ? $req->perPage :  10;
            $paginator = $orm->paginate($perPage);
            return [
                "current_page" => $paginator->currentPage(),
                "last_page" => $paginator->lastPage(),
                "data" => $paginator->items(),
                "total" => $paginator->total(),
            ];
        }
    }


    /**
     * | All Data Filter According to Key
     */

    if (!function_exists("searchFilter")) {
        function searchFilter($orm, $req)
        {
            $key = trim($req->key);
            return $orm->where(function ($query) use ($key) {
                $query->orwhere('application_no', 'ILIKE', '%' . $key . '%')
                    ->orwhere("applicant", 'ILIKE', '%' . $key . '%')
                    // ->orwhere(DB::raw("TO_CHAR(application_date, 'DD/MM/YYYY')"), 'ILIKE', '%' . $key . '%')
                    ->orwhere("entity_name", 'ILIKE', '%' . $key . '%');
            });
        }
    }

    /**
     * | Get Financial Year
     */

    if (!function_exists('getFinancialYear')) {
        function getFinancialYear($date)
        {
            $Year = date('Y', strtotime($date));
            $year = date('y', strtotime($date));
            $month = date('m', strtotime($date));
            if ($month <= 3) {
                return ($Year - 1) . '-' . $year;
            } else {
                return $Year . '-' . ($year + 1);
            }
        }
    }


    /**
     * | Api Response time for the the apis
     */
    if (!function_exists('authUserDetails')) {
        function authUserDetails($req)
        {
            return $req->auth;
        }
    }



    /**
     * | Search Filter for Shop Rental Data
     */

    if (!function_exists("searchShopRentalFilter")) {
        function searchShopRentalFilter($orm, $req)
        {
            $key = trim($req->key);
            return $orm->where(function ($query) use ($key) {
                $query->orwhere('shop_no', 'ILIKE', '%' . $key . '%')
                    ->orwhere("allottee", 'ILIKE', '%' . $key . '%');
                // ->orwhere("vendor_name", 'ILIKE', '%' . $key . '%')
                // ->orwhere("toll_no", 'ILIKE', '%' . $key . '%');
            });
        }
    }



    /**
     * | Search Filter for Shop Rental Data
     */

    if (!function_exists("searchTollRentalFilter")) {
        function searchTollRentalFilter($orm, $req)
        {
            $key = trim($req->key);
            return $orm->where(function ($query) use ($key) {
                // $query->orwhere('shop_no', 'ILIKE', '%' . $key . '%')
                // ->orwhere("allottee", 'ILIKE', '%' . $key . '%');
                $query->orwhere("vendor_name", 'ILIKE', '%' . $key . '%')
                    ->orwhere("toll_no", 'ILIKE', '%' . $key . '%');
            });
        }
    }
}
