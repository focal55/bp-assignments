<?php

/**
 * @file
 * Assignment 1 for BP
 * PHP 7.4
 */

// Const.
const DATA_DIR = "data";
const DATA_TEAMS = "assn1-teams.json";
const DATA_RECORDS = "assn1-records.json";

// Read JSON data files.
$teamsData = file_get_contents(DATA_DIR . "/" . DATA_TEAMS);
$recordsData = file_get_contents(DATA_DIR . "/" . DATA_RECORDS);

// Bail if we can not find both.
if (!$teamsData || !$recordsData) {
    print "Well that was unexpected. We can not find the data files.";
}

// Parse the data.
$teamsData = json_decode($teamsData, TRUE);
$recordsData = json_decode($recordsData, TRUE);

// Build response data.
// Loop through Records and populate a combined array sorted by overall win percentage.
$responseData = [];
foreach ($recordsData["conferences"] as $record) {
    foreach ($record["divisions"] as $division) {
        foreach ($division["teams"] as $data) {
            $teamData = getTeamFromData($data["alias"], $teamsData);

            // Skip if we do not have all the necessary data.
            if (!isset($teamData["team_id"]) || !isset($teamData["name"]) || !isset($data)) {
                continue;
            }

            $team = new Team($teamData["team_id"], $teamData["name"], $data);
            $responseData[] = $team->getObject();
        }
    }
}

// Sort by win percentage.
usort($responseData, "compareWinPct");

// Return data in json format.
header("Content-Type: application/json; charset=utf-8");
print json_encode($responseData);


/**
 * Get Team from supplied data array.
 * @param string $teamAbr
 * @param array $teamData
 * @return array|null
 */
function getTeamFromData(string $teamAbr, array $teamData) {
    $team = NULL;
    foreach ($teamData as $data) {
        if ($data["team_id"] == $teamAbr) {
            $team = $data;
            break;
        }
    }
    return $team;
}

/**
 * Sort Response Data by Overall Win Percentage.
 * @param $a
 * @param $b
 * @return int
 */
function compareWinPct($a, $b) {
    if ($a->records->overall->win_pct == $b->records->overall->win_pct) {
        return 0;
    }
    return $a->records->overall->win_pct > $b->records->overall->win_pct ? -1 : 1;
}


/**
 * Structs: Team.
 */
class Team
{
    protected $teamData = [];
    public $team_id;
    public $name;
    public $records;

    public function __construct(string $teamId, string $teamName, array $teamData)
    {
        $this->teamData = $teamData;
        $this->team_id = $teamId;
        $this->name = $teamData["market"] . " " . $teamName;
        $this->records = $this->getRecords();
    }

    protected function getRecords() {
        $teamConference = isset($this->teamDatadata["afc"]) ? 'afc' : 'nfc';

        $records = new stdClass();

        $overall = new Record('overall', $this->teamData["records"]);
        $records->overall = $overall->getObject();

        $conference = new Record($teamConference, $this->teamData["records"]);
        $records->conference = $conference->getObject();

        $division = new Record('division', $this->teamData["records"]);
        $records->division = $division->getObject();

        return $records;
    }

    public function getObject() : stdClass {
        $team = new stdClass();
        $team->team_id = $this->team_id;
        $team->name = $this->name;
        $team->records = $this->records;
        return $team;
    }
}

/**
 * Struct: Record.
 */
class Record
{
    public $wins;
    public $losses;
    public $ties;
    public $win_pct;
    public $points_for;
    public $points_against;

    public function __construct($category, $teamRecords)
    {
        foreach ($teamRecords as $record) {
            if ($record["category"] == $category) {
                $this->wins = $record["wins"];
                $this->losses = $record["losses"];
                $this->ties = $record["ties"];
                $this->win_pct = $record["win_pct"];
                $this->points_for = $record["points_for"];
                $this->points_against = $record["points_against"];
                break;
            }
        }
    }

    public function getObject(): stdClass {
        $record = new stdClass();
        $record->wins = $this->wins;
        $record->losses = $this->losses;
        $record->ties = $this->ties;
        $record->win_pct = $this->win_pct;
        $record->points_for = $this->points_for;
        $record->points_against = $this->points_against;
        return $record;
    }
}
