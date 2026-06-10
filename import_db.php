<?php
$host = '203.24.51.230';
$port = 33060;
$user = 'root';
$pass = 'oyMK5S18DMjHtOSVxoQWy2n0YASA5QJuyko00udOuneeijdiNFENgVG2ZMXFL36H';

$conn = mysqli_connect($host, $user, $pass, '', $port);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error() . "\n");
}

$sql = file_get_contents(__DIR__ . '/database/webgis_db.sql');
if (mysqli_multi_query($conn, $sql)) {
    do {
        if ($res = mysqli_store_result($conn)) {
            mysqli_free_result($res);
        }
    } while (mysqli_more_results($conn) && mysqli_next_result($conn));
    echo "Database imported successfully!\n";
} else {
    echo "Error importing: " . mysqli_error($conn) . "\n";
}
mysqli_close($conn);
