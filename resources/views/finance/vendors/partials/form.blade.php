<div class="row g-3 mb-3">
  <div class="col-md-6"><label class="finance-form-label">Name</label><input name="name" class="finance-form-control" value="{{ old('name', $vendor->name ?? '') }}" required></div>
  <div class="col-md-3"><label class="finance-form-label">Type</label><input name="type" class="finance-form-control" value="{{ old('type', $vendor->type ?? '') }}"></div>
  <div class="col-md-3"><label class="finance-form-label">Phone</label><input name="phone" class="finance-form-control" value="{{ old('phone', $vendor->phone ?? '') }}"></div>
  <div class="col-md-4"><label class="finance-form-label">Email</label><input type="email" name="email" class="finance-form-control" value="{{ old('email', $vendor->email ?? '') }}"></div>
  <div class="col-md-4"><label class="finance-form-label">Tax PIN</label><input name="tax_pin" class="finance-form-control" value="{{ old('tax_pin', $vendor->tax_pin ?? '') }}"></div>
  <div class="col-md-2"><label class="finance-form-label">Terms (days)</label><input type="number" name="payable_terms" class="finance-form-control" value="{{ old('payable_terms', $vendor->payable_terms ?? 0) }}"></div>
  <div class="col-md-2"><label class="finance-form-label">Active</label><select name="is_active" class="finance-form-select"><option value="1" @selected((string)old('is_active', $vendor->is_active ?? 1)==='1')>Yes</option><option value="0" @selected((string)old('is_active', $vendor->is_active ?? 1)==='0')>No</option></select></div>
</div>
