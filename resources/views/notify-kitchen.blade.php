@extends('layouts.app')

@section('content')
    <h1>Notify Kitchen</h1>

    @if (session('success'))
        <div style="color: green; margin-bottom: 10px;">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('notify-kitchen.submit') }}" method="POST">
        @csrf
        <table class="table table-bordered mt-3">
            <thead>
                <tr>
                    <th>Class</th>
                    <th>Present Students</th>
                </tr>
            </thead>
                <tbody>
                    @forelse ($classCounts as $classCount)
                        <tr>
                            <td>{{ $classCount->class }}</td>
                            <td>{{ $classCount->count }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="text-center">No attendance marked yet.</td>
                        </tr>
                    @endforelse
                </tbody>
        </table>
        <button type="submit" class="btn btn-primary">Notify Kitchen</button>
    </form>
@endsection
