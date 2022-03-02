<?php
/**
 * vATIS v1.0
 * @author Maksymilian Makow (ACCPL5) with usage of vacchun-atis functions
 * @date 09.03.2021
 */

require_once 'MetarDecoder.inc.php';
use MetarDecoder\MetarDecoder;
include_once ('onlinePositions.php');


function getTime($time)
{
    $time = str_replace(":", "", $time);
    $time = str_replace(" UTC", "", $time);
    return $time;
}

function getRunways($rwys)
{
    $rwys = explode(",", $rwys);

    $a = array();
    for ($i = 0; $i < count($rwys); $i++)
    {
        if (count($rwys) > 1 && $i == count($rwys) - 1)
            $a[] = "and";

        $a[] = $rwys[$i];
    }

    return $a;
}

function getTransitionLevel()
{
    $metars = json_decode(file_get_contents('metars.json'), true);

     if($metars['tl']['tl'] == 0) {
         $a = 80;
         }
     else {
         $a = 90;
     }

    // JSON data for P21

    $path = 'package.json';
    $json = file_get_contents($path);
    $api = json_decode($json);
        $api->TL = $a;
    $newJson = json_encode($api);
    file_put_contents('package.json', $newJson);


    return $a;
}

function getSurfaceWinds($sw)
{
    $a = array();


    if ($sw->getMeanSpeed()->getValue() < 3)
    {
        $a[] = "calm";
    }
    else
    {
        if ($sw->getMeanDirection() !== null) {
            $a[] = sprintf("%03d", $sw->getMeanDirection()->getValue());
            $a[] = "degrees";
        }else $a[] = "variable";

        if ($var = $sw->getDirectionVariations())
        {
            $a[] = "variable between";
            $a[] = sprintf("%03d", $var[0]->getValue());
            $a[] = "and";
            $a[] = sprintf("%03d", $var[count($var)-1]->getValue());
            $a[] = "degrees";
        }

        $a[] = $sw->getMeanSpeed()->getValue();
        $a[] = "knots";

        if ($gust = $sw->getSpeedVariations())
        {
            $a[] = "gusting";
            $a[] = $gust->getValue();
            $a[] = "knots";
        }
    }

    return $a;
}

function updateSurfaceWindsJson($sw, $icao)
{
    if ($sw->getMeanSpeed()->getValue() < 3)
    {
        $a = "calm";
    }
    else
    {
        if ($sw->getMeanDirection() !== null) {
            $a = sprintf("%03d", $sw->getMeanDirection()->getValue());

        }else $a = "VRB";

        $a .= $sw->getMeanSpeed()->getValue();
        if (!$gust = $sw->getSpeedVariations())
        {
            $a .= 'KT';
        }

        if ($gust = $sw->getSpeedVariations())
        {
            $a .= "G";
            $a .= $gust->getValue().'KT';
        }

        if ($var = $sw->getDirectionVariations())
        {
            $a .= ' '.sprintf("%03d", $var[0]->getValue()).'V'.sprintf("%03d", $var[count($var)-1]->getValue());
        }
    }

    // JSON data for P21

    $path = 'package.json';
    $json = file_get_contents($path);
    $api = json_decode($json);
    $api->{$icao}->wind = $a;
    $newJson = json_encode($api);
    file_put_contents('package.json', $newJson);
}

function updateAtisLetterJson($letter, $icao)
{
    // JSON data for P21

    $path = 'package.json';
    $json = file_get_contents($path);
    $api = json_decode($json);
    $api->{$icao}->atisLetter = $letter;
    $newJson = json_encode($api);
    file_put_contents('package.json', $newJson);
}

function getVisibility($d)
{
    $a = array();

    if ($d->getCavok())
    {
        $a[] = "CAVOK";
    }
    elseif ($v = $d->getVisibility())
    {
        $a[] = "Visibility";
        $vis = $v->getVisibility()->getValue();
        if ($vis != 9999)
        {
            $a[] = $vis;
            $a[] = "meters";
        }
        else
        {
            $a[] = "10 kilometers or more";
        }
    }

    return $a;
}

