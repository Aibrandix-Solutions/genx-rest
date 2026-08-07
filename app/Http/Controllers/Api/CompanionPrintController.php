<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Kot;
use App\Models\Printer;
use App\Services\EscPosPrinterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CompanionPrintController extends Controller
{
    /**
     * Desktop Companion App syncs its connected physical OS printers.
     */
    public function registerPrinters(Request $request)
    {
        $validated = $request->validate([
            'branch_hash' => 'required|string',
            'printers' => 'required|array',
            'os' => 'nullable|string',
            'agent' => 'nullable|string',
        ]);

        $branch = Branch::where('unique_hash', $validated['branch_hash'])->first();
        if (!$branch) {
            return response()->json(['success' => false, 'message' => 'Invalid branch key'], 404);
        }

        $branchId = $branch->id;
        $printers = array_values(array_unique(array_filter($validated['printers'])));

        // Store active physical printers and last seen in Cache for 5 minutes
        Cache::put("companion_printers_{$branchId}", $printers, 300);
        Cache::put("companion_last_seen_{$branchId}", now()->timestamp, 300);
        Cache::put("companion_os_{$branchId}", $validated['os'] ?? 'Windows', 300);

        return response()->json([
            'success' => true,
            'message' => 'Physical printers synced successfully',
            'branch_id' => $branchId,
            'synced_count' => count($printers),
        ]);
    }

    /**
     * Live web app fetches physical printers connected to the active branch's companion agent.
     */
    public function getBranchPrinters(Request $request, string $branchHash)
    {
        $branch = Branch::where('unique_hash', $branchHash)->first();
        if (!$branch) {
            return response()->json(['connected' => false, 'printers' => []]);
        }

        $branchId = $branch->id;
        $printers = Cache::get("companion_printers_{$branchId}", []);
        $lastSeen = Cache::get("companion_last_seen_{$branchId}", 0);
        $os = Cache::get("companion_os_{$branchId}", 'Windows');

        $isConnected = (now()->timestamp - $lastSeen) < 30; // Active if seen in last 30 seconds

        return response()->json([
            'connected' => $isConnected,
            'printers' => $printers,
            'last_seen' => $lastSeen,
            'os' => $os,
            'branch_name' => $branch->name,
        ]);
    }

    /**
     * Desktop companion app polls for pending print jobs.
     */
    public function pollPrintJobs(Request $request, string $branchHash)
    {
        $branch = Branch::where('unique_hash', $branchHash)->first();
        if (!$branch) {
            return response()->json(['jobs' => []]);
        }

        $branchId = $branch->id;

        // Keep-alive update
        Cache::put("companion_last_seen_{$branchId}", now()->timestamp, 300);

        $queueKey = "companion_jobs_{$branchId}";
        $jobs = Cache::get($queueKey, []);

        if (!empty($jobs)) {
            Log::info("[PRINT_DEBUG #4] CompanionPrintController::pollPrintJobs RETRIEVED " . count($jobs) . " queued jobs for branch #{$branchId} (Hash: {$branchHash})");
            // Clear fetched jobs from queue
            Cache::forget($queueKey);
        }

        return response()->json([
            'success' => true,
            'jobs' => array_values($jobs),
        ]);
    }

    /**
     * Dispatch a silent direct print job to the branch companion queue.
     */
    public static function dispatchPrintJob(int $branchId, string $printerName, string $payload, int $kotId = 0, bool $isImage = false): bool
    {
        if (empty($printerName)) {
            return false;
        }

        $queueKey = "companion_jobs_{$branchId}";
        $jobs = Cache::get($queueKey, []);

        $jobId = 'job_' . uniqid() . '_' . time();
        $jobs[] = [
            'id' => $jobId,
            'kot_id' => $kotId,
            'printer_name' => $printerName,
            'payload' => base64_encode($payload),
            'base64' => true,
            'is_image' => $isImage,
            'created_at' => now()->toIso8601String(),
        ];

        Cache::put($queueKey, $jobs, 300);
        Log::info("[PRINT_DEBUG #3] CompanionPrintController::dispatchPrintJob PUSHED job {$jobId} (KOT #{$kotId}) to cloud cache queue for branch #{$branchId} -> Printer: '{$printerName}' (is_image: " . ($isImage ? 'true' : 'false') . ")");
        return true;
    }
}
