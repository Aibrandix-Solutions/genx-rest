<?php

namespace App\Services;

use App\Models\Kot;
use App\Models\Printer;
use Illuminate\Support\Facades\Log;

class EscPosPrinterService
{
    /**
     * Send a raw ESC/POS KOT print ticket over TCP socket directly to a network printer IP (port 9100).
     */
    public static function printKotDirect(Kot $kot, Printer $printer): bool
    {
        $printerName = trim((string) ($printer->printer_name ?? ''));
        $ip = trim((string) ($printer->ip_address ?? $printer->ipv4_address ?? ''));
        $port = (int) ($printer->port ?? 9100);
        $isImageMode = ($printer->printing_choice === 'directImagePrint');

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $caller = isset($trace[1]['class']) ? ($trace[1]['class'] . '::' . $trace[1]['function']) : 'unknown';
        Log::info("[PRINT_DEBUG #1] printKotDirect CALLED for KOT #{$kot->id} (Order #{$kot->order_id}) -> Printer '{$printer->name}' (Choice: {$printer->printing_choice}) via {$caller}");

        try {
            if ($isImageMode) {
                $bytes = self::renderKotImage($kot, $printer);
            } else {
                $bytes = self::buildEscPosKotBytes($kot, $printer);
            }

            // 1. Route via GenX Print Companion if physical printer_name is configured
            if ($printerName !== '') {
                return self::sendToCompanion($printerName, $bytes, $kot->id, $kot->branch_id, $isImageMode);
            }

            // 2. Route via Direct TCP IP Socket if IP address is configured
            if ($ip !== '') {
                $socket = @fsockopen($ip, $port, $errno, $errstr, 3);
                if (!$socket) {
                    Log::error("EscPosPrinterService: Cannot connect to thermal printer at {$ip}:{$port} - Error {$errno}: {$errstr}");
                    return false;
                }

                fwrite($socket, $bytes);
                fclose($socket);

                Log::info("EscPosPrinterService: Successfully sent KOT #{$kot->kot_number} to printer {$printer->name} ({$ip}:{$port})");
                return true;
            }

            Log::warning("EscPosPrinterService: Printer ID {$printer->id} has neither printer_name nor IP address configured.");
            return false;
        } catch (\Throwable $e) {
            Log::error("EscPosPrinterService Exception for KOT #{$kot->kot_number}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send raw ESC/POS or Image payload to GenX Print Companion (Local Agent or Cloud Bridge Relay)
     */
    public static function sendToCompanion(string $printerName, string $bytes, int $kotId = 0, int $branchId = 0, bool $isImage = false): bool
    {
        Log::info("[PRINT_DEBUG #2] sendToCompanion CALLED for KOT #{$kotId} -> Printer '{$printerName}' (isImage: " . ($isImage ? 'true' : 'false') . ")");

        // 1. Try Local Agent Endpoint first (fastest for local environment)
        try {
            $ch = curl_init('http://127.0.0.1:8181/print');
            $payload = json_encode([
                'printer_name' => $printerName,
                'payload' => base64_encode($bytes),
                'base64' => true,
                'is_image' => $isImage,
            ]);

            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT => 5,
            ]);

            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                Log::info("[PRINT_DEBUG #2a] SUCCESS via Local Agent (http://127.0.0.1:8181/print) for Printer '{$printerName}'");
                return true;
            }
            Log::info("[PRINT_DEBUG #2b] Local Agent returned HTTP {$httpCode}, falling back to Cloud Relay Queue");
        } catch (\Throwable $e) {
            Log::info("[PRINT_DEBUG #2c] Local Agent failed ({$e->getMessage()}), falling back to Cloud Relay Queue");
        }

        // 2. Cloud Bridge Queue Dispatch (for live cloud domains like https://digierp.cloud)
        $targetBranchId = $branchId > 0 ? $branchId : (branch()?->id ?? 0);
        if ($targetBranchId > 0) {
            $dispatched = \App\Http\Controllers\Api\CompanionPrintController::dispatchPrintJob(
                $targetBranchId,
                $printerName,
                $bytes,
                $kotId,
                $isImage
            );
            if ($dispatched) {
                Log::info("EscPosPrinterService: Dispatched KOT to Cloud Companion Queue -> Printer: {$printerName}");
                return true;
            }
        }

        Log::error("EscPosPrinterService: Failed to dispatch KOT print to {$printerName}");
        return false;
    }

