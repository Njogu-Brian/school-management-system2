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
                @foreach ($classCounts as $class => $count)
                    <tr>
                        <td>{{ $class }}</td>
                        <td>{{ $count }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <button type="submit" class="btn btn-primary">Notify Kitchen</button>
    </form>
@endsection
