<div class="tab-pane fade" id="tab-gallery" role="tabpanel">
    <div class="settings-card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-1">Photo Gallery</h5>
                <div class="section-note">Upload images for the school gallery. Shown on the public gallery page with varied transitions. Max 5MB per image.</div>
            </div>
            <a href="{{ route('gallery.index') }}" class="btn btn-outline-primary btn-sm" target="_blank">
                <i class="bi bi-box-arrow-up-right"></i> View Gallery
            </a>
        </div>
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success py-2 mb-3">{{ session('success') }}</div>
            @endif

            <form method="POST" action="{{ route('settings.gallery.upload') }}" enctype="multipart/form-data" class="mb-4">
                @csrf
                <div class="row align-items-end g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Add images</label>
                        <input type="file" class="form-control" name="images[]" accept="image/jpeg,image/png,image/webp" multiple required>
                        <div class="form-text">Select one or more images (JPG, PNG, WebP). Up to 5MB each.</div>
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
                    <i class="bi bi-images" style="font-size: 3rem;"></i>
                    <p class="mt-2 mb-0">No gallery images yet. Upload some above.</p>
                </div>
            @else
                <div class="row g-3">
                        @foreach($galleryImages as $img)
                            <div class="col-6 col-md-4 col-lg-3">
                                <div class="card h-100 border position-relative overflow-hidden">
                                    <img src="{{ $img->url }}" alt="{{ $img->caption ?? 'Gallery' }}" class="card-img-top" style="height: 140px; object-fit: cover;" loading="lazy">
                                    <div class="card-body p-2 d-flex justify-content-end align-items-center">
                                        <form method="POST" action="{{ route('settings.gallery.destroy', $img) }}" class="d-inline" onsubmit="return confirm('Remove this image from gallery?');">
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