    /**
     * Build ESC/POS bytecode payload for a KOT.
     */
    public static function buildEscPosKotBytes(Kot $kot, Printer $printer): string
    {
        $kot->loadMissing([
            'items.menuItem',
            'items.menuItemVariation',
            'items.modifierOptions',
            'order.table',
            'order.waiter',
            'kotPlace',
        ]);

        $esc = "\x1B";
        $gs  = "\x1D";

        $init         = $esc . "@";           // Initialize printer
        $alignCenter  = $esc . "a\x01";        // Center align
        $alignLeft    = $esc . "a\x00";        // Left align
        $boldOn       = $esc . "E\x01";        // Bold ON
        $boldOff      = $esc . "E\x00";        // Bold OFF
        $doubleSize   = $gs  . "!\x11";        // Double height & width
        $normalSize   = $gs  . "!\x00";        // Normal text size
        $cutPaper     = $gs  . "V\x41\x03";    // Full cut with feed

        $charWidth = match ($printer->print_format ?? 'thermal80mm') {
            'thermal56mm'  => 32,
            'thermal112mm' => 64,
            default        => 48,
        };

        $divider = str_repeat('-', $charWidth) . "\n";
        $buf = '';

        // 1. Initialize
        $buf .= $init;

        // 2. Kitchen / Header
        $buf .= $alignCenter;
        $placeName = $kot->kotPlace?->name ?? 'KITCHEN';
        $buf .= $boldOn . $doubleSize . strtoupper($placeName) . "\n" . $normalSize . $boldOff;

        // 3. KOT Number
        $kotNumStr = 'KOT #' . ($kot->kot_number ?? $kot->id);
        $buf .= $boldOn . $doubleSize . $kotNumStr . "\n" . $normalSize . $boldOff;

        if ($kot->token_number) {
            $buf .= "Token: #" . $kot->token_number . "\n";
        }

        $buf .= $divider;

        // 4. Order info (Left aligned)
        $buf .= $alignLeft;

        $orderNum = $kot->order?->show_formatted_order_number ?? $kot->order?->order_number ?? ('#' . $kot->order_id);
        $tableCode = $kot->order?->table?->table_code ?? '-';
        $buf .= self::twoColumn('Order: ' . $orderNum, 'Table: ' . $tableCode, $charWidth) . "\n";

        $tz = branch()->restaurant->timezone ?? config('app.timezone', 'UTC');
        $createdAt = $kot->created_at?->timezone($tz);
        $dateStr = $createdAt ? $createdAt->format('d-m-Y') : '';
        $timeStr = $createdAt ? $createdAt->format('h:i A') : '';
        $buf .= self::twoColumn('Date: ' . $dateStr, 'Time: ' . $timeStr, $charWidth) . "\n";

        if ($kot->order?->waiter) {
            $buf .= "Waiter: " . $kot->order->waiter->name . "\n";
        }

        if ($kot->order?->order_type) {
            $buf .= "Type: " . ucwords(str_replace('_', ' ', (string) $kot->order->order_type)) . "\n";
        }

        $buf .= $divider;

        // 5. Items table header
        $buf .= $boldOn . self::twoColumn('ITEM', 'QTY', $charWidth) . "\n" . $boldOff;
        $buf .= $divider;

        // 6. Item lines
        foreach ($kot->items as $item) {
            $itemName = (string) ($item->menuItem?->item_name ?? 'Item');
            $qtyStr = (string) (int) $item->quantity;

            if ($item->menuItemVariation?->variation) {
                $itemName .= ' (' . $item->menuItemVariation->variation . ')';
            }

            $buf .= $boldOn . self::twoColumn($itemName, $qtyStr, $charWidth) . "\n" . $boldOff;

            // Modifiers
            foreach ($item->modifierOptions as $mod) {
                $modQty = (int) ($mod->pivot->quantity ?? 1);
                $modStr = '  + ' . $mod->name . ($modQty > 1 ? " x{$modQty}" : '');
                $buf .= $modStr . "\n";
            }

            // Item Note
            if (!empty($item->note)) {
                $buf .= '  * Note: ' . $item->note . "\n";
            }
        }

        $buf .= $divider;

        // 7. Overall KOT Special Instructions
        if (!empty($kot->note)) {
            $buf .= $boldOn . "SPECIAL INSTRUCTIONS:\n" . $boldOff;
            $buf .= $kot->note . "\n";
            $buf .= $divider;
        }

        // 8. Feed & Cut
        $buf .= "\n\n\n";
        $buf .= $cutPaper;

        return $buf;
    }

