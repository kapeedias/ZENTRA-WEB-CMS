<?php
/* -----------------------------------------------------------
     * TIMEZONE HELPERS
     * ----------------------------------------------------------- */
function toUTC($datetime, $timezone)
{
    try {
        $tz = new DateTimeZone($timezone);
        $dt = new DateTime($datetime, $tz);
        $dt->setTimezone(new DateTimeZone('UTC'));
        return $dt->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        return null;
    }
}
function fromUTC($datetime, $timezone)
{
    try {
        $dt = new DateTime($datetime, new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone($timezone));
        return $dt->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        return null;
    }
}
