<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Inventory\Entities\InventoryTransfer;

class StockTransferController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        abort_if(!in_array('Inventory', restaurant_modules()), 403);
        abort_if(!user_can('Show Stock Transfer'), 403);
        return view('inventory::stock-transfers.index');
    }

    /**
     * Print-friendly stock transfers report. Uses the same filters as the
     * transfers list but without pagination so all matching rows print.
     */
    public function reportPrint(Request $request)
    {
        abort_if(!in_array('Inventory', restaurant_modules()), 403);
        abort_if(!user_can('Show Stock Transfer'), 403);

        $search = (string) $request->query('search', '');
        $filterType = (string) $request->query('filterType', 'all');
        $statusFilter = (string) $request->query('statusFilter', 'all');
        $startDate = $request->query('startDate');
        $endDate = $request->query('endDate');

        $baseQuery = InventoryTransfer::query()
            ->where('restaurant_id', restaurant()->id);

        if ($statusFilter !== '' && $statusFilter !== 'all') {
            $baseQuery->where('status', $statusFilter);
        }

        if ($filterType === 'outgoing') {
            $baseQuery->where(function ($q) {
                $q->where('source_branch_id', branch()->id)
                    ->orWhereHas('sourceLocation', fn ($sq) => $sq->where('branch_id', branch()->id));
            });
        } elseif ($filterType === 'incoming') {
            $baseQuery->where(function ($q) {
                $q->where('destination_branch_id', branch()->id)
                    ->orWhereHas('destinationLocation', fn ($sq) => $sq->where('branch_id', branch()->id));
            });
        }

        if ($search !== '') {
            $baseQuery->where(function ($q) use ($search) {
                $q->where('transfer_number', 'like', '%' . $search . '%')
                    ->orWhereHas('sourceBranch', function ($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('destinationBranch', function ($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        if ($startDate && $endDate) {
            $baseQuery->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        }

        $stats = [
            'total_transfers' => (clone $baseQuery)->count(),
            'pending_transfers' => (clone $baseQuery)->where('status', 'pending')->count(),
            'active_transfers' => (clone $baseQuery)->whereIn('status', ['pending', 'in_transit'])->count(),
            'completed_transfers' => (clone $baseQuery)->where('status', 'completed')->count(),
        ];

        $transfers = (clone $baseQuery)
            ->with([
                'sourceBranch',
                'destinationBranch',
                'sourceLocation',
                'destinationLocation',
                'items',
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        $statuses = [
            'pending' => trans('inventory::modules.transfers.status_pending'),
            'in_transit' => trans('inventory::modules.transfers.status_in_transit'),
            'completed' => trans('inventory::modules.transfers.status_completed'),
            'cancelled' => trans('inventory::modules.transfers.status_cancelled'),
        ];

        $filterTypeLabels = [
            'all' => trans('inventory::modules.transfers.all_transfers'),
            'outgoing' => trans('inventory::modules.transfers.outgoing'),
            'incoming' => trans('inventory::modules.transfers.incoming'),
        ];

        return view('inventory::stock-transfers.report-print', [
            'transfers' => $transfers,
            'stats' => $stats,
            'statuses' => $statuses,
            'search' => $search,
            'filterTypeLabel' => $filterTypeLabels[$filterType] ?? $filterTypeLabels['all'],
            'statusFilterLabel' => ($statusFilter !== '' && $statusFilter !== 'all')
                ? ($statuses[$statusFilter] ?? ucfirst(str_replace('_', ' ', $statusFilter)))
                : trans('inventory::modules.transfers.all_status'),
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }
}
