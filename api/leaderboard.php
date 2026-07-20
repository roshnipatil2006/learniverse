<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once 'config/database.php';
include_once 'leaderboard.php';

$database = new Database();
$db = $database->getConnection();
$leaderboard = new leaderboard($db);

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        handleGetRequest($leaderboard);
        break;
    case 'POST':
        handlePostRequest($leaderboard);
        break;
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method not allowed."));
}

function handleGetRequest($leaderboard) {
    $game_type = isset($_GET['game_type']) ? $_GET['game_type'] : null;
    $level = isset($_GET['level']) ? $_GET['level'] : null;
    $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;
    $type = isset($_GET['type']) ? $_GET['type'] : 'top';
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;

    try {
        switch($type) {
            case 'top':
                if($game_type && $level) {
                    $stmt = $leaderboard->getTopScoresByGameAndLevel($game_type, $level, $limit);
                } elseif($game_type) {
                    $stmt = $leaderboard->getTopScoresByGame($game_type, $limit);
                } else {
                    $stmt = $leaderboard->getTopScores($limit);
                }
                break;
                
            case 'recent':
                $stmt = $leaderboard->getRecentScores(7, $limit);
                break;
                
            case 'user_best':
                if(!$user_id) {
                    http_response_code(400);
                    echo json_encode(array("message" => "User ID is required."));
                    return;
                }
                $stmt = $leaderboard->getUserBestScores($user_id);
                break;
                
            case 'ranked':
                $stmt = $leaderboard->getLeaderboardWithRank($game_type, $limit);
                break;
                
            default:
                $stmt = $leaderboard->getTopScores($limit);
        }

        $num = $stmt->rowCount();

        if($num > 0) {
            $scores_arr = array();
            $scores_arr["data"] = array();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $score_item = array(
                    "rank" => $row['rank'] ?? null,
                    "user_id" => $row['user_id'],
                    "username" => $row['username'] ?? 'Anonymous',
                    "game_type" => $row['game_type'],
                    "subject" => $row['subject'],
                    "level" => $row['level'],
                    "score" => $row['score'],
                    "duration" => $row['duration'],
                    "completed_at" => $row['completed_at']
                );
                array_push($scores_arr["data"], $score_item);
            }

            http_response_code(200);
            echo json_encode($scores_arr);
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "No scores found."));
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Unable to get leaderboard.", "error" => $e->getMessage()));
    }
}

function handlePostRequest($leaderboard) {
    $data = json_decode(file_get_contents("php://input"));

    if(
        !empty($data->user_id) &&
        !empty($data->game_type) &&
        !empty($data->subject) &&
        isset($data->score)
    ) {
        $leaderboard->user_id = $data->user_id;
        $leaderboard->game_type = $data->game_type;
        $leaderboard->subject = $data->subject;
        $leaderboard->level = $data->level ?? 1;
        $leaderboard->score = $data->score;
        $leaderboard->duration = $data->duration ?? 0;

        if($leaderboard->addScore()) {
            http_response_code(201);
            echo json_encode(array("message" => "Score added to leaderboard."));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Unable to add score."));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Unable to add score. Data is incomplete."));
    }
}
?>