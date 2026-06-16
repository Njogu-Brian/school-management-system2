<tr>
  <td><span style="padding-left: {{ $depth * 18 }}px">{{ $account->code }}</span></td>
  <td>{{ $account->name }}</td>
  <td>{{ ucfirst($account->account_type) }}</td>
  <td>{{ strtoupper($account->normal_balance) }}</td>
  <td>
    @if($account->is_system)<span class="badge bg-secondary">System</span>@endif
    {{ $account->is_active ? 'Active' : 'Inactive' }}
    @if(!$account->is_postable)<span class="badge bg-light text-dark">Header</span>@endif
  </td>
</tr>
@foreach($account->children as $child)
  @include('finance.accounting.chart_of_accounts._row', ['account' => $child, 'depth' => $depth + 1])
@endforeach