function getClouds($clouds)
{
    $a = array();

    if ($clouds)
    {
        foreach ($clouds as $cloud)
        {
            if ($cloud->getAmount() == "VV")
            {
                $a[] = "Vertical visibility";
                $a[] = "{" . $cloud->getBaseHeight()->getValue() . "}";
                $a[] = "feet";
            }
            else
            {
                $a[] = "clouds";

                if ($cloud->getAmount() == 'BKN'){
                    $a[] = 'Broken';
                }elseif ($cloud->getAmount() == 'FEW'){
                    $a[] = 'Few';
                }elseif ($cloud->getAmount() == 'OVC'){
                    $a[] = 'Overcast';
                }elseif ($cloud->getAmount() == 'SCT'){
                    $a[] = 'Scattered';
                }

                if ($cloud->getType() != ''){
                    if ($cloud->getType() == 'CB'){
                        $a[] = 'Cumulonimbus';
                    }elseif ($cloud->getType() == 'TCU'){
                        $a[] = 'Towering Cumulus';
                    }
                }
                    if($cloud->getBaseHeight() !== null) {
                        $a[] = "{" . $cloud->getBaseHeight()->getValue() . "}";
                    }else $a[] = "{0}";
                $a[] = "feet";
            }
        }
    }

    return $a;
}

function getNoSigClouds($metar)
{
    if (strpos($metar, 'NSC')){
        $a = "No Significant Clouds";
    }elseif (strpos($metar, 'NCD')){
        $a = "No Cloud Detected";
    }

    return $a;
}

function getTemperature($temp)
{
    $a = array();

    if ($temp < 0)
        $a[] = "minus";

    $a[] = sprintf("%02d", abs($temp));

    return $a;
}

function getQNH($qnh, $icao)
{
    $a = sprintf("%04d", $qnh);

    // JSON data for P21

    $path = 'package.json';
    $json = file_get_contents($path);
    $api = json_decode($json);
    $api->{$icao}->QNH = $a;
    $newJson = json_encode($api);
    file_put_contents('package.json', $newJson);

    return $a;
}

function getRVR($rvrs)
{
    $a = array();

    if ($rvrs)
    {
        $a[] = "RVR";
        foreach ($rvrs as $rvr)
        {
            $a[] = "runway";
            $a[] = ' '.$rvr->getRunway().' ';
            $a[] = $rvr->getVisualRange()->getValue();
            $a[] = "meters";
        }
    }

    return $a;
}

function getWeather($weathers)
{
    $a = array();

    for ($i = 0; $i < count($weathers); $i++)
    {
        if (count($weathers) > 1 && $i == count($weathers) - 1)
            $a[] = "and";

        $weather = $weathers[$i];
        $intensity = $weather->getIntensityProximity();

        if ($intensity == "+")
            $a[] = "heavy";
        if ($intensity == "-")
            $a[] = "light";

            if ($weather->getCharacteristics() != '') {
                if ($weather->getCharacteristics() == 'MI'){
                    $a[] = 'Shallow';
                }elseif ($weather->getCharacteristics() == 'PR'){
                    $a[] = 'Partial';
                }elseif ($weather->getCharacteristics() == 'BC'){
                    $a[] = 'Patches';
                }elseif ($weather->getCharacteristics() == 'DR'){
                    $a[] = 'Low Drifting';
                }elseif ($weather->getCharacteristics() == 'BL'){
                    $a[] = 'Blowing';
                }elseif ($weather->getCharacteristics() == 'SH'){
                    $a[] = 'Showers';
                }elseif ($weather->getCharacteristics() == 'TS'){
                    $a[] = 'Thunderstorm';
                }elseif ($weather->getCharacteristics() == 'FZ'){
                    $a[] = 'Freezing';
                }
            }

            if ($weather->getTypes() != '') {
                foreach ($weather->getTypes() as $type) {
                    if ($type == 'B') {
                        $a[] = 'Began';
                    } elseif ($type == 'BR') {
                        $a[] = 'Mist';
                    } elseif ($type == 'DS') {
                        $a[] = 'Dust Storm';
                    } elseif ($type == 'DU') {
                        $a[] = 'Dust';
                    } elseif ($type == 'DZ') {
                        $a[] = 'Drizzle';
                    } elseif ($type == 'E') {
                        $a[] = 'Ended';
                    } elseif ($type == 'FC') {
                        $a[] = 'Funnel Cloud';
                    } elseif ($type == 'FG') {
                        $a[] = 'Fog';
                    } elseif ($type == 'FU') {
                        $a[] = 'Smoke';
                    } elseif ($type == 'GR') {
                        $a[] = 'Hail';
                    } elseif ($type == 'GS') {
                        $a[] = 'Small Hail';
                    } elseif ($type == 'HZ') {
                        $a[] = 'Haze';
                    } elseif ($type == 'IC') {
                        $a[] = 'Ice Crystals';
                    } elseif ($type == 'PL') {
                        $a[] = 'Ice Pellets';
                    } elseif ($type == 'PO') {
                        $a[] = 'Well-Developed Dust Whirls';
                    } elseif ($type == 'PY') {
                        $a[] = 'Spray';
                    } elseif ($type == 'RA') {
                        $a[] = 'Rain';
                    } elseif ($type == 'SA') {
                        $a[] = 'Sand';
                    } elseif ($type == 'SG') {
                        $a[] = 'Snow Grains';
                    } elseif ($type == 'SN') {
                        $a[] = 'Snow';
                    } elseif ($type == 'SQ') {
                        $a[] = 'Squalls Moderate';
                    } elseif ($type == 'SS') {
                        $a[] = 'Sandstorm';
                    } elseif ($type == 'UP') {
                        $a[] = 'Unknown Percipitation';
                    } elseif ($type == 'VA') {
                        $a[] = 'Volcanic Ash';
                    }
                }
            }
        if ($intensity == "VC")
            $a[] = "in vicinity";
    }

    return $a;
}

