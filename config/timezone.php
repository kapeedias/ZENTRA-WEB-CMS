<?php

function toUTC(string $datetime, string $timezone): string
{
    $dt = new DateTime($datetime, new DateTimeZone($timezone));
    $dt->setTimezone(new DateTimeZone('UTC'));
    return $dt->format('Y-m-d H:i:s');
}

function fromUTC(string $datetime, string $timezone): string
{
    $dt = new DateTime($datetime, new DateTimeZone('UTC'));
    $dt->setTimezone(new DateTimeZone($timezone));
    return $dt->format('Y-m-d H:i:s');
}
