<div class="card">
    <div class="card-header">Filter Students</div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.dashboard') }}" class="mb-3">
            <div class="row">
                <!-- Date Filter -->
                <div class="col-md-4">
                    <input type="date" name="date" class="form-control" value="{{ request('date', $selectedDate) }}">
                </div>

                <!-- Class Filter -->
                <div class="col-md-4">
                    <select name="class" class="form-control">
                        <option value="">All Classes</option>
                        @foreach ($classes as $class)
                            <option value="{{ $class }}" {{ request('class') == $class ? 'selected' : '' }}>
                                {{ $class }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="col-md-4">
                    <select name="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="present" {{ request('status') == 'present' ? 'selected' : '' }}>Present</option>
                        <option value="absent" {{ request('status') == 'absent' ? 'selected' : '' }}>Absent</option>
                    </select>
                </div>

                <!-- Search Filter -->
                <div class="col-md-4 mt-2">
                    <input type="text" name="search" class="form-control" placeholder="Search by Name" value="{{ request('search') }}">
                </div>
            </div>

            <button type="submit" class="btn btn-primary mt-2">Apply Filters</button>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary mt-2">Reset</a>
        </form>
    </div>
</div>
