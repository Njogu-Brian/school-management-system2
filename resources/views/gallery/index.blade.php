@extends('layouts.app')

@push('styles')
<style>
.gallery-page { padding: 1rem 0; }
.gallery-hero {
    aspect-ratio: 16/9;
    max-height: 70vh;
    background: #111;
    border-radius: 12px;
    overflow: hidden;
    position: relative;
    margin-bottom: 1.5rem;
}
.gallery-slide {
    position: absolute;
    inset: 0;
    opacity: 0;
    pointer-events: none;
    transition: none;
}
.gallery-slide.active {
    opacity: 1;
    pointer-events: auto;
    z-index: 1;
}
.gallery-slide img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
/* Transition: zoom-in */
.gallery-slide[data-transition="zoom"].active img { animation: gZoomIn 1.2s ease-out forwards; }
@keyframes gZoomIn {
    from { transform: scale(0.85); opacity: 0.6; }
    to { transform: scale(1); opacity: 1; }
}
/* Transition: slide-up */
.gallery-slide[data-transition="slide-up"].active img { animation: gSlideUp 1s ease-out forwards; }
@keyframes gSlideUp {
    from { transform: translateY(20%); opacity: 0.7; }
    to { transform: translateY(0); opacity: 1; }
}
/* Transition: slide-right */
.gallery-slide[data-transition="slide-right"].active img { animation: gSlideRight 1s ease-out forwards; }
@keyframes gSlideRight {
    from { transform: translateX(-15%); opacity: 0.7; }
    to { transform: translateX(0); opacity: 1; }
}
/* Transition: ken-burns (subtle pan) */
.gallery-slide[data-transition="kenburns"].active img { animation: gKenBurns 8s ease-out forwards; }
@keyframes gKenBurns {
    0% { transform: scale(1.05) translate(-1%, -1%); }
    100% { transform: scale(1) translate(1%, 1%); }
}
/* Transition: scale-rotate */
.gallery-slide[data-transition="scale-rotate"].active img { animation: gScaleRotate 1.1s ease-out forwards; }
@keyframes gScaleRotate {
    from { transform: scale(0.8) rotate(-2deg); opacity: 0.5; }
    to { transform: scale(1) rotate(0); opacity: 1; }
}
/* Transition: blur-in */
.gallery-slide[data-transition="blur"].active img { animation: gBlurIn 1.2s ease-out forwards; }
@keyframes gBlurIn {
    from { filter: blur(12px); opacity: 0.4; }
    to { filter: blur(0); opacity: 1; }
}
/* Transition: clip-reveal */
.gallery-slide[data-transition="clip"].active img { animation: gClipReveal 1s ease-out forwards; }
@keyframes gClipReveal {
    from { clip-path: inset(0 100% 0 0); opacity: 0.9; }
    to { clip-path: inset(0 0 0 0); opacity: 1; }
}
/* Transition: flip */
.gallery-slide[data-transition="flip"].active img { animation: gFlip 1s ease-out forwards; }
@keyframes gFlip {
    from { transform: perspective(800px) rotateY(-15deg); opacity: 0.6; }
    to { transform: perspective(800px) rotateY(0); opacity: 1; }
}
/* Dots */
.gallery-dots { display: flex; justify-content: center; gap: 0.5rem; flex-wrap: wrap; margin-top: 1rem; }
.gallery-dots button {
    width: 10px; height: 10px;
    border-radius: 50%;
    border: 2px solid #6b7280;
    background: transparent;
    cursor: pointer;
    padding: 0;
    transition: all 0.2s;
}
.gallery-dots button:hover { border-color: var(--brand-primary, #6366f1); background: rgba(99,102,241,0.3); }
.gallery-dots button.active { background: var(--brand-primary, #6366f1); border-color: var(--brand-primary, #6366f1); }
.gallery-nav { position: absolute; top: 50%; transform: translateY(-50%); z-index: 2; background: rgba(0,0,0,0.4); color: #fff; border: none; width: 44px; height: 44px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.2s; }
.gallery-nav:hover { background: rgba(0,0,0,0.6); }
.gallery-nav.prev { left: 12px; }
.gallery-nav.next { right: 12px; }
</style>
@endpush

@section('content')
<div class="gallery-page">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-images"></i> Gallery</h1>
            <p class="text-muted small mb-0">{{ $images->count() }} image(s)</p>
        </div>
        @auth
        <a href="{{ route('settings.index') }}#tab-gallery" class="btn btn-outline-primary btn-sm"><i class="bi bi-gear"></i> Manage</a>
        @endauth
    </div>

    @if($images->isEmpty())
        <div class="card">
            <div class="card-body text-center py-5 text-muted">
                <i class="bi bi-images" style="font-size: 3rem;"></i>
                <p class="mt-2 mb-0">No gallery images yet.</p>
            </div>
        </div>
    @else
        @php
            $transitions = ['zoom', 'slide-up', 'slide-right', 'kenburns', 'scale-rotate', 'blur', 'clip', 'flip'];
        @endphp
        <div class="gallery-hero" id="galleryHero">
            @foreach($images as $i => $img)
                <div class="gallery-slide {{ $i === 0 ? 'active' : '' }}"
                     data-transition="{{ $transitions[$i % count($transitions)] }}"
                     data-index="{{ $i }}">
                    <img src="{{ $img->url }}" alt="{{ $img->caption ?? 'Gallery' }}" loading="{{ $i === 0 ? 'eager' : 'lazy' }}">
                </div>
            @endforeach
            @if($images->count() > 1)
                <button type="button" class="gallery-nav prev" aria-label="Previous" onclick="galleryPrev()"><i class="bi bi-chevron-left"></i></button>
                <button type="button" class="gallery-nav next" aria-label="Next" onclick="galleryNext()"><i class="bi bi-chevron-right"></i></button>
            @endif
        </div>

        @if($images->count() > 1)
        <div class="gallery-dots" id="galleryDots">
            @foreach($images as $i => $img)
                <button type="button" class="{{ $i === 0 ? 'active' : '' }}" aria-label="Go to image {{ $i + 1 }}" onclick="galleryGoTo({{ $i }})"></button>
            @endforeach
        </div>

        <script>
        (function() {
            var slides = document.querySelectorAll('#galleryHero .gallery-slide');
            var dots = document.querySelectorAll('#galleryDots button');
            var total = slides.length;
            var current = 0;
            var interval;

            function show(i) {
                current = (i + total) % total;
                slides.forEach(function(s, j) {
                    s.classList.toggle('active', j === current);
                    s.removeAttribute('style');
                    if (j === current) s.offsetHeight;
                });
                dots.forEach(function(d, j) { d.classList.toggle('active', j === current); });
            }
            function next() { show(current + 1); }
            function prev() { show(current - 1); }
            function goTo(i) { show(i); resetInterval(); }
            function resetInterval() {
                clearInterval(interval);
                interval = setInterval(next, 5000);
            }
            window.galleryNext = next;
            window.galleryPrev = prev;
            window.galleryGoTo = goTo;
            if (total > 1) {
                interval = setInterval(next, 5000);
                document.getElementById('galleryHero').addEventListener('mouseenter', function() { clearInterval(interval); });
                document.getElementById('galleryHero').addEventListener('mouseleave', resetInterval);
            }
        })();
        </script>
        @endif
    @endif
</div>
@endsection
