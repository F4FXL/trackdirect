<?php

require dirname(__DIR__) . "../../includes/bootstrap.php";

$safe_GET = sanitize_get($_GET);

$coverage_type = in_array($safe_GET['coveragetype'], ["onlymoving", "all"]) ? $safe_GET['coveragetype'] : "onlymoving";

$response = [];
$station = StationRepository::getInstance()->getObjectById($_GET['id'] ?? null);
if ($station->isExistingObject()) {
    $response['station_id'] = $station->id;
    $response['coverage'] = [];

    $numberOfHours = 10*24; // latest 10 days should be enough
    $limit = 5000; // Limit number of packets to reduce load on server (and browser)

    if ($coverage_type == 'onlymoving') {
        $packetPaths = PacketPathRepository::getInstance()->getLatestMovingDataListByReceivingStationId($_GET['id'] ?? null, $numberOfHours, $limit);
    } else {
        $packetPaths = PacketPathRepository::getInstance()->getLatestDataListByReceivingStationId($_GET['id'] ?? null, $numberOfHours, $limit);
    }


    foreach ($packetPaths as $path) {
        $row = [];
        $row['latitude'] = $path['sending_latitude'];
        $row['longitude'] = $path['sending_longitude'];
        $row['distance'] = $path['distance'];
        $response['coverage'][] = $row;
    }
}

header('Content-type: application/json');
header('Cache-Control: max-age=86400, public');
header('Expires: '. gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));
echo json_encode($response);
