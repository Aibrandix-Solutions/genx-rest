<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payslips - {{ $monthLabel }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #333; }

        .payslip {
            padding: 22px 28px;
        }

        /* ── Header ── */
        .ps-header-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .ps-logo { width: 70px; height: 70px; object-fit: contain; }
        .ps-company-name { font-size: 20px; font-weight: bold; color: #2c3e50; }
        .ps-company-info { font-size: 10px; color: #666; margin-top: 2px; line-height: 1.5; }
        .ps-badge {
            background: #2c3e50;
            color: white;
            padding: 10px 18px;
            text-align: center;
        }
        .ps-badge-title { font-size: 15px; font-weight: bold; letter-spacing: 1px; }
        .ps-badge-period { font-size: 11px; margin-top: 3px; opacity: 0.85; }

        .ps-divider { border-top: 2px solid #2c3e50; margin-bottom: 12px; }

        /* ── Employee info ── */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            border: 1px solid #ddd;
        }
        .info-table .section-header {
            background: #f0f4f8;
            font-weight: bold;
            font-size: 11px;
            padding: 6px 10px;
            border-bottom: 1px solid #ddd;
        }
        .info-table td {
            padding: 5px 10px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: top;
        }
        .info-table .label { font-weight: bold; color: #555; width: 16%; white-space: nowrap; }
        .info-table tr:nth-child(even) td { background: #fafafa; }

        /* ── Attendance summary ── */
        .att-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            text-align: center;
        }
        .att-table th {
            background: #2c3e50;
            color: white;
            padding: 6px 5px;
            border: 1px solid #2c3e50;
            font-size: 10px;
        }
        .att-table td {
            padding: 5px;
            border: 1px solid #ddd;
            font-size: 11px;
        }

        /* ── Earnings / Deductions ── */
        .ed-outer { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .ed-outer > tbody > tr > td { vertical-align: top; }
        .ed-cell { width: 49%; }
        .ed-spacer { width: 2%; }

        .ed-table { width: 100%; border-collapse: collapse; border: 1px solid #ddd; }
        .ed-head {
            color: white;
            padding: 7px 10px;
            font-size: 12px;
            font-weight: bold;
        }
        .ed-head-earn { background: #27ae60; }
        .ed-head-ded  { background: #e74c3c; }
        .ed-table td {
            padding: 5px 10px;
            border-bottom: 1px solid #eee;
            font-size: 11px;
        }
        .ed-table .amount { text-align: right; font-weight: bold; }
        .ed-table .lbl { color: #555; }
        .ed-table .alt { background: #f9f9f9; }
        .ed-total-earn { background: #e8f5e9; }
        .ed-total-ded  { background: #fdecea; }
        .ed-total-label { font-weight: bold; padding: 6px 10px; }
        .ed-total-amount { text-align: right; font-weight: bold; font-size: 12px; padding: 6px 10px; }
        .earn-color { color: #27ae60; }
        .ded-color  { color: #e74c3c; }

        /* ── Net payable ── */
        .net-bar {
            background: #2c3e50;
            color: white;
            padding: 12px 18px;
            margin-bottom: 10px;
        }
        .net-bar-label { font-size: 12px; display: inline; }
        .net-bar-amount { font-size: 19px; font-weight: bold; margin-left: 10px; }

        .payment-date { margin-bottom: 18px; color: #555; font-size: 11px; }

        /* ── Signatures ── */
        .sig-table { width: 100%; border-collapse: collapse; margin-top: 25px; }
        .sig-table td { text-align: center; padding-top: 6px; font-size: 10px; color: #555; }
        .sig-line { border-top: 1px solid #333; }

        /* ── Footer note ── */
        .ps-note { text-align: center; margin-top: 18px; font-size: 9px; color: #aaa; }
    </style>
</head>
<body>

@foreach($rows as $index => $row)

    @if($index > 0)
    <div style="page-break-before: always;"></div>
    @endif

    <div class="payslip">

        {{-- ── Header ── --}}
        <table class="ps-header-table">
            <tr>
                @if(!empty($logoPath))
                <td style="width:80px; vertical-align:middle;">
                    <img src="{{ $logoPath }}" alt="Logo" class="ps-logo">
                </td>
                @endif
                <td style="vertical-align:middle; padding-left:12px;">
                    <div class="ps-company-name">{{ $restaurant->name }}</div>
                    <div class="ps-company-info">
                        @if($restaurant->address ?? false){{ $restaurant->address }}<br>@endif
                        @if($restaurant->phone_number ?? false)Phone: {{ $restaurant->phone_number }}@endif
                    </div>
                </td>
                <td style="width:140px; vertical-align:top; text-align:right;">
                    <div class="ps-badge">
                        <div class="ps-badge-title">PAYSLIP</div>
                        <div class="ps-badge-period">{{ $monthLabel }}</div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="ps-divider"></div>

        {{-- ── Employee Details ── --}}
        <table class="info-table">
            <tr>
                <td colspan="4" class="section-header">Employee Details</td>
            </tr>
            <tr>
                <td class="label">Name</td>
                <td style="width:34%;">{{ $row['name'] }}</td>
                <td class="label">Staff Code</td>
                <td style="width:34%;">{{ $row['staff_code'] }}</td>
            </tr>
            <tr>
                <td class="label">Department</td>
                <td>{{ $row['department'] ?? '—' }}</td>
                <td class="label">Designation</td>
                <td>{{ $row['designation'] ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">Branch</td>
                <td>{{ $branchName ?: '—' }}</td>
                <td class="label">Pay Period</td>
                <td>{{ $monthLabel }}</td>
            </tr>
        </table>

        {{-- ── Attendance Summary ── --}}
        <table class="att-table">
            <thead>
                <tr>
                    <th>Calendar Days</th>
                    <th>Days Worked</th>
                    <th>Leave Days</th>
                    <th>Basic Rate / Day</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $row['total_of_working_days'] }}</td>
                    <td>{{ $row['total_working_days'] }}</td>
                    <td>{{ $row['total_leave'] }}</td>
                    <td>{{ number_format((float)$row['monthly_basic_salary_per_day'], 2) }}</td>
                </tr>
            </tbody>
        </table>

        {{-- ── Earnings & Deductions ── --}}
        <table class="ed-outer">
            <tbody>
                <tr>
                    {{-- Earnings --}}
                    <td class="ed-cell">
                        <table class="ed-table">
                            <tr>
                                <td colspan="2" class="ed-head ed-head-earn">EARNINGS</td>
                            </tr>
                            <tr>
                                <td class="lbl">Monthly Basic Salary</td>
                                <td class="amount">{{ number_format((float)$row['monthly_basic_salary'], 2) }}</td>
                            </tr>
                            <tr class="alt">
                                <td class="lbl">Additional Pay</td>
                                <td class="amount">{{ number_format((float)$row['additional_pay'], 2) }}</td>
                            </tr>
                            <tr class="ed-total-earn">
                                <td class="ed-total-label">TOTAL EARNINGS</td>
                                <td class="ed-total-amount earn-color">{{ number_format((float)$row['total_earning'], 2) }}</td>
                            </tr>
                        </table>
                    </td>

                    <td class="ed-spacer"></td>

                    {{-- Deductions --}}
                    <td class="ed-cell">
                        <table class="ed-table">
                            <tr>
                                <td colspan="2" class="ed-head ed-head-ded">DEDUCTIONS</td>
                            </tr>
                            <tr>
                                <td class="lbl">Advance</td>
                                <td class="amount">{{ number_format((float)$row['advance'], 2) }}</td>
                            </tr>
                            <tr class="alt">
                                <td class="lbl">EPF - Employee ({{ rtrim(rtrim(number_format((float)($row['epf_rate'] ?? 8), 2, '.', ''), '0'), '.') }}%)</td>
                                <td class="amount">{{ number_format((float)$row['epf'], 2) }}</td>
                            </tr>
                            <tr>
                                <td class="lbl">Time Deduction</td>
                                <td class="amount">{{ number_format((float)$row['time_deduction'], 2) }}</td>
                            </tr>
                            <tr class="alt">
                                <td class="lbl">Credit Purchase</td>
                                <td class="amount">{{ number_format((float)$row['credit_purchase'], 2) }}</td>
                            </tr>
                            <tr>
                                <td class="lbl">Other Deduction</td>
                                <td class="amount">{{ number_format((float)$row['other_deduction'], 2) }}</td>
                            </tr>
                            <tr class="ed-total-ded">
                                <td class="ed-total-label">TOTAL DEDUCTIONS</td>
                                <td class="ed-total-amount ded-color">{{ number_format((float)$row['total_of_deduction'], 2) }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </tbody>
        </table>

        {{-- ── Net Payable ── --}}
        <div class="net-bar" style="text-align:right;">
            <span class="net-bar-label">NET PAYABLE SALARY</span>
            <span class="net-bar-amount">{{ number_format((float)$row['payable_salary'], 2) }}</span>
        </div>

        @if(!empty($row['payment_date']))
        <div class="payment-date">Payment Date: <strong>{{ $row['payment_date'] }}</strong></div>
        @endif

        {{-- ── Signatures ── --}}
        <table class="sig-table">
            <tr>
                <td style="width:40%;" class="sig-line">Employee Signature</td>
                <td style="width:20%;"></td>
                <td style="width:40%;" class="sig-line">Authorized Signatory</td>
            </tr>
            <tr>
                <td style="padding-top:4px; font-size:10px; color:#777;">{{ $row['name'] }}</td>
                <td></td>
                <td style="padding-top:4px; font-size:10px; color:#777;">{{ $restaurant->name }}</td>
            </tr>
        </table>

        <div class="ps-note">
            This is a computer-generated payslip. Confidential — for recipient only.
        </div>

    </div>

@endforeach

</body>
</html>
