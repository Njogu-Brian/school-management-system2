<x-feedback.flash :keys="['success', 'warning', 'error', 'info']" />

@if(isset($errors) && is_object($errors) && $errors->any())
    <x-feedback.alert type="danger" title="Validation Errors:" dismissible>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-feedback.alert>
@endif

@if(session('failed_students') && is_array(session('failed_students')) && count(session('failed_students')) > 0)
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-circle me-2"></i><strong>Failed Operations:</strong>
        <ul class="mb-0 mt-2">
            @foreach(session('failed_students') as $failed)
                <li>
                    @if(is_array($failed))
                        Student ID: {{ $failed['student_id'] ?? 'N/A' }} 
                        @if(isset($failed['action']))
                            ({{ $failed['action'] }})
                        @endif
                        - <code>{{ $failed['error'] ?? 'Unknown error' }}</code>
                    @else
                        {{ $failed }}
                    @endif
                </li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
