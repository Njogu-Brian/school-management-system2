@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        @include('website.partials.header', [
            'title' => 'Site Settings',
            'icon' => 'bi bi-sliders',
            'subtitle' => 'Branding, contact details, and global website configuration',
        ])

        <div class="settings-card">
            <div class="card-body">
                <form action="{{ route('website.settings.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">School Name</label>
                            <input type="text" name="school_name" class="form-control" value="{{ old('school_name', $settings->school_name) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tagline</label>
                            <input type="text" name="tagline" class="form-control" value="{{ old('tagline', $settings->tagline) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Primary Color</label>
                            <input type="color" name="primary_color" class="form-control form-control-color" value="{{ old('primary_color', $settings->primary_color) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Secondary Color</label>
                            <input type="color" name="secondary_color" class="form-control form-control-color" value="{{ old('secondary_color', $settings->secondary_color) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $settings->phone) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $settings->email) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="2">{{ old('address', $settings->address) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Google Map Embed / URL</label>
                            <textarea name="google_map" class="form-control" rows="2">{{ old('google_map', $settings->google_map) }}</textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">WhatsApp</label>
                            <input type="text" name="whatsapp" class="form-control" value="{{ old('whatsapp', $settings->whatsapp) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Facebook</label>
                            <input type="url" name="facebook" class="form-control" value="{{ old('facebook', $settings->facebook) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Instagram</label>
                            <input type="url" name="instagram" class="form-control" value="{{ old('instagram', $settings->instagram) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">YouTube</label>
                            <input type="url" name="youtube" class="form-control" value="{{ old('youtube', $settings->youtube) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">TikTok</label>
                            <input type="url" name="tiktok" class="form-control" value="{{ old('tiktok', $settings->tiktok) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Hero Video URL</label>
                            <input type="text" name="hero_video" class="form-control" value="{{ old('hero_video', $settings->hero_video) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Current Term</label>
                            <input type="text" name="current_term" class="form-control" value="{{ old('current_term', $settings->current_term) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Logo</label>
                            <input type="file" name="logo" class="form-control" accept="image/*">
                            @if($settings->logo)
                                <small class="text-muted">Current: {{ $settings->logo }}</small>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Favicon</label>
                            <input type="file" name="favicon" class="form-control" accept="image/*">
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="admissions_open" value="1" id="admissions_open" @checked(old('admissions_open', $settings->admissions_open))>
                                <label class="form-check-label" for="admissions_open">Admissions Open</label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-settings-primary"><i class="bi bi-check-circle"></i> Save Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
