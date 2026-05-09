<!doctype html>
<html>
<head><meta charset="utf-8"><title>Expense Report</title></head>
<body>
  <h2>Expense Report</h2>
  <table width="100%" border="1" cellspacing="0" cellpadding="6">
    <thead><tr><th>No</th><th>Date</th><th>Status</th><th>Vendor</th><th>Total</th></tr></thead>
    <tbody>
      @foreach($expenses as $expense)
      <tr>
        <td>{{ $expense->expense_no }}</td>
        <td>{{ optional($expense->expense_date)->format('Y-m-d') }}</td>
        <td>{{ ucfirst($expense->status) }}</td>
        <td>{{ optional($expense->vendor)->name }}</td>
        <td>{{ number_format((float)$expense->total, 2) }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
</body>
</html>
