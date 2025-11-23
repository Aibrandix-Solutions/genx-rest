<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Inventory\Entities\AccountTransaction;
use Modules\Inventory\Exports\PaymentAccountReportExport;
use Maatwebsite\Excel\Facades\Excel;

class PaymentAccountController extends Controller
{
    public function index()
    {
        abort_if(!in_array('Inventory', restaurant_modules()), 403);
        return view('inventory::payment_accounts.index');
    }

    public function report()
    {
        abort_if(!in_array('Inventory', restaurant_modules()), 403);
        return view('inventory::payment_accounts.report');
    }

    public function exportReport(Request $request)
    {
        abort_if(!in_array('Inventory', restaurant_modules()), 403);
        return Excel::download(new PaymentAccountReportExport($request), 'payment_account_report.xlsx');
    }

    public function balanceSheet()
    {
        abort_if(!in_array('Inventory', restaurant_modules()), 403);
        return view('inventory::payment_accounts.balance_sheet');
    }

    public function trialBalance()
    {
        abort_if(!in_array('Inventory', restaurant_modules()), 403);
        return view('inventory::payment_accounts.trial_balance');
    }

    public function cashFlow()
    {
        abort_if(!in_array('Inventory', restaurant_modules()), 403);
        return view('inventory::payment_accounts.cash_flow');
    }
}
