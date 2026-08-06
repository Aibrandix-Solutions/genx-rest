<?php
/**
 * GenX Print Companion - Local Direct Print Agent Daemon
 * Runs on 127.0.0.1:8181
 * Discovers connected OS physical printers & executes silent direct thermal printing.
 */

$host = '127.0.0.1';
$port = 8181;

echo "=================================================================\n";
echo " GenX Print Companion v1.0.0 (Local Agent Daemon)\n";
echo " Listening on http://{$host}:{$port}\n";
echo " Press Ctrl+C to stop the daemon\n";
echo "=================================================================\n\n";

$server = @stream_socket_server("tcp://{$host}:{$port}", $errno, $errstr);

if (!$server) {
    echo "ERROR: Unable to start server on {$host}:{$port} - {$errstr} ({$errno})\n";
    exit(1);
}

while ($conn = @stream_socket_accept($server, -1)) {
    $request = fread($conn, 65536);
    if (!$request) {
        fclose($conn);
        continue;
    }

    $lines = explode("\r\n", $request);
    $firstLine = $lines[0] ?? '';
    preg_match('/^(GET|POST|OPTIONS)\s+([^\s]+)/', $firstLine, $matches);
    $method = $matches[1] ?? 'GET';
    $path = parse_url($matches[2] ?? '/', PHP_URL_PATH);

    // Standard CORS headers for live URL web apps
    $corsHeaders = "Access-Control-Allow-Origin: *\r\n" .
                   "Access-Control-Allow-Methods: GET, POST, OPTIONS\r\n" .
                   "Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With\r\n";

    if ($method === 'OPTIONS') {
        $response = "HTTP/1.1 204 No Content\r\n" . $corsHeaders . "Connection: close\r\n\r\n";
        fwrite($conn, $response);
        fclose($conn);
        continue;
    }

    // Extract JSON Body for POST
    $body = '';
    $bodyPos = strpos($request, "\r\n\r\n");
    if ($bodyPos !== false) {
        $body = substr($request, $bodyPos + 4);
    }

    $responseData = [];
    $statusCode = 200;

    if ($path === '/status') {
        $responseData = [
            'status' => 'online',
            'version' => '1.0.0',
            'agent' => 'GenX Print Companion',
            'os' => PHP_OS_FAMILY,
        ];
    } elseif ($path === '/printers') {
        $printers = getSystemPrinters();
        $responseData = [
            'success' => true,
            'printers' => $printers,
            'count' => count($printers),
        ];
    } elseif ($path === '/print' && $method === 'POST') {
        $data = json_decode($body, true) ?? [];
        $printerName = trim($data['printer_name'] ?? '');
        $payload = $data['payload'] ?? '';
        $base64 = $data['base64'] ?? false;

        if (empty($printerName)) {
            $statusCode = 400;
            $responseData = ['success' => false, 'message' => 'Missing printer_name parameter'];
        } else {
            $rawBytes = $base64 ? base64_decode($payload) : $payload;
            $printed = sendToSystemPrinter($printerName, $rawBytes);

            if ($printed['success']) {
                $responseData = [
                    'success' => true,
                    'message' => "Successfully printed to physical printer: {$printerName}",
                    'printer' => $printerName
                ];
                echo "[" . date('Y-m-d H:i:s') . "] PRINT SUCCESS -> Printer: {$printerName}\n";
            } else {
                $statusCode = 500;
                $responseData = [
                    'success' => false,
                    'message' => "Failed to print to {$printerName}: " . ($printed['error'] ?? 'Unknown error')
                ];
                echo "[" . date('Y-m-d H:i:s') . "] PRINT FAILED -> Printer: {$printerName} - " . ($printed['error'] ?? '') . "\n";
            }
        }
    } else {
        $statusCode = 404;
        $responseData = ['error' => 'Endpoint not found'];
    }

    $jsonOutput = json_encode($responseData, JSON_PRETTY_PRINT);
    $responseHeaders = "HTTP/1.1 {$statusCode} " . ($statusCode === 200 ? 'OK' : ($statusCode === 404 ? 'Not Found' : 'Bad Request')) . "\r\n" .
                        $corsHeaders .
                        "Content-Type: application/json; charset=utf-8\r\n" .
                        "Content-Length: " . strlen($jsonOutput) . "\r\n" .
                        "Connection: close\r\n\r\n";

    fwrite($conn, $responseHeaders . $jsonOutput);
    fclose($conn);
}

/**
 * Fetch connected OS physical printers
 */
function getSystemPrinters(): array
{
    $printers = [];

    if (PHP_OS_FAMILY === 'Windows') {
        // Query Windows Spooler via PowerShell
        $cmd = 'powershell -NoProfile -ExecutionPolicy Bypass -Command "Get-Printer | Select-Object -ExpandProperty Name"';
        $output = shell_exec($cmd);
        if ($output) {
            $lines = explode("\n", str_replace("\r", "", trim($output)));
            foreach ($lines as $line) {
                $name = trim($line);
                if (!empty($name)) {
                    $printers[] = $name;
                }
            }
        }
    } else {
        // macOS / Linux CUPS
        $cmd = 'lpstat -p 2>&1 | awk \'{print $2}\'';
        $output = shell_exec($cmd);
        if ($output) {
            $lines = explode("\n", trim($output));
            foreach ($lines as $line) {
                $name = trim($line);
                if (!empty($name)) {
                    $printers[] = $name;
                }
            }
        }
    }

    return array_values(array_unique($printers));
}

/**
 * Send raw ESC/POS payload to physical Windows/Mac spooler
 */
function sendToSystemPrinter(string $printerName, string $payload): array
{
    $tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'genx_print_' . uniqid() . '.bin';
    file_put_contents($tempFile, $payload);

    try {
        if (PHP_OS_FAMILY === 'Windows') {
            // Write raw bytes to Windows printer spooler via PowerShell Out-Printer
            $escapedPrinter = addslashes($printerName);
            $cmd = 'powershell -NoProfile -ExecutionPolicy Bypass -Command "Get-Content -Path \'' . addslashes($tempFile) . '\' -Raw | Out-Printer -Name \'' . $escapedPrinter . '\'"';
            $output = shell_exec($cmd . ' 2>&1');
            @unlink($tempFile);
            return ['success' => true];
        } else {
            // macOS / Linux lpr command
            $cmd = 'lpr -P "' . escapeshellcmd($printerName) . '" -o raw "' . escapeshellcmd($tempFile) . '" 2>&1';
            $output = shell_exec($cmd);
            @unlink($tempFile);
            return ['success' => true];
        }
    } catch (\Throwable $e) {
        @unlink($tempFile);
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
