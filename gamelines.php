<?php

/**
 * @file
 * Assignment 2 for BP
 * PHP 7.4
 */

// Const.
const GAMELINES_URL = "https://sportsbook-us-nj.draftkings.com/api/odds/v1/leagues/88670561/offers/gamelines.json";
const API_KEY = "123asf23";
const CSV_FILE_PATH = "gameline-offer-data.csv";

// Parse the args
parse_str(implode('&', array_slice($argv, 1)), $_GET);

// Require the --offer arg.
if (!isset($_GET["--offer"])) {
    print "Please include an --offer argument.\n";
    die();
}

// Fetch the Data.
print "Fetching Data.../n";
$headers = [
    "Accept: application/json",
    "Authorization: " . API_KEY
];
$curl = curl_init(GAMELINES_URL);
curl_setopt($curl, CURLOPT_URL, GAMELINES_URL);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($curl);
curl_close($curl);

// Bail if we did not get a response.
if (!$response) {
    print "Well that was unexpected. We did not get an response for the API.\n";
    die();
}

print "Received Data...\n";
$responseData = json_decode($response, TRUE);

// Parse response.
print "Parsing Data...\n";
$offerData = fetchOfferData("Total 2nd Half", $responseData);

// Write CSV.
print "Saving Data...\n";
$offerData = array_2_csv($offerData);

$success = file_put_contents(CSV_FILE_PATH, $offerData);
if (!$success) {
    print "Whoops, we couldn't save the offer data for some reason\n";
    die();
}

print "Complete! Grab it at " . __DIR__ . "/" . CSV_FILE_PATH . "\n";

/**
 * Get Selected Offer from Response Data.
 *
 * @param $offerLabel
 * @param $data
 * @return array
 */
function fetchOfferData($offerLabel, $data) {
    $matchingEvents[] = ["Id", "Name", "Label", "Line", "Odds American"];
    foreach ($data["events"] as $event) {
        foreach ($event["offers"] as $offer) {
            if ($offer["label"] == $offerLabel) {
                foreach ($offer["outcomes"] as $outcome) {
                    $matchingEvents[] = [
                        $event["id"],
                        $event["name"],
                        $outcome["label"],
                        $outcome["line"],
                        $outcome["oddsAmerican"]
                    ];
                }
            }
        }
    }
    return $matchingEvents;
}


/**
 * Convert array to CSV
 *
 * @param array $data
 * @param string $delimiter
 * @param string $enclosure
 * @param string $escape_char
 * @return false|string
 */
function array_2_csv(array $data, string $delimiter = ',', string $enclosure = '"', $escape_char = "\\") {
    $f = fopen('php://memory', 'r+');
    foreach ($data as $item) {
        fputcsv($f, $item, $delimiter, $enclosure, $escape_char);
    }
    fseek($f, 0);
    return stream_get_contents($f);
}