function getAptName($airport)
{

    if ($airport == "EPWA") $a = "Warsaw";
    elseif ($airport == "EPKK") $a = "Krakow";
    elseif ($airport == "EPGD") $a = "Gdansk";
    elseif ($airport == "EPKT") $a = "Katowice";
    elseif ($airport == "EPLL") $a = "Lodz";
    elseif ($airport == "EPPO") $a = "Poznan";
    elseif ($airport == "EPRZ") $a = "Rzeszow";
    elseif ($airport == "EPSC") $a = "Szczecin";
    elseif ($airport == "EPWR") $a = "Wroclaw";

    return $a;
}

function getArrivalLetter($icao, $runway){
    if ($icao == "EPWA"){

        if ($runway == 33){
            $a = "Uniform";
        }elseif ($runway == 11){
            $a = "November";
        }elseif ($runway == 15){
            $a = "Papa";
        }else $a = "Victor";

    }elseif ($icao == "EPKK"){

        if ($runway == 25){
            $a = "Golf";
        }else $a = "Hotel";

    }elseif ($icao == "EPGD"){

        if ($runway == 29){
            $a = "Tango";
        }else $a = 'Romeo';

    }
    elseif ($icao == "EPKT"){

        if ($runway == 27){
            $a = "Delta";
        }else $a = "Victor";

    }elseif ($icao == "EPLL"){

        if($runway == 25){
            $a = "Tango";
        }else $a = "Romeo";

    }
    elseif ($icao == "EPPO"){

        if($runway == 28){
            $a = "Mike";
        }else $a = "Tango";

    }
    elseif ($icao == "EPSC"){

        if($runway == 31){
            $a = "Bravo";
        }else $a = "Alpha";

    }
    elseif ($icao == "EPWR"){
        if ($runway == 29){
            $a = "Uniform";
        }else $a = "Papa";
    }

    return $a;
}

function getLVP($metar){

    $decoder = new MetarDecoder();
    $d = $decoder->parse($metar);

    $clouds = $d->getClouds();
    $rvr = $d->getRunwaysVisualRange();

    foreach ($clouds as $cloud)
    {
        if ($cloud->getAmount() == "VV" && $cloud->getBaseHeight()->getValue() <= 200)
        {
            $a = "Low Visibility Procedure in Operations";
        }
    if ($a != "Low Visibility Procedure in Operations") {
        if ($cloud->getAmount() == "OVC" && $cloud->getBaseHeight()->getValue() < 200) {
            // Conditions for LVP
            $a = "Low Visibility Procedure in Operations";
            break;
        }
    }
    }

    // check if LVP are currently in operations

    if ($a != "Low Visibility Procedure in Operations" && $rvr){
        // there are no LVP in operations -> check if rvr is under

        foreach ($rvr as $RVRange)
        {
            if ($RVRange->getVisualRange()->getValue() <= 600){
                $a = "Low Visibility Procedure in Operations";
                break;
            }
        }
    }

    return $a;

}

function getBirdsActivity($metar)
{
    $decoder = new MetarDecoder();
    $d = $decoder->parse($metar);

    $randNum = mt_rand(0, 1000);
    if ($randNum % 2 == 0)
    {
        if ($d->getCavok() && date('m') >= 3 && date('m') <=10)
        {
            $a = "Caution birds activity";
        }
    }

    return $a;
}

function getATClereance($fq)
{
    $a = "For ATC clearence contact ".$fq;

    return $a;
}

function getDepFQ($fq)
{
    $frequencies = [125.450, 134.225, 133.475, 120.950, 124.625, 134.175, 123.625, 124.925, 129.075, 127.025, 127.450, 130.875, 130.625];

    if (in_array($fq, $frequencies)) {
        $a = "When airborne contact Radar on ".$fq;
        }else{
        $a = "When airborne contact Approach on ".$fq;
    }

    return $a;
}

