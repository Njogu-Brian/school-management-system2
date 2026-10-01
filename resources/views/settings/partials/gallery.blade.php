<div class="tab-pane fade" id="tab-gallery" role="tabpanel">
    <div class="settings-card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-1">Login Backgrounds</h5>
                <div class="section-note">
                    Upload and choose the image shown behind the staff login form.
                    Images are resized automatically (max ~1920×1080) so login stays fast.
                    Website photo gallery is managed separately under <strong>Website CMS → Media</strong>.
                </div>
            </div>
            <a href="{{ url('/login') }}" class="btn btn-outline-primary btn-sm" target="_blank" rel="noopener">
                <i class="bi bi-box-arrow-up-right"></i> Preview login
            </a>
        </div>
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success py-2 mb-3">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger py-2 mb-3">{{ $errors->first() }}</div>
            @endif

            @php
                $activeBg = $settings['login_background']->value ?? null;
            @endphp

            <form method="POST" action="{{ route('settings.gallery.upload') }}" enctype="multipart/form-data" class="mb-4">
                @csrf
                <div class="row align-items-end g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Add background images</label>
                        <input type="file" class="form-control" name="images[]" accept="image/jpeg,image/png,image/webp" multiple required>
                        <div class="form-text">JPG, PNG, or WebP. Up to 10MB each. Prefer landscape 16:9 photos of the campus.</div>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-settings-primary w-100">
                            <i class="bi bi-cloud-upload"></i> Upload
                        </button>
                    </div>
                </div>
            </form>

            @if($galleryImages->isEmpty())
                <div class="text-center py-5 text-muted border rounded">
                    <i class="bi bi-image" style="font-size: 3rem;"></i>
                    <p class="mt-2 mb-0">No login backgrounds yet. Upload one above, then set it active.</p>
                </div>
            @else
                <div class="row g-3">
                    @foreach($galleryImages as $img)
                        @php
                            $previewUrl = $img->thumb_url ?: $img->url;
                            $exists = $img->fileExists();
                            $isActive = $activeBg && $activeBg === $img->filename;
                        @endphp
                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="card h-100 border position-relative overflow-hidden {{ $isActive ? 'border-success border-2' : '' }}">
                                @if($isActive)
                                    <span class="badge bg-success position-absolute top-0 start-0 m-2 z-1">Active</span>
                                @endif
                                @if($exists)
                                    <img src="{{ $previewUrl }}"
                                         alt="{{ $img->caption ?? 'Login background' }}"
                                         class="card-img-top"
                                         style="height: 140px; object-fit: cover; background: #f1f5f9;"
                                         loading="lazy"
                                         decoding="async"
                                         width="480"
                                         height="360"
                                         onerror="this.style.display='none'; var h=this.nextElementSibling; if(h) h.style.display='flex';">
                                    <div class="align-items-center justify-content-center text-center text-muted small px-2"
                                         style="display:none;height:140px;background:#f8fafc;">
                                        Preview failed to load<br><code class="small">{{ $img->filename }}</code>
                                    </div>
                                @else
                                    <div class="d-flex align-items-center justify-content-center text-center text-warning small px-2"
                                         style="height:140px;background:#fff7ed;">
                                        File missing on disk<br><code class="small">{{ $img->filename }}</code>
                                    </div>
                                @endif
                                <div class="card-body p-2 d-flex justify-content-between align-items-center gap-1 flex-wrap">
                                    @if($exists && ! $isActive)
                                        <form method="POST" action="{{ route('settings.gallery.set-login', $img) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-primary py-0 px-2" title="Use on login page">
                                                <i class="bi bi-check2-circle"></i> Use
                                            </button>
                                        </form>
                                    @elseif($isActive)
                                        <span class="small text-success fw-semibold">On login page</span>
                                    @else
                                        <span></span>
                                    @endif
                                    <form method="POST" action="{{ route('settings.gallery.destroy', $img) }}" class="d-inline" onsubmit="return confirm('Remove this image?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1" title="Remove"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
