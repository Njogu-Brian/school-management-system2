@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <a href="{{ route('inventory.requisitions.index') }}" class="text-decoration-none">
            <i class="bi bi-arrow-left"></i> Requisitions
        </a>
    </div>

    @include('partials.alerts')

    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h4 mb-3">New Requisition</h1>
            <form method="POST" action="{{ route('inventory.requisitions.store') }}" id="requisitionForm">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Type</label>
                        <select name="type" id="requisitionType" class="form-select" required>
                            <option value="inventory" @selected(old('type') === 'inventory')>Inventory Item (issue from store)</option>
                            <option value="requirement" @selected(old('type') === 'requirement')>Requirement Item (for parent communication)</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Purpose / Reason</label>
                        <input type="text" name="purpose" class="form-control" value="{{ old('purpose') }}" placeholder="E.g. Extra art supplies for Grade 4">
                    </div>
                </div>

                <div class="table-responsive mt-4">
                    <table class="table table-bordered align-middle" id="itemsTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 220px;">Inventory Item</th>
                                <th style="width: 220px;">Requirement Type</th>
                                <th>Custom Name</th>
                                <th style="width: 120px;">Brand</th>
                                <th style="width: 120px;">Quantity</th>
                                <th style="width: 110px;">Unit</th>
                                <th>Purpose</th>
                                <th style="width: 60px;"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                        </tbody>
                    </table>
                </div>

                <button type="button" class="btn btn-outline-secondary" id="addItemBtn">
                <i class="bi bi-plus-circle"></i> Add Item
                </button>

                <div class="text-end mt-4">
                    <button class="btn btn-primary">
                        <i class="bi bi-send-check"></i> Submit Requisition
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<template id="itemRowTemplate">
    <tr>
        <td>
            <select class="form-select inventory-select" name="items[__INDEX__][inventory_item_id]">
                <option value="">Select item</option>
                @foreach($inventoryItems as $inventory)
                    <option value="{{ $inventory->id }}">{{ $inventory->name }} ({{ $inventory->quantity }} {{ $inventory->unit }})</option>
                @endforeach
            </select>
        </td>
        <td>
            <select class="form-select requirement-select" name="items[__INDEX__][requirement_type_id]">
                <option value="">Select type</option>
                @foreach($requirementTypes as $type)
                    <option value="{{ $type->id }}">{{ $type->name }} • {{ $type->category }}</option>
                @endforeach
            </select>
        </td>
        <td>
            <input type="text" class="form-control" name="items[__INDEX__][item_name]" required placeholder="E.g. A4 paper">
        </td>
        <td>
            <input type="text" class="form-control" name="items[__INDEX__][brand]" placeholder="Brand / variant">
        </td>
        <td>
            <input type="number" step="0.01" min="0" class="form-control" name="items[__INDEX__][quantity_requested]" required>
        </td>
        <td>
            <input type="text" class="form-control" name="items[__INDEX__][unit]" required value="pcs">
        </td>
        <td>
            <input type="text" class="form-control" name="items[__INDEX__][purpose]" placeholder="Optional note">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger remove-row">
                <i class="bi bi-x-lg"></i>
            </button>
        </td>
    </tr>
</template>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const typeSelect = document.getElementById('requisitionType');
    const itemsBody = document.getElementById('itemsBody');
    const template = document.getElementById('itemRowTemplate').innerHTML;
    const addBtn = document.getElementById('addItemBtn');
    let index = 0;

    const addRow = () => {
        const html = template.replace(/__INDEX__/g, index++);
        itemsBody.insertAdjacentHTML('beforeend', html);
        refreshVisibility();
    };

    const refreshVisibility = () => {
        const isInventory = typeSelect.value === 'inventory';
        itemsBody.querySelectorAll('tr').forEach(row => {
            const inventorySelect = row.querySelector('.inventory-select');
            const requirementSelect = row.querySelector('.requirement-select');
            inventorySelect.disabled = !isInventory;
            requirementSelect.disabled = isInventory;
            inventorySelect.parentElement.classList.toggle('opacity-50', !isInventory);
            requirementSelect.parentElement.classList.toggle('opacity-50', isInventory);
        });
    };

    addBtn.addEventListener('click', addRow);
    typeSelect.addEventListener('change', refreshVisibility);
    itemsBody.addEventListener('click', (event) => {
        if (event.target.closest('.remove-row')) {
            event.target.closest('tr').remove();
        }
    });

    // Seed at least one row
    if (itemsBody.children.length === 0) {
        addRow();
    }
});
</script>
@endpush
@endsection

