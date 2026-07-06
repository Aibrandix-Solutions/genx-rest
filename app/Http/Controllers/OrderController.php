<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\ReceiptSetting;
use App\Models\RestaurantTax;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\OrderNumberSetting;
use App\Helper\Files;
use Illuminate\Support\Facades\File;

class OrderController extends Controller
{

    public function index()
    {
        abort_if(!in_array('Order', restaurant_modules()), 403);
        abort_if((!user_can('Show Order')), 403);
        return view('order.index');
    }

    public function show($id)
    {
        abort_if(! in_array('Order', restaurant_modules()), 403);
        abort_if(! user_can('Show Order'), 403);

        $order = Order::findByIdentifier($id);

        abort_if(! $order, 404);

        if ($order->status === 'kot') {
            return redirect()->to($order->staffDetailUrl());
        }

        return view('order.show', ['id' => $order->id]);
    }

    public function printOrder($id, $width = 80, $thermal = false, $generateImage = false)
    {
        $id = Order::findIdByIdentifier($id) ?? abort(404);

        $payment = Payment::where('order_id', $id)->first();
        $restaurant = restaurant();
        $taxDetails = RestaurantTax::where('restaurant_id', $restaurant->id)->get();
        $order = Order::with([
            'items.menuItem',
            'items.menuItemVariation',
            'items.modifierOptions',
            'items.comboPack',
            'charges.charge',
            'taxes.tax',
            'payments',
            'kot',
            'table',
            'waiter',
            'posUser',
            'customer',
        ])->find($id);
        $receiptSettings = $restaurant->receiptSetting;
        $taxMode = $order?->tax_mode ?? ($restaurant->tax_mode ?? 'order');
        $totalTaxAmount = 0;

        if ($taxMode === 'item') {
            $totalTaxAmount = $order->total_tax_amount ?? 0;
        }

        $content = view('order.print', compact('order', 'receiptSettings', 'taxDetails', 'payment', 'taxMode', 'totalTaxAmount', 'width', 'thermal'));

        if ($generateImage) {
            // return $this->printOrderImage($order, $content);
        }

        return $content;
    }

    /**
     * Generate PDF for order print
     */
    public function generateOrderPdf($id)
    {
        $id = Order::findIdByIdentifier($id) ?? abort(404);

        $payment = Payment::where('order_id', $id)->first();
        $restaurant = restaurant();
        $taxDetails = RestaurantTax::where('restaurant_id', $restaurant->id)->get();
        $order = Order::with([
            'items.menuItem',
            'items.menuItemVariation',
            'items.modifierOptions',
            'items.comboPack',
            'charges.charge',
            'taxes.tax',
            'payments',
            'kot',
            'table',
            'waiter',
            'posUser',
            'customer',
        ])->find($id);
        $receiptSettings = $restaurant->receiptSetting;
        $taxMode = $restaurant->tax_mode ?? 'order';
        $totalTaxAmount = 0;

        if ($taxMode === 'item') {
            $totalTaxAmount = $order->total_tax_amount ?? 0;
        }

        // Generate PDF
        $pdf = Pdf::loadView('order.print-pdf', compact('order', 'receiptSettings', 'taxDetails', 'payment', 'taxMode', 'totalTaxAmount'));

        // Set paper size to A4
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download($order->show_formatted_order_number . '.pdf');
    }

    /**
     * Get PDF content as string for email attachment
     */
    public function getOrderPdfContent($id)
    {
        $payment = Payment::where('order_id', $id)->first();
        $restaurant = restaurant();
        $taxDetails = RestaurantTax::where('restaurant_id', $restaurant->id)->get();
        $order = Order::with([
            'items.menuItem',
            'items.menuItemVariation',
            'items.modifierOptions',
            'items.comboPack',
            'charges.charge',
            'taxes.tax',
            'payments',
            'kot',
            'table',
            'waiter',
            'posUser',
            'customer',
        ])->find($id);
        $receiptSettings = $restaurant->receiptSetting;
        $taxMode = $restaurant->tax_mode ?? 'order';
        $totalTaxAmount = 0;

        if ($taxMode === 'item') {
            $totalTaxAmount = $order->total_tax_amount ?? 0;
        }

        // Generate PDF
        $pdf = Pdf::loadView('order.print-pdf', compact('order', 'receiptSettings', 'taxDetails', 'payment', 'taxMode', 'totalTaxAmount'));

        // Set paper size to A4
        $pdf->setPaper('A4', 'portrait');

        return $pdf->output();
    }

    public function printOrderImage($order, $content)
    {
        $filename = 'order-' . $order->id . '.png';
        $path = Files::UPLOAD_FOLDER . '/print/' . $filename;
        $absolutePath = public_path($path);

        try {
            // For 80mm thermal printer, set width to 576px (standard for 80mm printers)
            \Spatie\Browsershot\Browsershot::html(
                $content
            )
                ->windowSize(100, 100) // Set a reasonable initial height
                ->deviceScaleFactor(2) // Sharper print for thermal printers
                ->fullPage() // Dynamically adjust height based on content
                ->fullWidth() // Dynamically adjust height based on content
                ->save($absolutePath);
        } catch (\Exception $e) {
        }
        return $filename;
    }
}
