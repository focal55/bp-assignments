<?php

/**
 * @file
 * Assignment 1 for BP
 *
 * - Writing for PHP 7.4.25
 */

const DATA_DIR = "data";
const DATA_TEAMS = "assn1-teams.json";
const DATA_RECORDS = "assn1-records.json";

// Read JSON data files
$teamData = file_get_contents(DATA_DIR . "/" . DATA_TEAMS);
$recordData = file_get_contents(DATA_DIR . "/" . DATA_RECORDS);

if (!$teamData || !$recordData) {
    print "Well that was unexpected. We can not find the data files.";
}

// Parse.
$teams = json_decode($teamData, TRUE);
$records = json_decode($recordData, TRUE);

$responseData = [];

// Loop through Records and populate combined array keyed by season then team
// See example structure
foreach ($records as $record) {
    foreach ($record["conferences"] as $conference) {
        foreach ($conference["divisions"] as $division) {
            foreach ($conference["teams"] as $team) {
                $teamId = getTeamIdFromData($team["alias"], $teamData);
                $team = new Team($teamId, $team);
                $team->records = new stdClass();
                $team->records->overall = new Record($team['records']);
                $team->records->conference = new Record($team['records']);
                $team->records->division = new Record($team['records']);
                $responseData[] = $team;
            }
        }
    }

    usort($responseData, ["Team", "compareWinPct"]);

    return $responseData;
}


function compareWinPct($a, $b) {
    if ($a->records->overall->win_pct == $b->records->overall->win_pct) {
        return 0;
    }
    return $a->records->overall->win_pct > $b->records->overall->win_pct ? -1 : 1;

}

/**
 * Get Team from supplied data array.
 * @param string $teamAbr
 * @param array $teamData
 * @return string|null
 */
function getTeamIdFromData(string $teamAbr, array $teamData): array {
    $teamId = NULL;
    foreach ($teamData as $data) {
        if (isset($data[$teamAbr])) {
            $teamId = $data['alias'];
            break;
        }
    }
    return $teamId;
}

/**
 * Structs: Team.
 */
class Team
{
    protected $team_id;
    protected $name;

    public function __construct(string $teamId, array $teamData)
    {
        $this->team_id = $teamData['team_id'];
        $this->name = $teamData['city'] . " " . $teamData['name'];
    }

    public function getObject() : stdClass {
        $team = new stdClass();
        $team->team_id = $this->team_id;
        $team->name = $this->name;
        return $team;
    }
}

/**
 * Struct: Record.
 */
class Record
{
    protected $wins;
    protected $losses;
    protected $ties;
    protected $win_pct;
    protected $points_for;
    protected $points_against;

    public function __construct($category, $teamRecords)
    {

        foreach ($teamRecords as $record) {
            if ($record['category'] == $category) {
                $this->wins = $record['wins'];
                $this->losses = $record['losses'];
                $this->ties = $record['ties'];
                $this->win_pct = $record['win_pct'];
                $this->points_for = $record['points_for'];
                $this->points_against = $record['points_against'];
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