function automaticATClereance($icao)
{
    $atcs = json_decode(file_get_contents('onlineAtc.json'), true);

    $frequencies = array();

        foreach ($atcs as $atc) {
            $callsign = $atc['callsign'][0];
            if ((substr($callsign, -4) == 'ATIS'))
                continue;

            $callsigns[] = $callsign;
            $freq = $atc['freq'][0];
            $frequencies[] = $freq;
        }

        /** Loops for each airports - from down to top */

    if ($icao == 'EPWA')
    {
    if (in_array(121.600, $frequencies)){
    $a = 121.605;
    }
    elseif (in_array(121.900, $frequencies)){
    $a = 121.905;
    }
    elseif (in_array(118.300, $frequencies)){
    $a = 118.305;
    }
    elseif (in_array(128.800, $frequencies)){
    $a = 128.805;
    }
    elseif (in_array(125.050, $frequencies)){
    $a = 125.050;
    }
    elseif (in_array(120.950, $frequencies)){
        $a = 120.950;
    }
    elseif (in_array(127.450, $frequencies)){
    $a = 123.625;
    }
    elseif (in_array(125.450, $frequencies)){
    $a = 125.450;
    }
        elseif (in_array(130.625, $frequencies)){
            $a = 130.625;
        }

    }
    elseif ($icao == 'EPKK'){


    if (in_array(121.975, $frequencies)){
        $a = 121.975;
    }
    elseif (in_array(118.100, $frequencies) && in_array('EPKK_GND', $callsigns)){
        $a = 118.105;
    }
    elseif (in_array(123.250, $frequencies)){
        $a = 123.250;
    }
    elseif (in_array(121.075, $frequencies)){
        $a = 121.075;
    }
    elseif (in_array(134.175, $frequencies)){
        $a = 134.175;
    }
        elseif (in_array(124.625, $frequencies)){
            $a = 124.625;
        }
    elseif (in_array(125.450, $frequencies)){
        $a = 125.450;
    }
    elseif (in_array(130.625, $frequencies)){
        $a = 130.625;
    }

    }
    elseif ($icao == 'EPGD') {


        if (in_array(131.325, $frequencies)) {
            $a = 131.325;
        } elseif (in_array(118.100, $frequencies) && in_array('EPGD_TWR', $callsigns) || in_array('EPGD_T_TWR', $callsigns)) {
            $a = 118.105;
        } elseif (in_array(127.275, $frequencies)) {
            $a = 127.275;
        } elseif (in_array(129.075, $frequencies)) {
            $a = 129.075;
        } elseif (in_array(124.925, $frequencies)) {
            $a = 124.925;
        }
        elseif (in_array(127.450, $frequencies)) {
            $a = 127.450;
        }
        elseif (in_array(125.450, $frequencies)){
            $a = 125.450;
        }
        elseif (in_array(130.625, $frequencies)){
            $a = 130.625;
        }
    }
    elseif ($icao == 'EPKT'){

        if (in_array(121.800, $frequencies)){
            $a = 121.805;
        }elseif (in_array(129.250, $frequencies)){
            $a = 129.250;
        }elseif (in_array(135.400, $frequencies)){
            $a = 135.405;
        }elseif (in_array(121.075, $frequencies)){
            $a = 121.075;
        }
        elseif (in_array(134.175, $frequencies)){
            $a = 134.175;
        }
        elseif (in_array(124.625, $frequencies)){
            $a = 124.625;
        }
        elseif (in_array(125.450, $frequencies)){
            $a = 125.450;
        }
        elseif (in_array(130.625, $frequencies)){
            $a = 130.625;
        }

    }elseif ($icao == 'EPLL'){

        if (in_array(120.000, $frequencies)){
            $a = 120.0;
        }elseif (in_array(124.225, $frequencies)){
            $a = 124.225;
        }elseif (in_array(128.800, $frequencies)){
            $a = 128.805;
        }
        elseif (in_array(125.050, $frequencies)){
            $a = 125.050;
        }
        elseif (in_array(120.950, $frequencies)){
            $a = 120.950;
        }
        elseif (in_array(127.450, $frequencies)){
            $a = 123.625;
        }
        elseif (in_array(125.450, $frequencies)){
            $a = 125.450;
        }
        elseif (in_array(130.625, $frequencies)){
            $a = 130.625;
        }

    }elseif ($icao == 'EPPO'){

        if (in_array(121.800, $frequencies)){
            $a = 121.805;
        }elseif (in_array(119.975, $frequencies)){
            $a = 119.975;
        }elseif (in_array(128.925, $frequencies)){
            $a = 128.925;
        }elseif (in_array(134.225, $frequencies)){
            $a = 134.225;
        }elseif (in_array(125.450, $frequencies)){
            $a = 125.450;
        }
        elseif (in_array(130.625, $frequencies)){
            $a = 130.625;
        }

    }elseif ($icao == 'EPRZ'){

        if (in_array(121.800, $frequencies)){
            $a = 121.805;
        }elseif (in_array(126.800, $frequencies)){
            $a = 126.805;
        }elseif (in_array(123.625, $frequencies)){
            $a = 123.625;
        }elseif (in_array(124.625, $frequencies)){
            $a = 124.625;
        }
        elseif (in_array(125.450, $frequencies)){
            $a = 125.450;
        }
        elseif (in_array(130.625, $frequencies)){
            $a = 130.625;
        }

    }elseif ($icao == 'EPSC'){

        if (in_array(121.250, $frequencies)){
            $a = 121.250;
        }elseif (in_array(127.025, $frequencies)){
            $a = 127.025;
        }elseif (in_array(124.925, $frequencies)){
            $a = 124.925;
        }
        elseif (in_array(127.450, $frequencies)) {
            $a = 127.450;
        }
        elseif (in_array(125.450, $frequencies)){
            $a = 125.450;
        }
        elseif (in_array(130.625, $frequencies)){
            $a = 130.625;
        }

    }elseif ($icao == 'EPWR'){

        if (in_array(121.800, $frequencies)){
            $a = 121.805;
        }elseif (in_array(120.250, $frequencies)){
            $a = 120.250;
        }elseif (in_array(123.050, $frequencies)){
            $a = 123.050;
        }elseif (in_array(128.925, $frequencies)){
            $a = 128.925;
        }elseif (in_array(125.450, $frequencies)){
            $a = 125.450;
        }
        elseif (in_array(130.625, $frequencies)){
            $a = 130.625;
        }
    }


    if ($a != null) {
        $clrfq = 'For ATC clearance contact ' . $a;
    }else $clrfq = '';

    return $clrfq;
}

