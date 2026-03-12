<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 9px; color: #333; }

        .header { text-align: center; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 2px solid #2c3e50; }
        .logo { width: 65px; height: 65px; object-fit: contain; display: block; margin: 0 auto 6px; }
        .company-name { font-size: 18px; font-weight: bold; color: #2c3e50; margin-bottom: 3px; }
        .report-title { font-size: 13px; font-weight: bold; margin-bottom: 2px; }
        .sub-info { font-size: 10px; color: #666; }

        table { width: 100%; border-collapse: collapse; }
        th {
            background-color: #2c3e50;
            color: white;
            padding: 5px 3px;
            text-align: center;
            border: 1px solid #2c3e50;
            font-size: 8px;
        }
        td {
            padding: 4px 3px;
            border: 1px solid #d0d0d0;
            text-align: center;
            font-size: 8px;
        }
        tbody tr:nth-child(even) { background-color: #f5f7fa; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }

        tfoot tr td {
            background-color: #ecf0f1;
            font-weight: bold;
            border: 1px solid #b0b0b0;
        }

        .footer {
            margin-top: 14px;
            font-size: 8px;
            color: #999;
            text-align: right;
        }
    </style>
</head>
<body>

    <div class="header">
        @if(!empty($logoPath))
            <img src="{{ $logoPath }}" alt="Logo" class="logo">
        @endif
        <div class="company-name">{{ $restaurant->name }}</div>
        <div class="report-title">Monthly Payroll Report</div>
        <div class="sub-info">Period: {{ $monthLabel }} &nbsp;|&nbsp; Branch: {{ $branchName ?: '—' }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2">#</th>
                <th rowspan="2">Name</th>
                <th rowspan="2">Staff Code</th>
                <th colspan="3">Attendance</th>
                <th rowspan="2">Basic/Day</th>
                <th colspan="3" style="background:#1a5276;">Earnings</th>
                <th colspan="5" style="background:#7b241c;">Deductions</th>
                <th rowspan="2" style="background:#145a32;">Total Deduction</th>
                <th rowspan="2" style="background:#145a32;">Payable Salary</th>
                <th rowspan="2">Payment Date</th>
            </tr>
            <tr>
                <th>Days in Month</th>
                <th>Days Worked</th>
                <th>Leave Days</th>
                <th style="background:#1a5276;">Monthly Basic</th>
                <th style="background:#1a5276;">Add. Pay</th>
                <th style="background:#1a5276;">Total Earning</th>
                <th style="background:#7b241c;">Advance</th>
                <th style="background:#7b241c;">EPF (8%)</th>
                <th style="background:#7b241c;">Time Deduct.</th>
                <th style="background:#7b241c;">Credit Purch.</th>
                <th style="background:#7b241c;">Other Deduct.</th>
            </tr>
        </thead>
        <tbody>
            @php
            $totals = [
                'monthly_basic_salary' => 0,
                'additional_pay'       => 0,
                'total_earning'        => 0,
                'advance'              => 0,
                'epf'                  => 0,
                'time_deduction'       => 0,
                'credit_purchase'      => 0,
                'other_deduction'      => 0,
                'total_of_deduction'   => 0,
                'payable_salary'       => 0,
            ];
            @endphp

            @forelse($rows as $row)
            @php
                $totals['monthly_basic_salary'] += $row['monthly_basic_salary'];
                $totals['additional_pay']       += $row['additional_pay'];
                $totals['total_earning']        += $row['total_earning'];
                $totals['advance']              += $row['advance'];
                $totals['epf']                  += $row['epf'];
                $totals['time_deduction']       += $row['time_deduction'];
                $totals['credit_purchase']      += $row['credit_purchase'];
                $totals['other_deduction']      += $row['other_deduction'];
                $totals['total_of_deduction']   += $row['total_of_deduction'];
                $totals['payable_salary']       += $row['payable_salary'];
            @endphp
            <tr>
                <td>{{ $row['sn'] }}</td>
                <td class="text-left">{{ $row['name'] }}</td>
                <td>{{ $row['staff_code'] }}</td>
                <td>{{ $row['total_of_working_days'] }}</td>
                <td>{{ $row['total_working_days'] }}</td>
                <td>{{ $row['total_leave'] }}</td>
                <td class="text-right">{{ number_format((float)$row['monthly_basic_salary_per_day'], 2) }}</td>
                <td class="text-right">{{ number_format((float)$row['monthly_basic_salary'], 2) }}</td>
                <td class="text-right">{{ number_format((float)$row['additional_pay'], 2) }}</td>
                <td class="text-right" style="font-weight:bold;">{{ number_format((float)$row['total_earning'], 2) }}</td>
                <td class="text-right">{{ number_format((float)$row['advance'], 2) }}</td>
                <td class="text-right">{{ number_format((float)$row['epf'], 2) }}</td>
                <td class="text-right">{{ number_format((float)$row['time_deduction'], 2) }}</td>
                <td class="text-right">{{ number_format((float)$row['credit_purchase'], 2) }}</td>
                <td class="text-right">{{ number_format((float)$row['other_deduction'], 2) }}</td>
                <td class="text-right" style="font-weight:bold;">{{ number_format((float)$row['total_of_deduction'], 2) }}</td>
                <td class="text-right" style="font-weight:bold;">{{ number_format((float)$row['payable_salary'], 2) }}</td>
                <td>{{ $row['payment_date'] ?? '—' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="18" style="text-align:center; padding:12px; color:#999;">No payroll records found.</td>
            </tr>
            @endforelse
        </tbody>
        @if(count($rows) > 0)
        <tfoot>
            <tr>
                <td colspan="7" class="text-right" style="padding-right:6px; font-size:9px;">TOTALS</td>
                <td class="text-right">{{ number_format($totals['monthly_basic_salary'], 2) }}</td>
                <td class="text-right">{{ number_format($totals['additional_pay'], 2) }}</td>
                <td class="text-right">{{ number_format($totals['total_earning'], 2) }}</td>
                <td class="text-right">{{ number_format($totals['advance'], 2) }}</td>
                <td class="text-right">{{ number_format($totals['epf'], 2) }}</td>
                <td class="text-right">{{ number_format($totals['time_deduction'], 2) }}</td>
                <td class="text-right">{{ number_format($totals['credit_purchase'], 2) }}</td>
                <td class="text-right">{{ number_format($totals['other_deduction'], 2) }}</td>
                <td class="text-right">{{ number_format($totals['total_of_deduction'], 2) }}</td>
                <td class="text-right">{{ number_format($totals['payable_salary'], 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="footer">Generated: {{ now()->format('d M Y, H:i') }} &nbsp;|&nbsp; {{ $title }}</div>

</body>
</html>
