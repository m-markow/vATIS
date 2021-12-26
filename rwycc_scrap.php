<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$data = file_get_contents('https://ibs.rlp.cz/notam.do?id=notam_snowtam_okoli&anode=notam_snowtam_okoli&csrfpId=awFxv6ilGq1Fnajvgsy6dYs4W6QN3ln0uS2yh_ZgGRc=');

preg_match_all('/<pre class="preNotam">([^<]+)<\/pre>/i', $data, $matches);

foreach ($matches[0] as $notams)
{
    if (preg_match('/EP../i', trim($notams))){
        $expoladed = explode(' ', $notams);
        echo $expoladed[19];
    }
}