    /**
     * Render a KOT ticket as a high-contrast PNG image payload matching browser popup layout.
     */
    public static function renderKotImage(Kot $kot, Printer $printer): string
    {
        $kot->loadMissing([
            'items.menuItem',
            'items.menuItemVariation',
            'items.modifierOptions',
            'order.table',
            'order.waiter',
            'kotPlace',
        ]);

        $width = match ($printer->print_format ?? 'thermal80mm') {
            'thermal56mm'  => 280,
            'thermal112mm' => 560,
            default        => 384, // 80mm optimal thermal width
        };

        $placeName = $kot->kotPlace?->name ?? $printer->kot_details?->first()?->name ?? '';
        if (strtoupper($placeName) === 'DEFAULT KITCHEN') {
            $placeName = '';
        }

        $kotNumStr = 'KOT #' . ($kot->kot_number ?? $kot->id);
        
        $rawOrderNum = $kot->order?->show_formatted_order_number ?? $kot->order?->order_number ?? ('#' . $kot->order_id);
        $orderNum = str_starts_with($rawOrderNum, 'Order') ? $rawOrderNum : ('Order ' . $rawOrderNum);
        
        $tableCode = $kot->order?->table?->table_code ?? '-';

        $tz = branch()->restaurant->timezone ?? config('app.timezone', 'UTC');
        $createdAt = $kot->created_at?->timezone($tz);
        $dateStr = $createdAt ? $createdAt->format('d-m-Y') : '';
        $timeStr = $createdAt ? $createdAt->format('h:i A') : '';

        $lineHeight = 24;
        $totalLines = 12 + (count($kot->items) * 3);
        $height = max(350, $totalLines * $lineHeight + 100);

        $img = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);

        imagefill($img, 0, 0, $white);

        $font = 4; // Clean GD Font
        $largeFont = 5; // Bold Title Font
        $y = 15;

        // 1. Kitchen Name (Centered)
        if (!empty($placeName)) {
            $textWidth = imagefontwidth($font) * strlen($placeName);
            imagestring($img, $font, (int)(($width - $textWidth) / 2), $y, $placeName, $black);
            $y += 24;
        }

        // 2. KOT Number Header (Large Centered Bold)
        $textWidth = imagefontwidth($largeFont) * strlen($kotNumStr);
        imagestring($img, $largeFont, (int)(($width - $textWidth) / 2), $y, $kotNumStr, $black);
        $y += 30;

        // 3. Dashed Divider
        $dashedDivider = str_repeat('-', (int)($width / (imagefontwidth($font) + 0.5)));
        imagestring($img, $font, 10, $y, $dashedDivider, $black);
        $y += 20;

        // 4. Order Info
        imagestring($img, $font, 10, $y, $orderNum, $black);
        $tableStr = "Table: {$tableCode}";
        $tableX = $width - 10 - (imagefontwidth($font) * strlen($tableStr));
        imagestring($img, $font, (int)$tableX, $y, $tableStr, $black);
        $y += 22;

