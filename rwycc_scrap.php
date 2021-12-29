<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$data = file_get_contents('https://ibs.rlp.cz/home.do');
preg_match_all('/csrfInsert\(([^)]+)\)/i', $data, $matches);

$csrf = substr($matches[0][0], 23, strlen($matches[0][0]) - 25);
echo $csrf;
$data = file_get_contents('https://ibs.rlp.cz/notam.do?id=notam_snowtam_okoli&anode=notam_snowtam_okoli&csrfpId='.$csrf);
preg_match_all('/<pre class="preNotam">([^<]+)<\/pre>/i', $data, $matches);


function contains($str, array $arr)
{
    foreach($arr as $a) {
        if (stripos($str,$a) !== false) return true;
    }
    return false;
}

foreach ($matches[0] as $notams)
{
    if (preg_match('/EP../i', trim($notams))){
        $expoladed = explode(' ', preg_replace('/\s+/', ' ', $notams));
        //print_r($expoladed);



//                echo "There were $val instance(s) of \"" , chr($i) , "\" in the string.\n";
//                if (chr($i) == '/' && $val == 2)
//                {
//                    $result = $expoladed[11];
//                }else{
//                    $array .= ' '.$expoladed[12];
//                    if (substr_count($array, '/') == 2)
//                    {
//
//                    }else{
//                        $array .= ' '.$expoladed[13];
//                    }
//                }
                $array = $expoladed[11];
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

                            for ($i = 12; $i <= count($expoladed); $i++)
                            {
                                if (contains($expoladed[$i], $conditions)) {
                                    $array .= ' ' . $expoladed[$i];
                                }else break;
                            }
                echo 'checking for '.$expoladed[5].'<br>';
                $array = explode('/', $array);

            }
}
