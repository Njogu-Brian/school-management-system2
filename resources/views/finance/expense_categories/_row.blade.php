<tr>
  <td><span style="padding-left: {{ $depth * 18 }}px">{{ $category->code }}</span></td>
  <td>{{ $category->name }}</td>
  <td>{{ optional($category->account)->fullName() ?? '—' }}</td>
  <td>@if($category->is_header)<span class="badge bg-light text-dark">Group</span>@else<span class="badge bg-primary">Selectable</span>@endif</td>
  <td>{{ $category->is_active ? 'Active' : 'Inactive' }}</td>
</tr>
@foreach($category->children as $child)
  @include('finance.expense_categories._row', ['category' => $child, 'depth' => $depth + 1])
@endforeach