function automaticDepFq($icao, $runway_arrival)
{
    $atcs = json_decode(file_get_contents('onlineAtc.json'), true);

    $frequencies = array();

    foreach ($atcs as $atc) {
        $callsign = $atc['callsign'][0];
        if ((substr($callsign, -4) == 'ATIS'))
            continue;

        $freq = $atc['freq'][0];
        $frequencies[] = $freq;
    }

    /** Loops for each airports - down to top */

    if ($icao == 'EPWA') {
        if ($runway_arrival == '33')
        {
            if (in_array(128.800, $frequencies)){
                $a = 128.805;
            }
            elseif (in_array(125.050, $frequencies)){
                $a = 125.050;
            }
            elseif (in_array(120.950, $frequencies)){
                $a = 120.950;
            }
            elseif (in_array(127.450, $frequencies)){
                $a = 123.625;
            }
            elseif (in_array(125.450, $frequencies)){
                $a = 125.450;
            }
            elseif (in_array(130.625, $frequencies)){
                $a = 130.625;
            }
        }else
        {
            if (in_array(125.050, $frequencies)){
                $a = 125.050;
            }
            elseif (in_array(128.800, $frequencies)){
                $a = 128.800;
            }
            elseif (in_array(120.950, $frequencies)){
                $a = 120.950;
            }
            elseif (in_array(127.450, $frequencies)){
                $a = 123.625;
            }
            elseif (in_array(125.450, $frequencies)){
                $a = 125.450;
            }
            elseif (in_array(130.625, $frequencies)){
                $a = 130.625;
            }
        }



    } elseif ($icao == 'EPKK') {

        if (in_array(121.075, $frequencies)){
            $a = 121.075;
        }
        elseif (in_array(134.175, $frequencies)){
            $a = 134.175;
        }
        elseif (in_array(124.625, $frequencies)){
            $a = 124.625;
        }
        elseif (in_array(125.450, $frequencies)){
            $a = 125.450;
        }
        elseif (in_array(130.625, $frequencies)){
            $a = 130.625;
        }


    } elseif ($icao == 'EPGD') {

        if (in_array(127.275, $frequencies)) {
            $a = 127.275;
        } elseif (in_array(129.075, $frequencies)) {
            $a = 129.075;
        } elseif (in_array(124.925, $frequencies)) {
            $a = 124.925;
        }
        elseif (in_array(127.450, $frequencies)) {
            $a = 127.450;
        }
        elseif (in_array(125.450, $frequencies)){
            $a = 125.450;
        }
        elseif (in_array(130.625, $frequencies)){
            $a = 130.625;
        }

    }elseif ($icao == 'EPKT'){

        if (in_array(135.400, $frequencies)){
            $a = 135.405;
        }elseif (in_array(121.075, $frequencies)){
            $a = 121.075;
        }
        elseif (in_array(134.175, $frequencies)){
            $a = 134.175;
        }
        elseif (in_array(124.625, $frequencies)){
            $a = 124.625;
        }
        elseif (in_array(125.450, $frequencies)){
            $a = 125.450;
        }
        elseif (in_array(130.625, $frequencies)){
            $a = 130.625;
        }

    }elseif ($icao == 'EPLL'){
        if (in_array(124.225, $frequencies)){
            $a = 124.225;
        }elseif (in_array(128.800, $frequencies)){
            $a = 128.805;
        }
        elseif (in_array(125.050, $frequencies)){
            $a = 125.050;
        }
        elseif (in_array(120.950, $frequencies)){
            $a = 120.950;
        }
        elseif (in_array(127.450, $frequencies)){
            $a = 123.625;
        }
        elseif (in_array(125.450, $frequencies)){
            $a = 125.450;
        }
        elseif (in_array(130.625, $frequencies)){
            $a = 130.625;
        }

    }elseif ($icao == 'EPPO'){
        if (in_array(128.925, $frequencies)){
            $a = 128.925;
        }elseif (in_array(134.225, $frequencies)){
            $a = 134.225;
        }elseif (in_array(125.450, $frequencies)){
            $a = 125.450;
        }
        elseif (in_array(130.625, $frequencies)){
            $a = 130.625;
        }

    }elseif ($icao == 'EPRZ'){
        if (in_array(126.800, $frequencies)){
            $a = 126.805;
        }elseif (in_array(123.625, $frequencies)){
            $a = 123.625;
        }elseif (in_array(124.625, $frequencies)){
            $a = 124.625;
        }
        elseif (in_array(125.450, $frequencies)){
            $a = 125.450;
        }
        elseif (in_array(130.625, $frequencies)){
            $a = 130.625;
        }

    }elseif ($icao == 'EPSC'){
        if (in_array(121.250, $frequencies)){
            $a = 121.250;
        }elseif (in_array(127.025, $frequencies)){
            $a = 127.025;
        }elseif (in_array(124.925, $frequencies)){
            $a = 124.925;
        }
        elseif (in_array(127.450, $frequencies)) {
            $a = 127.450;
        }
        elseif (in_array(125.450, $frequencies)){
            $a = 125.450;
        }
        elseif (in_array(130.625, $frequencies)){
            $a = 130.625;
        }

    }elseif ($icao == 'EPWR'){
        if (in_array(121.800, $frequencies)){
            $a = 121.805;
        }elseif (in_array(120.250, $frequencies)){
            $a = 120.250;
        }elseif (in_array(123.050, $frequencies)){
            $a = 123.050;
        }elseif (in_array(128.925, $frequencies)){
            $a = 128.925;
        }elseif (in_array(125.450, $frequencies)){
            $a = 125.450;
        }
        elseif (in_array(130.625, $frequencies)){
            $a = 130.625;
        }

    }




    if ($a != null) {
        $frequencies = [125.450, 134.225, 133.475, 120.950, 124.625, 134.175, 123.625, 124.925, 129.075, 127.025, 127.450, 130.875, 130.625];
        if (in_array($a, $frequencies)) {
            $depFq = "When airborne contact Radar on " . $a;
        }elseif ($a == 121.250 || $a == 126.805 || $a == 124.225){
            $depFq = "When airborne contact Tower on " . $a;
        }
        else {
            $depFq = "When airborne contact Approach on " . $a;
        }
    }else $depFq = '';

    return $depFq;
}

