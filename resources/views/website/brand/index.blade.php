@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        @include('website.partials.header', [
            'title' => 'Brand Content',
            'icon' => 'bi bi-palette',
            'subtitle' => 'Homepage trust pills, school cards, journey, co-curricular, faith & leadership',
        ])

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="settings-card mb-4">
            <div class="card-header"><strong>Add brand block</strong></div>
            <div class="card-body">
                <form action="{{ route('website.brand.store') }}" method="POST" class="row g-3">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label">Block type</label>
                        <select name="block_type" class="form-select" required>
                            @foreach($types as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4"><label class="form-label">Title</label><input name="title" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Subtitle</label><input name="subtitle" class="form-control"></div>
                    <div class="col-12"><label class="form-label">Body</label><textarea name="body" class="form-control" rows="2"></textarea></div>
                    <div class="col-md-6"><label class="form-label">Image URL</label><input name="image_url" class="form-control" placeholder="https://..."></div>
                    <div class="col-md-6"><label class="form-label">Link URL</label><input name="link_url" class="form-control" placeholder="/academics"></div>
                    <div class="col-md-3"><label class="form-label">Sort order</label><input type="number" name="sort_order" class="form-control" value="0"></div>
                    <div class="col-md-3 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked id="active"><label class="form-check-label" for="active">Active</label></div></div>
                    <div class="col-12"><button type="submit" class="btn btn-settings-primary">Add</button></div>
                </form>
            </div>
        </div>

        @foreach($types as $typeKey => $typeLabel)
            @php $group = $items->where('block_type', $typeKey); @endphp
            @if($group->isNotEmpty())
                <div class="settings-card mb-3">
                    <div class="card-header"><strong>{{ $typeLabel }}</strong></div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-modern mb-0">
                                <thead class="table-light"><tr><th>Title</th><th>Subtitle</th><th>Order</th><th>Active</th><th></th></tr></thead>
                                <tbody>
                                    @foreach($group as $item)
                                        <tr>
                                            <td colspan="5" class="p-0">
                                                <form action="{{ route('website.brand.update', $item) }}" method="POST" class="p-3 border-bottom">
                                                    @csrf @method('PUT')
                                                    <div class="row g-2 align-items-end">
                                                        <div class="col-md-3"><input name="title" class="form-control form-control-sm" value="{{ $item->title }}"></div>
                                                        <div class="col-md-2"><input name="subtitle" class="form-control form-control-sm" value="{{ $item->subtitle }}"></div>
                                                        <div class="col-md-3"><input name="body" class="form-control form-control-sm" value="{{ $item->body }}"></div>
                                                        <div class="col-md-2"><input name="image_url" class="form-control form-control-sm" value="{{ $item->image_url }}" placeholder="Image URL"></div>
                                                        <div class="col-md-1"><input type="number" name="sort_order" class="form-control form-control-sm" value="{{ $item->sort_order }}"></div>
                                                        <div class="col-md-1"><input type="checkbox" name="is_active" value="1" {{ $item->is_active ? 'checked' : '' }}></div>
                                                        <div class="col-md-12 mt-1 d-flex gap-2">
                                                            <button class="btn btn-sm btn-settings-primary">Save</button>
                                                        </div>
                                                    </div>
                                                </form>
                                                <form action="{{ route('website.brand.destroy', $item) }}" method="POST" class="px-3 pb-2" onsubmit="return confirm('Delete?');">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
</div>
@endsection
