<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\PrintJob;
use Illuminate\Http\Request;
use App\Models\Printer;
use App\Helper\Files;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PrintJobController extends Controller
{
    public function testConnection(Request $request)
    {
        $branch = $request->get('branch');

        if ($branch) {
            $pusherSettings = pusherSettings();

            $pusherEnabled = $pusherSettings->is_enabled_pusher_broadcast;

            $response = [
                'message' => 'Connection established',
                'status' => 'success',
                'pusher_enabled' => $pusherEnabled,
            ];

            // Include Pusher configuration for desktop application
            if ($pusherEnabled) {
                $response['pusher_config'] = [
                    'app_id' => $pusherSettings->pusher_app_id,
                    'key' => $pusherSettings->pusher_key,
                    'cluster' => $pusherSettings->pusher_cluster ?? 'mt1',
                    'channel' => 'print-jobs',
                    'event' => 'print-job.created'
                ];
            }

            return response()->json($response, 200);
        }
        return response()->noContent(); // 204
    }

    public function printerDetails(Request $request)
    {
        $branch = $request->get('branch');
        $printer = Printer::where('branch_id', $branch->id)->get();
        return response()->json($printer);
    }

    /**
     * Get print jobs for a specific printer
     * Desktop applications can use this to get printer-specific jobs
     */
    public function getPrinterJobs(Request $request, $printerId)
    {
        $branch = $request->get('branch');

        $printJobs = PrintJob::where('printer_id', $printerId)
            ->where('branch_id', $branch->id)
            ->where('status', 'pending')
            ->with('printer:id,name,printing_choice,print_format,share_name,type')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'print_jobs' => $printJobs->map(fn (PrintJob $job) => $this->formatJobForDesktop($job, $branch))->values(),
            'count' => $printJobs->count(),
        ]);
    }
    // Returns the oldest pending job (or 204 if none)
    public function pull(Request $request)
    {
        $branch = $request->get('branch');

        $job = PrintJob::with('printer:id,name,printing_choice,print_format,share_name,type')
            ->where('status', 'pending')
            ->where('branch_id', $branch->id)
            ->oldest()
            ->first();

        if (!$job) {
            return response()->json(['message' => 'No pending jobs', 'status' => 'error'], 204);
        }

        $job->update(['status' => 'printing']);

        return response()->json($this->formatJobForDesktop($job, $branch));
    }

    public function pullMultiple(Request $request)
    {
        $branch = $request->get('branch');

        $jobs = PrintJob::with('printer:id,name,printing_choice,print_format,share_name,type')
            ->where('status', 'pending')
            ->where('branch_id', $branch->id)
            ->oldest()
            ->get();

        if ($jobs->isEmpty()) {
            return response()->json([]); // Return empty array instead of object
        }

        // Filter jobs to only include those where the image file exists
        $validJobs = $jobs->filter(function ($job) {

            if (empty($job->image_filename)) {
                return false;
            }

            $imagePath = public_path(Files::UPLOAD_FOLDER . '/print/' . $job->image_filename);

            $checkImageExists = File::exists($imagePath);

            if (!$checkImageExists) {
                $job->update([
                    'error' => 'Image file not found',
                    'status' => 'failed'
                ]);
                return false;
            }

            return true;
        });

        if ($validJobs->isEmpty()) {
            return response()->json([]); // Return empty array instead of object
        }

        foreach ($validJobs as $item) {
            $item->update(['status' => 'printing']);
        }

        return response()->json(
            $validJobs->values()->map(fn (PrintJob $job) => $this->formatJobForDesktop($job, $branch))->all()
        );
    }

    /**
     * Serve a print ticket image to the desktop app (key via header or ?key= query).
     */
    public function image(Request $request, PrintJob $printJob): Response
    {
        /** @var Branch $branch */
        $branch = $request->get('branch');

        if ((int) $printJob->branch_id !== (int) $branch->id) {
            return response()->json(['message' => 'Print job not found'], 404);
        }

        if (empty($printJob->image_filename)) {
            return response()->json(['message' => 'No image for this print job'], 404);
        }

        $path = public_path(Files::UPLOAD_FOLDER.'/print/'.$printJob->image_filename);

        if (! File::exists($path)) {
            return response()->json(['message' => 'Image file not found'], 404);
        }

        return response()->file($path, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'private, no-cache',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatJobForDesktop(PrintJob $job, Branch $branch): array
    {
        $data = $job->toArray();
        $desktopUrl = $job->desktopImageUrl($branch->unique_hash);
        if ($desktopUrl !== null) {
            $data['image_path'] = $desktopUrl;
        }

        return $data;
    }

    // Electron calls this after attempting to print
    public function update(Request $request, PrintJob $printJob)
    {

        $request->validate([
            'status'      => 'required|in:done,failed',
            'printed_at'  => 'nullable|date',
            'error'       => 'nullable|string',
            'printer'     => 'nullable|string',
        ]);

        $printJob->update([
            'status'     => $request->status,
            'error' => $request->has('error') ? $request->error : null,
            'response_printer'    => $request->printer,
            'printed_at' => $request->printed_at,
        ]);

        return response()->json(['message' => 'Print job updated', 'status' => 'success']);
    }

    public function complete(Request $request, $printJobId)
    {
        $printJob = PrintJob::findOrFail($printJobId);

        $printJob->update([
            'status' => 'completed',
            'printed_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Print job marked as completed'
        ]);
    }

    public function failed(Request $request, $printJobId)
    {
        $printJob = PrintJob::findOrFail($printJobId);
        $printJob->update([
            'status' => 'failed'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Print job marked as failed'
        ]);
    }

    public function pending(Request $request, $printId)
    {
        /** @var Branch $branch */
        $branch = $request->get('branch');

        $printJobs = PrintJob::where('printer_id', $printId)
            ->where('branch_id', $branch->id)
            ->where('status', 'pending')
            ->with('printer:id,name,printing_choice,print_format,share_name,type')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'print_jobs' => $printJobs->map(fn (PrintJob $job) => $this->formatJobForDesktop($job, $branch))->values(),
        ]);
    }
}
