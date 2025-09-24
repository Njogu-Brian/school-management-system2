<h5 class="mt-4">Bank & Statutory Details</h5>
<div class="row">
    <div class="col-md-3 mb-3">
        <label>KRA PIN</label>
        <input type="text" name="kra_pin" class="form-control"
               value="{{ old('kra_pin',$staff->kra_pin ?? '') }}">
    </div>
    <div class="col-md-3 mb-3">
        <label>NSSF</label>
        <input type="text" name="nssf" class="form-control"
               value="{{ old('nssf',$staff->nssf ?? '') }}">
    </div>
    <div class="col-md-3 mb-3">
        <label>NHIF</label>
        <input type="text" name="nhif" class="form-control"
               value="{{ old('nhif',$staff->nhif ?? '') }}">
    </div>
    <div class="col-md-3 mb-3">
        <label>Bank Name</label>
        <input type="text" name="bank_name" class="form-control"
               value="{{ old('bank_name',$staff->bank_name ?? '') }}">
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label>Bank Branch</label>
        <input type="text" name="bank_branch" class="form-control"
               value="{{ old('bank_branch',$staff->bank_branch ?? '') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label>Bank Account</label>
        <input type="text" name="bank_account" class="form-control"
               value="{{ old('bank_account',$staff->bank_account ?? '') }}">
    </div>
</div>