function getRunwayCC($icao, $runway_arrival)
{
    $a = [];
    $data = file_get_contents('https://ibs.rlp.cz/home.do');
    preg_match_all('/csrfInsert\(([^)]+)\)/i', $data, $matches);

    $csrf = substr($matches[0][0], 23, strlen($matches[0][0]) - 25);

    $data = file_get_contents('https://ibs.rlp.cz/notam.do?id=notam_snowtam_okoli&anode=notam_snowtam_okoli&csrfpId='.$csrf);
    preg_match_all('/<pre class="preNotam">([^<]+)<\/pre>/i', $data, $matches);

    foreach ($matches[0] as $notams)
    {
        if (preg_match('/EP../i', trim($notams))){
            $expoladed = explode(' ', preg_replace('/\s+/', ' ', $notams));

                if ($expoladed[5] == $icao)
                {
                    $cc = explode('/', $expoladed[8]);
                    $a[] = ', Runway '.$runway_arrival.' runway condition codes ';
                    $a[] = $cc[0].', '.$cc[1].', '.$cc[2];
                }

        }
    }
    return $a;
}

function getRunwayInfo($icao)
{
    function contains($str, array $arr)
    {
        foreach($arr as $a) {
            if (stripos($str,$a) !== false) return true;
        }
        return false;
    }

    $conditions = [
        'COMPACTED',
        'SNOW',
        'DRY',
        'ON', 'TOP', 'OF',
        'ICE',
        'FROST',
        'SLIPPERY',
        'WET',
        'SLUSH',
        'SPECIALLY',
        'PREPARED',
        'RUNWAY',
        'WINTER',
        'STANDING', 'WATER',
        'CHEMICALLY', 'TREATED',
        'LOOSE', 'SAND'
    ];

    $result = '';

    $data = file_get_contents('https://ibs.rlp.cz/home.do');
    preg_match_all('/csrfInsert\(([^)]+)\)/i', $data, $matches);

    $csrf = substr($matches[0][0], 23, strlen($matches[0][0]) - 25);
    $data = file_get_contents('https://ibs.rlp.cz/notam.do?id=notam_snowtam_okoli&anode=notam_snowtam_okoli&csrfpId='.$csrf);
    preg_match_all('/<pre class="preNotam">([^<]+)<\/pre>/i', $data, $matches);
    foreach ($matches[0] as $notams)
    {
        if (preg_match('/EP../i', trim($notams))){
            $expoladed = explode(' ', preg_replace('/\s+/', ' ', $notams));

            if ($expoladed[5] == $icao)
            {
                $array = $expoladed[11];
                for ($i = 12; $i <= count($expoladed); $i++)
                {
                    if (contains($expoladed[$i], $conditions)) {
                        $array .= ' ' . $expoladed[$i];
                    }else break;
                }
                // returning array with format XXX/XXX/XXX
                $array = explode('/', $array);
                $result = ',  First part '.strtolower($array[0]).', Second part '.strtolower($array[1]).', Third part '.strtolower($array[2]).'  ';
            }

        }
    }
    return $result;
}

