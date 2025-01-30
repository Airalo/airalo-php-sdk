<?php

namespace Airalo\Helpers;


use DateTime;

final class Date
{
    /**
     * @param $date
     * @param $format
     * @return bool
     */
    public static function validateDate(string $date, string $format = 'Y-m-d'): bool
    {
        $date = DateTime::createFromFormat($format, $date);
        if (!$date) {
            return false;
        }
        return true;
    }
}