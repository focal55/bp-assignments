<?php

/**
 * Fetch Expert Records.
 *
 * @param int $minNumPicks
 *  The expert needs at least this many picks to be included in result data.
 * @return array
 */
function fetchExpertRecords(int $minNumPicks = 3) : array {

    // Validate number of picks.
    if ($minNumPicks <= 0) {
        print "Please use a minimum number of picks value greater than 0";
        die();
    }

	$data = [];
    $recordTypes = ["Moneyline", "Spread", "Over-Under"];
    $recordTemplate = ["Category" => "", "Win" => 0, "Loss" => 0, "Push" => 0];

    $conn = mysqli_connect();

    $queryString = "
        SELECT Expert_id, Offer_Type, Result
        FROM `nfl.expert_picks`
        WHERE Expert_id IN (
            SELECT Expert_id
            FROM `nfl.expert_picks`
            GROUP BY Expert_id
            HAVING COUNT(Expert_id) >= ?
        )
    ";

    $sql = mysqli_prepare($conn, $queryString);
    $sql->bind_param("s", $minNumPicks);
    $sql->execute();
    $result = $sql->get_result();

    while ($row = $result->fetch_assoc()) {

        // Provide default array structure when a new Expert_id is encountered. This helps with the tally operations.
        if (!isset($data[$row["Expert_id"]])) {
            $data[$row["Expert_id"]]["Expert_id"] = $row["Expert_id"];
            foreach ($recordTypes as $type) {
                $data[$row["Expert_id"]]["records"][$type] = $recordTemplate;
                $data[$row["Expert_id"]]["records"]["Overall"] = $recordTemplate;
                $data[$row["Expert_id"]]["records"]["Overall"]["Category"] = "Overall";
            }
        }

        $data[$row["Expert_id"]]["records"][$row["Offer_Type"]]["Category"] = $row["Offer_Type"];
        $data[$row["Expert_id"]]["records"][$row["Offer_Type"]][transformResultName(trim($row["Result"]))] += 1;
        $data[$row["Expert_id"]]["records"]["Overall"][transformResultName(trim($row["Result"]))] += 1;
    }

	return $data;
}

/**
 * Helper: Convert Result Value to Expected Array Key Name.
 *
 * @param $value
 * @return mixed|string
 */
function transformResultName($value): string {
    $name = $value;
    if ($value == "Correct") {
        $name = "Win";
    }
    elseif ($value == "Incorrect") {
        $name = "Loss";
    }
    return $name;
}
