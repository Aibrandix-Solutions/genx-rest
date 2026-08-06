<?php
$server = stream_socket_server("tcp://127.0.0.1:9100", $errno, $errstr);
if (!$server) {
    die("Error opening socket: $errstr ($errno)\n");
}
echo "=========================================================\n";
echo " Kitchen 1 Virtual Thermal Printer Listening on 127.0.0.1:9100...\n";
echo "=========================================================\n";

while ($client = stream_socket_accept($server)) {
    echo "\n\n=================== INCOMING KOT PRINT ===================\n";
    echo stream_get_contents($client);
    echo "\n==========================================================\n";
    fclose($client);
}
