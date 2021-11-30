<?php
$data = simplexml_load_string(file_get_contents("http://data.vroute.net/services/online.php?fir=EPWW"));

$online = array();
foreach($data->atcs->record as $atc)
{
    array_push($online, array(
        "callsign" => $atc->callsign,
        "name" => $atc->name,
        "cid" => $atc->cid,
        "freq" => $atc->freq
    ));
}
file_put_contents("onlineAtc.json", json_encode($online));