function getPhoneticLetter($letter)
{
    if($letter == 'A'){
        $a = 'Alpha';
    }elseif ($letter == 'B'){
        $a = 'Bravo';
    }elseif ($letter == 'C'){
        $a = 'Charlie';
    }
    elseif ($letter == 'D'){
        $a = 'Delta';
    }
    elseif ($letter == 'E'){
        $a = 'Echo';
    }
    elseif ($letter == 'F'){
        $a = 'Foxtrot';
    }
    elseif ($letter == 'G'){
        $a = 'Golf';
    }
    elseif ($letter == 'H'){
        $a = 'Hotel';
    }
    elseif ($letter == 'I'){
        $a = 'India';
    }
    elseif ($letter == 'J'){
        $a = 'Juliett';
    }
    elseif ($letter == 'K'){
        $a = 'Kilo';
    }
    elseif ($letter == 'L'){
        $a = 'Lima';
    }
    elseif ($letter == 'M'){
        $a = 'Mike';
    }
    elseif ($letter == 'N'){
        $a = 'November';
    }
    elseif ($letter == 'O'){
        $a = 'Oscar';
    }
    elseif ($letter == 'P'){
        $a = 'Papa';
    }
    elseif ($letter == 'Q'){
        $a = 'Quebec';
    }
    elseif ($letter == 'R'){
        $a = 'Romeo';
    }
    elseif ($letter == 'S'){
        $a = 'Sierra';
    }
    elseif ($letter == 'T'){
        $a = 'Tango';
    }
    elseif ($letter == 'U'){
        $a = 'Uniform';
    }
    elseif ($letter == 'V'){
        $a = 'Victor';
    }
    elseif ($letter == 'W'){
        $a = 'Whiskey';
    }
    elseif ($letter == 'X'){
        $a = 'Xray';
    }
    elseif ($letter == 'Y'){
        $a = 'Yankee';
    }
    elseif ($letter == 'Z'){
        $a = 'Zulu';
    }else $a = $letter;

    return $a;
}


    $raw_metar   = isset($_GET["metar"]) ? $_GET["metar"] : null;
    $rwy_arrival = isset($_GET["arr"])   ? $_GET["arr"]   : null;
    $rwy_depart  = isset($_GET["dep"])   ? $_GET["dep"]   : null;
    $app_type    = isset($_GET["app"])   ? $_GET["app"]   : null;
    $atis_letter = isset($_GET["info"])  ? $_GET["info"]  : null;
    $app_spec_type = isset($_GET['app_letter']) ? $_GET['app_letter'] : null;


$a = array();
$decoder = new MetarDecoder();
$d = $decoder->parse($raw_metar);

if (!$d->isValid())
{
    die("Invalid METAR was given");
}

$a[] = "This is";
$a[] = getAptName($d->getIcao());
$a[] = "information";
$a[] = getPhoneticLetter($atis_letter);

$path = 'token.json';
$json = file_get_contents($path);
$api = json_decode($json);
if (isset($_GET['token']) && $_GET['token'] == $api->token){
    updateAtisLetterJson($atis_letter, $d->getIcao());
}


