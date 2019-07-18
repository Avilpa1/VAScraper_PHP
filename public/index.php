<?php
ini_set('max_execution_time', 1800);
require __DIR__.'/../vendor/autoload.php';
use Symfony\Component\DomCrawler\Crawler;

$locationArray = [];

function startLoop() {
    $baseURL = 'www.va.gov/directory/guide/region.asp?ID=';
    for ($x = 1001; $x <= 1024; $x++) {
        echo $baseURL.$x;
        echo '<br>';
        getLocations($baseURL.$x);
    }
}

function getLocations($url) {
    $client = new \GuzzleHttp\Client(['verify' => false]);
    $response = $client->request('GET', $url);
    $html = ''.$response->getBody();
    $crawler = new Crawler($html);
    
    $crawler->filter('#tier4innerContent > table')->filter('a')->each(function (Crawler $node, $i) {
        $locations = $node->attr('href');
        
        if (strpos($locations, 'http://') !== false) {
            global $locationArray;
            if (!in_array($locations, $locationArray)) {
                global $locationArray;
                $locationArray[] = $node->attr('href');
            }
        }
    });

    global $locationArray;
    foreach ($locationArray as $url) {
        detectPageType($url);
    }
    echo 'Done.';
}

function detectPageType($url) {
    if (strpos($url, '.asp') !== false) {
        getLocationInfoASP($url);
    } elseif (strpos($url, '.gov') !== false) {
        getLocationInfo($url);
    } else {
        echo 'other';
    }
}

function getLocationInfo($url) {
    $url = $url;
    $client = new \GuzzleHttp\Client(['verify' => false]);
    $response = $client->request('GET', $url);
    $html = ''.$response->getBody();
    $crawler = new Crawler($html);
    
    $crawler->filter('#address-widget')->each(function (Crawler $node, $i) use ($url) {  
        $out = $node->filter('h3')->each(function (Crawler $node2, $i) {
            global $locationName;
            $locationName[] = $node2->html();
        });
        $url2 = $url;
        $node->filter('p')->each(function (Crawler $node3, $i) use ($url2) {
            $addressData = $node3->html();
    
            $arr = explode("\n", $addressData);
            $arr2 = explode(",", $arr[2]);
            $arr3 = explode(" ", $arr2[1]);
            $address = str_replace('<br>' ,'' ,$arr[1]);
            $city = $arr2[0];
            $state = $arr3[1];
            $zip = str_replace('<br>' ,'' ,$arr3[2]);
    
            global $locationName;
            global $url;

            echo $url2;
            echo '<br>';
            echo $locationName[$i];
            echo '<br>';
            echo $address; //Address
            echo '<br>';
            echo $city;    //City
            echo '<br>';
            echo $state;   //State
            echo '<br>';
            echo $zip;     //Zip
            echo '<br>';
            echo '<br>';
            geoCodeAddress($locationName[$i], $address, $city, $state, $zip);
        });
    });

}

function getLocationInfoASP($url) {
    $url = $url;
    $client = new \GuzzleHttp\Client(['verify' => false]);
    $response = $client->request('GET', $url);
    $html = ''.$response->getBody();
    $crawler = new Crawler($html);
    
    $node = $crawler->filter('script');
        $nodeToString = print_r($node,true);
        $parsedString = getBetween($nodeToString, "show", ";");

        $locationName = getBetween($parsedString, 'name":"', '","');
        $address      = getBetween($parsedString, 'address_1":"', '","');
        $city         = getBetween($parsedString, 'city":"', '","');
        $state        = getBetween($parsedString, 'state":"', '","');
        $zip          = getBetween($parsedString, 'zip":"', '","');
        $lat          = getBetween($parsedString, 'lat":', ',"');
        $lng          = getBetween($parsedString, 'long":', ',"');
        // print_r($parsedString);
        // echo '<br>';
        print_r($url);
        echo '<br>';
        print_r($locationName);
        echo '<br>';
        print_r($address);
        echo '<br>';
        print_r($city);
        echo '<br>';
        print_r($state);
        echo '<br>';
        print_r($zip);
        echo '<br>';
        print_r($lat);
        echo '<br>';
        print_r($lng);
        echo '<br>';
        echo '<br>';

        $output = [
            'locationName' => $locationName,
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'zip' => $zip,
            'lat' => $lng,
            'lng' => $lng
        ];
    
        saveToCSV($output);
}


function geoCodeAddress($locationName, $address, $city, $state, $zip) {
    $client = new \GuzzleHttp\Client(['verify' => false]);
    
    $geoCodeURL = 'https://geoservices.tamu.edu/Services/Geocode/WebService/GeocoderWebServiceHttpNonParsed_V04_01.aspx';
    
    $geocodeResponse = $client->request('GET', $geoCodeURL, [
                                        'query' => ['streetAddress' => $address,
                                                    'city' => $city,
                                                    'state' => $state,
                                                    'zip' => $zip,
                                                    'apikey' => '3f38f179eb154b6498643df2e2d783ef',
                                                    'format' => 'json',
                                                    'notStore'=> 'false',
                                                    'version' => '4.01'  
                                                ]
    ])->getBody();
    
    $data = json_decode($geocodeResponse);

    $lat = $data->OutputGeocodes[0]->OutputGeocode->Latitude;
    $lng = $data->OutputGeocodes[0]->OutputGeocode->Longitude;
    
    echo $lat;
    echo '<br>';
    echo $lng;
    echo '<br>';
    echo '<br>';
    
    $output = [
        'locationName' => $locationName,
        'address' => $address,
        'city' => $city,
        'state' => $state,
        'zip' => $zip,
        'lat' => $lng,
        'lng' => $lng
    ];

    saveToCSV($output);
}

function saveToCSV($output) {
    $file =fopen('data.csv', 'a');
    fputcsv($file, $output);
    fclose($file);
}

function getBetween($string, $start = "", $end = ""){
    if (strpos($string, $start)) { // required if $start not exist in $string
        $startCharCount = strpos($string, $start) + strlen($start);
        $firstSubStr = substr($string, $startCharCount, strlen($string));
        $endCharCount = strpos($firstSubStr, $end);
        if ($endCharCount == 0) {
            $endCharCount = strlen($firstSubStr);
        }
        return substr($firstSubStr, 0, $endCharCount);
    } else {
        return '';
    }
}

// startLoop();
getLocations('www.va.gov/directory/guide/region.asp?ID=1002');
// getLocationInfo();
