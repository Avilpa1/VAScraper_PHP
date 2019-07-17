<?php
 
 require __DIR__.'/../vendor/autoload.php';

use Symfony\Component\DomCrawler\Crawler;

// $url = 'www.va.gov/directory/guide/region.asp?ID=1001';


$url = 'https://www.boston.va.gov/';

$client = new \GuzzleHttp\Client(['verify' => false]);
$response = $client->request('GET', $url);
 
$html = ''.$response->getBody();

$crawler = new Crawler($html);

// $nodeValues = $crawler->filter('#tier4innerContent > table')->filter('a')->each(function (Crawler $node, $i) {
//     //echo $node->html();
//     echo $node->attr('href');
//     echo '<br>';
// });


$nodeValues = $crawler->filter('#address-widget > p')->each(function (Crawler $node, $i) {
    $addressData = $node->html();

    $arr = explode("\n", $addressData);
    $arr2 = explode(",", $arr[2]);
    $arr3 = explode(" ", $arr2[1]);

    echo $arr[1]; //Address
    echo $arr2[0]; //City
    echo '<br>';
    echo $arr3[1]; //State
    echo '<br>';
    echo $arr3[2]; //Zip
    echo '<br>';

    geoCodeAddress($arr[1], $arr2[0], $arr3[1], $arr3[2]);
});

function geoCodeAddress($street, $city, $state, $zip) {
    $client = new \GuzzleHttp\Client(['verify' => false]);
    
    $geoCodeURL = 'https://geoservices.tamu.edu/Services/Geocode/WebService/GeocoderWebServiceHttpNonParsed_V04_01.aspx';
    
    $geocodeResponse = $client->request('GET', $geoCodeURL, [
                                        'query' => ['streetAddress' => $street,
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
    
    echo $data->OutputGeocodes[0]->OutputGeocode->Latitude;
    echo '<br>';
    echo $data->OutputGeocodes[0]->OutputGeocode->Longitude;
    echo '<br>';
    echo '<br>';
}



