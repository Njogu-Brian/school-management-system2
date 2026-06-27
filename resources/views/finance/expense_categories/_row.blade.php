<tr>
  <td><span style="padding-left: {{ $depth * 18 }}px">{{ $category->code }}</span></td>
  <td>{{ $category->name }}</td>
  <td>{{ optional($category->account)->fullName() ?? '—' }}</td>
  <td>@if($category->is_header)<span class="badge bg-light text-dark">Group</span>@else<span class="badge bg-primary">Selectable</span>@endif</td>
  <td>{{ $category->is_active ? 'Active' : 'Inactive' }}</td>
  <td class="text-end">
    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#edit-cat-{{ $category->id }}">
      <i class="bi bi-pencil"></i> Edit
    </button>
  </td>
</tr>
<tr class="collapse" id="edit-cat-{{ $category->id }}">
  <td colspan="6" class="bg-light">
    <form method="POST" action="{{ route('finance.expense-categories.update', $category) }}" class="row g-2 align-items-end py-2 px-1">
      @csrf
      @method('PUT')
      <div class="col-md-3">
        <label class="form-label small mb-1">Name</label>
        <input class="finance-form-control" name="name" value="{{ $category->name }}" required>
      </div>
      <div class="col-md-3">
        <label class="form-label small mb-1">Parent group (move)</label>
        <select class="finance-form-select" name="parent_id">
          <option value="">Top-level group</option>
          @foreach($headerParents as $parent)
            @if($parent->id !== $category->id)
              <option value="{{ $parent->id }}" @selected($category->parent_id === $parent->id)>{{ $parent->name }}</option>
            @endif
          @endforeach
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-1">GL account</label>
        <select class="finance-form-select" name="account_id">
          <option value="">— None —</option>
          @foreach($accountGroups as $groupLabel => $groupAccounts)
            <optgroup label="{{ $groupLabel }}">
              @foreach($groupAccounts as $account)
                <option value="{{ $account->id }}" @selected($category->account_id === $account->id)>{{ $account->code }} — {{ $account->name }}</option>
              @endforeach
            </optgroup>
          @endforeach
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-1">Type</label>
        <select class="finance-form-select" name="is_header">
          <option value="0" @selected(! $category->is_header)>Line item</option>
          <option value="1" @selected($category->is_header)>Group header</option>
        </select>
      </div>
      <div class="col-md-1">
        <label class="form-label small mb-1">Status</label>
        <select class="finance-form-select" name="is_active">
          <option value="1" @selected($category->is_active)>Active</option>
          <option value="0" @selected(! $category->is_active)>Inactive</option>
        </select>
      </div>
      <div class="col-md-1">
        <label class="form-label small mb-1 d-none d-md-block">&nbsp;</label>
        <button class="btn btn-primary w-100" type="submit">Save</button>
      </div>
    </form>
  </td>
</tr>
@foreach($category->children as $child)
  @include('finance.expense_categories._row', ['category' => $child, 'depth' => $depth + 1, 'headerParents' => $headerParents, 'accountGroups' => $accountGroups])
@endforeach