$a[] = "Time";
$a[] = getTime($d->getTime());

// Approach
$a[] = "expect";
    if($d->getIcao() != "EPRZ") {
        $a[] = getArrivalLetter($d->getIcao(), $rwy_arrival);
        $a[] = "arrivals for";
    }
$a[] = $app_type;
if ($d->getIcao() == "EPWA" && ($rwy_arrival == 33 || $rwy_arrival == 11) && !isset($_GET['appLetter'])){
    $a[] = 'Yankee';
}elseif (isset($_GET['appLetter']))
{
    $a[] = getPhoneticLetter($_GET['appLetter']);
}
$a[] = "approach";
$a[] = "runway";
$a   = array_merge($a, getRunways($rwy_arrival));

// Runway Condition Codes
$a = array_merge($a, getRunwayCC($d->getIcao(), $rwy_arrival));

// Each runway part condition
$a[] = getRunwayInfo($d->getIcao());

// Departures
$a[] = "Departures runway";
$a   = array_merge($a, getRunways($rwy_depart));

$a[] = "Transition level";
$a[] = getTransitionLevel();

// Surface winds
$a[] = "Wind";
$a   = array_merge($a, getSurfaceWinds($d->getSurfaceWind()));

updateSurfaceWindsJson($d->getSurfaceWind(), $d->getIcao());

// Visibility
$a   = array_merge($a, getVisibility($d));

// Runway Visual Range
$a   = array_merge($a, getRVR($d->getRunwaysVisualRange()));

// Weather phenomenas
$a   = array_merge($a, getWeather($d->getPresentWeather()));

// Cloud base
$a   = array_merge($a, getClouds($d->getClouds()));

// NSC
$a[] = getNoSigClouds($raw_metar);

// Temperature
$a[] = "Temperature";
$a   = array_merge($a, getTemperature($d->getAirTemperature()->getValue()));

// Dew point
$a[] = "Dewpoint";
$a   = array_merge($a, getTemperature($d->getDewPointTemperature()->getValue()));

// QNH
$a[] = "QNH";
$a[] = getQNH($d->getPressure()->getValue(), $d->getIcao());



// Free text, atc information, special prcedures
if (!isset($_GET['lvp'])) {
    $a[] = getLVP($raw_metar);
}elseif (isset($_GET['lvp'])){
    $a[] = "Low Visibility Procedure in Operations";
}

if (isset($_GET['clrFq']))
{
    $a[] = getATClereance($_GET['clrFq']);
}else{
    $a[] = automaticATClereance($d->getIcao());
}

if (isset($_GET['depFq']) && $_GET['depFq'] != 0)
{
    $a[] = getDepFQ($_GET['depFq']);
}elseif(!isset($_GET['depFq'])){
    $a[] = automaticDepFq($d->getIcao(), $rwy_arrival);
}

if (!isset($_GET['cancelBirds']))
{
    $a[] = getBirdsActivity($raw_metar);
}

if (isset($_GET['freeTxt']))
{
    $a[] = $_GET['freeTxt'];
}



$a[] = "end of information";
$a[] = getPhoneticLetter($atis_letter);



// Condition parsing it into ES

    foreach ($a as $s)
    {
        if (is_numeric($s) || strpos($s, "{") > -1)
        {
            print $s;
        }
        elseif (strlen($s) == 1){
            print ' '.$s.' ';
        }
        elseif (strlen($s) > 0)
        {
            print " [". $s . "] ";
        }
    }



// Przykładowe linki
//http://markow.pl/atc/atis.php?metar=$metar($atisairport)&arr=$arrrwy($atisairport)&dep=$deprwy($atisairport)&app=ILS&info=$atiscode&depFq=121.07&clrFq=121.6
//http://markow.pl/atc/atis.php?metar=EPWA%20101230Z%20VRB04KT%20SCT001%2004/M10%20Q1021%20NOSIG&arr=33&dep=29&app=ILS&info=A
//http://markow.pl/atc/atis.php?metar=EPWA%20111300Z%2015013KT%201200%20R26/0540N%20+SN%20SCT003%20OVC001%20M00/M01%20Q1002%20R26/29//95&arr=33&dep=29&app=ILS&info=A&depFq=128.8&clrFq=121.6
//http://markow.pl/atc/atis.php?metar=$metar($atisairport)&arr=$arrrwy($atisairport)&dep=$deprwy($atisairport)&app=ILS&info=$atiscode&depFq=121.07&clrFq=121.07
//http://markow.pl/atc/atis.php?token=p4Ib5&metar=$metar($atisairport)&arr=$arrrwy($atisairport)&dep=$deprwy($atisairport)&app=ILS&info=$atiscode&depFq=128.8&clrFq=121.6

//ToDo:
