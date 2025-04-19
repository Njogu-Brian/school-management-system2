<form action="{{ route('communication.send') }}" method="POST">
    @csrf

    <input type="hidden" name="type" value="email">

    <div class="mb-3">
        <label for="template_id" class="form-label">Email Template</label>
        <select name="template_id" class="form-control" required>
            @foreach ($templates as $template)
                <option value="{{ $template->id }}">{{ $template->subject }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label for="target" class="form-label">Message To</label>
        <select name="target" class="form-control" required>
            <option value="students">Students</option>
            <option value="parents">Parents</option>
            <option value="teachers">Teachers</option>
            <option value="staff">Staff</option>
        </select>
    </div>

    <button type="submit" class="btn btn-primary">Send Email</button>
</form>
