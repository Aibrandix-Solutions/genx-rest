<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        h2 { margin: 0 0 10px 0; font-size: 14px; text-align: center; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #111; padding: 4px; }
        th { background: #f2f2f2; font-weight: bold; }
    </style>
</head>
<body>
    <h2>{{ $title }}</h2>

    <table>
        <thead>
            <tr>
                <th>S/N</th>
                <th>Name</th>
                <th>Staff Code</th>
                <th>Total Working Days</th>
                <th>Total Leave</th>
                <th>Total Worked</th>
                <th>Basic/Day</th>
                <th>Monthly Basic</th>
                <th>Additional</th>
                <th>Total Earning</th>
                <th>Advance</th>
                <th>EPF (8%)</th>
                <th>Time Ded.</th>
                <th>Credit Purch.</th>
                <th>Other Ded.</th>
                <th>Total Ded.</th>
                <th>Payable</th>
                <th>Payment Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $r)
                <tr>
                    <td>{{ $r['sn'] }}</td>
                    <td>{{ $r['name'] }}</td>
                    <td>{{ $r['staff_code'] }}</td>
                    <td>{{ $r['total_of_working_days'] }}</td>
                    <td>{{ $r['total_leave'] }}</td>
                    <td>{{ $r['total_working_days'] }}</td>
                    <td>{{ number_format((float)$r['monthly_basic_salary_per_day'], 2) }}</td>
                    <td>{{ number_format((float)$r['monthly_basic_salary'], 2) }}</td>
                    <td>{{ number_format((float)$r['additional_pay'], 2) }}</td>
                    <td>{{ number_format((float)$r['total_earning'], 2) }}</td>
                    <td>{{ number_format((float)$r['advance'], 2) }}</td>
                    <td>{{ number_format((float)$r['epf'], 2) }}</td>
                    <td>{{ number_format((float)$r['time_deduction'], 2) }}</td>
                    <td>{{ number_format((float)$r['credit_purchase'], 2) }}</td>
                    <td>{{ number_format((float)($r['other_deduction'] ?? 0), 2) }}</td>
                    <td>{{ number_format((float)$r['total_of_deduction'], 2) }}</td>
                    <td><strong>{{ number_format((float)$r['payable_salary'], 2) }}</strong></td>
                    <td>{{ $r['payment_date'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
