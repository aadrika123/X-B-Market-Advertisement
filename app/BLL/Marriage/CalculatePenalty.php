<?php

namespace App\BLL\Marriage;

use Carbon\Carbon;

class CalculatePenalty
{
    public function calculate($req)
    {
        $todayDate = Carbon::now();
        $marriageDate = $req->marriageDate;
        $penalty = 0;

        $dayDiffrence = dateDiff($marriageDate, $todayDate);

        if ($dayDiffrence > 365) {
            $totalDay = $dayDiffrence - 365;
            $penalty = $totalDay * 5;

            if ($penalty > 100)
                $penalty = 100;
            else
                $penalty = $penalty;
        }
        return $penalty;
    }
}