        imagestring($img, $font, 10, $y, "Date: {$dateStr}", $black);
        $timeFullStr = "Time: {$timeStr}";
        $timeX = $width - 10 - (imagefontwidth($font) * strlen($timeFullStr));
        imagestring($img, $font, (int)$timeX, $y, $timeFullStr, $black);
        $y += 22;

        if ($kot->order?->waiter) {
            imagestring($img, $font, 10, $y, "Waiter: {$kot->order->waiter->name}", $black);
            $y += 22;
        }

        if ($kot->order?->order_type) {
            $orderTypeStr = "Order Type: " . ucwords(str_replace('_', ' ', (string) $kot->order->order_type));
            imagestring($img, $font, 10, $y, $orderTypeStr, $black);
            $y += 22;
        }

        // Dashed Divider
        imagestring($img, $font, 10, $y, $dashedDivider, $black);
        $y += 20;

        // 5. Items Header
        imagestring($img, $largeFont, 10, $y, "Item Name", $black);
        $qtyHeaderStr = "Qty";
        $qtyX = $width - 10 - (imagefontwidth($largeFont) * strlen($qtyHeaderStr));
        imagestring($img, $largeFont, (int)$qtyX, $y, $qtyHeaderStr, $black);
        $y += 22;
        imageline($img, 10, $y, $width - 10, $y, $black);
        $y += 12;

        // 6. Items Table
        foreach ($kot->items as $item) {
            $itemName = (string) ($item->menuItem?->item_name ?? 'Item');
            $qtyStr = (string) (int) $item->quantity;

            if ($item->menuItemVariation?->variation) {
                $itemName .= ' (' . $item->menuItemVariation->variation . ')';
            }

            imagestring($img, $font, 10, $y, substr($itemName, 0, 24), $black);
            $itemQtyX = $width - 10 - (imagefontwidth($font) * strlen($qtyStr));
            imagestring($img, $font, (int)$itemQtyX, $y, $qtyStr, $black);
            $y += 22;

            foreach ($item->modifierOptions as $mod) {
                $modQty = (int) ($mod->pivot->quantity ?? 1);
                $modStr = '  + ' . $mod->name . ($modQty > 1 ? " x{$modQty}" : '');
                imagestring($img, $font, 15, $y, substr($modStr, 0, 28), $black);
                $y += 18;
            }

            if (!empty($item->note)) {
                imagestring($img, $font, 15, $y, "  * Note: {$item->note}", $black);
                $y += 18;
            }
        }

        // Dashed Divider
        imagestring($img, $font, 10, $y, $dashedDivider, $black);
        $y += 20;

        if (!empty($kot->note)) {
            imagestring($img, $largeFont, 10, $y, "SPECIAL INSTRUCTIONS:", $black);
            $y += 22;
            imagestring($img, $font, 10, $y, substr($kot->note, 0, 32), $black);
            $y += 24;
            imagestring($img, $font, 10, $y, $dashedDivider, $black);
            $y += 20;
        }

        $actualHeight = $y + 20;
        $croppedImg = imagecreatetruecolor($width, $actualHeight);
        imagecopy($croppedImg, $img, 0, 0, 0, 0, $width, $actualHeight);
        imagedestroy($img);

        ob_start();
        imagepng($croppedImg);
        $pngData = ob_get_clean();
        imagedestroy($croppedImg);

        return (string) $pngData;
    }

    /**
     * Format a line into 2 columns aligned to the left and right edges.
     */
    private static function twoColumn(string $left, string $right, int $width = 48): string
    {
        $leftLen = strlen($left);
        $rightLen = strlen($right);

        if ($leftLen + $rightLen + 1 > $width) {
            $maxLeft = max(10, $width - $rightLen - 1);
            $left = substr($left, 0, $maxLeft);
            $leftLen = strlen($left);
        }

        $spaces = max(1, $width - $leftLen - $rightLen);
        return $left . str_repeat(' ', $spaces) . $right;
    }
}
