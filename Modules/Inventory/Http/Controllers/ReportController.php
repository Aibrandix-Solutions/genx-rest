<?php

namespace Modules\Inventory\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Inventory\Entities\Supplier;
use Modules\Inventory\Services\ItemInventoryReportService;

class ReportController extends Controller
{
    public function usage(Request $request)
    {
        abort_if(!in_array('Inventory', restaurant_modules()), 403);
        abort_if(!user_can('Show Inventory Report'), 403);
        
        return view('inventory::reports.usage');
    }

    public function turnover()
    {
        abort_if(!in_array('Inventory', restaurant_modules()), 403);

        return view('inventory::reports.turnover');
    }

    public function forecasting()
    {
        abort_if(!in_array('Inventory', restaurant_modules()), 403);

        return view('inventory::reports.forecasting');
    }

    public function cogs()
    {
        abort_if(!in_array('Inventory', restaurant_modules()), 403);

        return view('inventory::reports.cogs');
    }

    public function profitAndLoss()
    {
        abort_if(!in_array('Inventory', restaurant_modules()), 403);

        return view('inventory::reports.profit-and-loss');
    }

    public function itemInventory()
    {
        abort_if(! in_array('Inventory', restaurant_modules()), 403);
        abort_if(! user_can('Show Inventory Report'), 403);

        return view('inventory::reports.item-inventory');
    }

    public function itemPurchases()
    {
        abort_if(! in_array('Inventory', restaurant_modules()), 403);
        abort_if(! user_can('Show Inventory Report'), 403);

        return view('inventory::reports.item-purchases');
    }

    public function transfers()
    {
        abort_if(! in_array('Inventory', restaurant_modules()), 403);
        abort_if(! user_can('Show Inventory Report'), 403);

        return view('inventory::reports.transfers');
    }

    public function itemInventoryPdf(Request $request, ItemInventoryReportService $reportService)
    {
        abort_if(! in_array('Inventory', restaurant_modules()), 403);
        abort_if(! user_can('Show Inventory Report'), 403);

        $startDate = $request->query('startDate') ?: Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate = $request->query('endDate') ?: Carbon::now()->format('Y-m-d');

        $restaurantId = (int) restaurant()->id;
        $filters = $reportService->parseFiltersFromInput($request->query());
        $branchFilter = (string) ($filters['branch_filter'] ?? 'all');
        $supplierId = $filters['supplier_id'] ?? null;
        $purchaseStatus = (string) ($filters['purchase_status'] ?? '');
        $paymentStatus = (string) ($filters['payment_status'] ?? '');

        $summaryRows = $reportService->getItemSummaryRows($restaurantId, $filters);

        $branchLabel = $reportService->resolveBranchLabel($restaurantId, $branchFilter);
        $supplierName = $supplierId
            ? (Supplier::where('restaurant_id', $restaurantId)->find($supplierId)?->name ?? trans('app.all'))
            : trans('app.all');
        $itemLabel = $reportService->resolveItemLabel($restaurantId, $filters['item_id'] ?? null);
        $locationLabel = $reportService->resolveLocationLabel($restaurantId, $filters['location_id'] ?? null);
        $search = (string) ($filters['search'] ?? '');

        $sectionTitle = trans('inventory::modules.reports.item_inventory.title');
        $filename = 'item-inventory-report-' . now()->format('Y-m-d') . '.pdf';

        $pdf = Pdf::loadView('inventory::reports.item-inventory-pdf', [
            'sectionTitle' => $sectionTitle,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'branchLabel' => $branchLabel,
            'supplierName' => $supplierName,
            'purchaseStatus' => $purchaseStatus,
            'paymentStatus' => $paymentStatus,
            'search' => $search,
            'itemLabel' => $itemLabel,
            'locationLabel' => $locationLabel,
            'summaryRows' => $summaryRows,
            'restaurantName' => restaurant()->name ?? config('app.name'),
            'printedBy' => user()->name ?? '',
            'printedAt' => now()->format('Y-m-d H:i'),
        ])->setPaper('a4', 'landscape');

        $pdf->getDomPDF()->set_option('defaultFont', 'Arial');
        $pdf->getDomPDF()->set_option('isRemoteEnabled', true);

        return $pdf->download($filename);
    }
} 