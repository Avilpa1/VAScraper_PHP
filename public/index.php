<?php
ini_set('max_execution_time', 1800);
require __DIR__.'/../vendor/autoload.php';
use Symfony\Component\DomCrawler\Crawler;
$locationArray = [];
function getLocations() {
    $MainUrl = 'www.va.gov/directory/guide/region.asp?ID=1001';
    $client = new \GuzzleHttp\Client(['verify' => false]);
    $response = $client->request('GET', $MainUrl);
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
    foreach ($locationArray as $value) {
        print_r($value);
        echo '<br>';
        getLocationInfo($value);
    }
    echo 'Done.';
}

function getLocationInfo($url) {
    $url = 'http://www.boston.va.gov/locations/Causeway_Street_Boston_CBOC.asp';
    $client = new \GuzzleHttp\Client(['verify' => false]);
    $response = $client->request('GET', $url);
    $html = ''.$response->getBody();
    $crawler = new Crawler($html);
    
    $crawler->filter('#address-widget')->each(function (Crawler $node, $i) {  
        $out = $node->filter('h3')->each(function (Crawler $node2, $i) {
            global $locationName;
            $locationName[] = $node2->html();
        });
    
        $node->filter('p')->each(function (Crawler $node3, $i) {
            $addressData = $node3->html();
    
            $arr = explode("\n", $addressData);
            $arr2 = explode(",", $arr[2]);
            $arr3 = explode(" ", $arr2[1]);
            $address = str_replace('<br>' ,'' ,$arr[1]);
            $city = $arr2[0];
            $state = $arr3[1];
            $zip = str_replace('<br>' ,'' ,$arr3[2]);
    
            global $locationName;
    
            echo $locationName[$i];
            echo '<br>';
            echo $address; //Address
            echo '<br>';
            echo $city; //City
            echo '<br>';
            echo $state; //State
            echo '<br>';
            echo $zip; //Zip
            echo '<br>';
            echo '<br>';
            // geoCodeAddress($locationName[$i], $address, $city, $state, $zip);
        });
    });

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

    //print_r($output);
    saveToCSV($output);
}

function saveToCSV($output) {
    $file =fopen('test.csv', 'a');
    fputcsv($file, $output);
    fclose($file);
}


getLocations();
// getLocationInfo();
