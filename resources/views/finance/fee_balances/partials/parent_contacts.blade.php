@if(!empty($contacts))
    <div class="fee-parent-contacts">
        @foreach($contacts as $contact)
            @php
                $phone = $contact['phone'] ?? null;
                $tel = $phone ? preg_replace('/[^\d+]/', '', $phone) : null;
            @endphp
            @if($tel)
                <a class="fee-parent-contact" href="tel:{{ $tel }}">
            @else
                <span class="fee-parent-contact">
            @endif
                <span class="fee-parent-role">{{ $contact['role'] }}</span>
                @if(!empty($contact['name']))
                    <span class="fee-parent-name">{{ $contact['name'] }}</span>
                @endif
                @if($phone)
                    <span class="fee-parent-phone"><i class="bi bi-telephone"></i> {{ $phone }}</span>
                @endif
            @if($tel)
                </a>
            @else
                </span>
            @endif
        @endforeach
    </div>
@endif
