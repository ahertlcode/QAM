<?php


include_once "dbconfig.php";
include_once "DbHandlers.php";
include_once "utilities.php";
include_once "api_maker.php";
include_once "app_maker.php";

if (sizeof($argv)>1){
    //var_dump($argv);
    $param = array();
    for ($i=2; $i<sizeof($argv); $i++){
        array_push($param, $argv[$i]);
    }
    if ($argv[1] == "--api"){
        apimaker::makeapi($param);
    } else if ($argv[1] == "--app") {
        appmaker::makeapp($param);
    } else {
        echo $argv[1]." is not a known maker command";
    }
} else {
    echo "You must specify arguments to the script.";
}
?>
