<?php
require_once 'classes/logger.php';

// Query parameters are removed
$url = strtok($_SERVER['REQUEST_URI'], '?');
// Remove the trailing slash if it exists
if (substr($url, strlen($url) - 1) === '/') {
    $url = substr($url, 0, strlen($url) - 1);
}
// The server path up to this folder is removed
$url = substr($url, strpos($url, basename(__DIR__)));

// The different pieces are set into an array
$urlPieces = explode('/', urldecode($url));

header('Content-Type: application/json');
header('Accept-version: v1');

require_once 'classes/Movie.php';
$movie = new Movie();

http_response_code(200);

// Check if server if POST, GET, PUT or DELETE
if($_SERVER['REQUEST_METHOD'] === 'PUT') {
    //$data = json_decode(file_get_contents('php://input'), true); // For raw json
    //parse_str(file_get_contents('php://input'), $data); // For x-www-from-urlencoded

    $movieID = $urlPieces[2];
 
    /*** Raw data and x-www-from-urlencoded versions ***/
    // if(isset($data['movie_name'])) {
    //     echo json_encode($movie->update($movieID, $data['movie_name']));
    // }

    /*** Form-data version ***/
    $updatingStatus = false;
    $rawData = file_get_contents('php://input'); // For form-data
    $contentType = $_SERVER['CONTENT_TYPE'] ?? ''; 
    // Check if raw data contains multipart data (based on boundary)
    if (preg_match('/boundary=(.*)$/', $contentType, $matches)) {
        $boundary = $matches[1];
        
        // Split the raw data by boundary (you may want to sanitize the boundary)
        $parts = explode("--$boundary", $rawData);

        // Loop through the parts and parse the fields (ignoring boundary markers)
        foreach ($parts as $part) {
            if (strpos($part, 'movie_name') !== false) {
                // Look for 'movie_name' field and extract value
                preg_match('/name="movie_name"\s*([^"]+)/', $part, $movie_name_match);

                $movieName = trim($movie_name_match[1]);

                //Logger::logText('Raw movie name:', var_export($movieName, true));
                if (!empty($movieName)) {
                    $updatingStatus = true;
                    echo json_encode($movie->update($movieID, $movie_name_match[1]));
                    Logger::logText('result', json_encode($movie->update($movieID, $movie_name_match[1])));
                }
            }
        }

        // Check if updating failed
        if(!$updatingStatus) {
            http_response_code(400);
            reportError('Updating went wrong. movie name missing');
        }
    } else {
        // If the raw data's Content-Type is wrong
        http_response_code(400);
        reportError('Updating went wrong. Missing or incorrect Content-Type');
    }
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Posting a new movie
    $newMovieName = trim(htmlspecialchars($_POST['movie_name'])??'');
    if ($newMovieName === '') {
        reportError('No movie name given');
    } else {
        http_response_code(201);
        echo json_encode($movie->add($newMovieName));
    }
}

if($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Getting data from the API
    switch ($_GET['action']) {
        case 'list':
            // Get all movies
            echo json_encode($movie->list());
            break;
        case 'search':
            // Search for movies
            $searchText = trim($_GET['s'] ?? '');
    
            if ($searchText === '') {
                reportError('No searchword is added');
            } else {
                echo json_encode($movie->search($searchText));
            }
            break;
        case 'get':
            // Get movie by ID
            $movieID = trim($_GET['id'] ?? '');
    
            if ($movieID === '') {
                reportError('No movie ID is added');
            } else {
                echo json_encode($movie->get($movieID));
            }
            break;
        case 'delete':
            break;
        default:
            http_response_code(405);
    }
}


// foreach($urlPieces as $piece) {
//     switch ($urlPieces) {
//         case 'movies':
//             echo json_encode($movie->list());
//             break;
//         case 'search':
//             $searchText = trim($_GET['search_text'] ?? '');
//             if ($searchText === '') {
//                 reportError();
//             } else {
//                 echo json_encode($movie->search($searchText));
//             }
//             break;
//         case 'add':
//             $name = trim($_GET['name'] ?? '');
//             if ($name === '') {
//                 reportError();
//             } else {
//                 http_response_code(201);
//                 echo json_encode($movie->add($name));
//             }
//             break;
//         case 'edit':
    
//             break;
//         case 'delete':
//             break;
//         default:
//             http_response_code(405);
//     }
// }

function reportError(string $message = 'Incorrect parameters')
{
    http_response_code(400);
    echo json_encode([
        'error' => $message
    ]);
}