{{-- Simplified Website CMS navigation (staff content management only) --}}
@php $websiteCmsActive = Request::is('website-cms*'); @endphp
<a href="#websiteCmsMenu" data-bs-toggle="collapse"
   aria-expanded="{{ $websiteCmsActive ? 'true' : 'false' }}"
   class="{{ $websiteCmsActive ? 'parent-active' : '' }}">
    <i class="bi bi-globe2"></i><span> Website CMS</span>
</a>
<div class="collapse {{ $websiteCmsActive ? 'show' : '' }}" id="websiteCmsMenu">
    <a href="{{ route('website.settings.edit') }}" class="sublink {{ Request::is('website-cms/settings*') ? 'active' : '' }}">
        <i class="bi bi-sliders"></i> Site Settings
    </a>
    <a href="{{ route('website.homepage.index') }}" class="sublink {{ Request::is('website-cms/homepage*') ? 'active' : '' }}">
        <i class="bi bi-layout-wtf"></i> Homepage
    </a>
    <a href="{{ route('website.pages.index') }}" class="sublink {{ Request::is('website-cms/pages*') ? 'active' : '' }}">
        <i class="bi bi-file-earmark-text"></i> Pages
    </a>
    <a href="{{ route('website.media.index') }}" class="sublink {{ Request::is('website-cms/media*') ? 'active' : '' }}">
        <i class="bi bi-images"></i> Media Library
    </a>

    @php $cmsContentActive = Request::is('website-cms/blogs*', 'website-cms/events*', 'website-cms/testimonials*', 'website-cms/faqs*'); @endphp
    <a href="#websiteCmsContent" data-bs-toggle="collapse" aria-expanded="{{ $cmsContentActive ? 'true' : 'false' }}" class="sublink {{ $cmsContentActive ? 'active' : '' }}">
        <i class="bi bi-journal-richtext"></i> Content
    </a>
    <div class="collapse {{ $cmsContentActive ? 'show' : '' }} ps-3" id="websiteCmsContent">
        <a href="{{ route('website.blogs.index') }}" class="sublink {{ Request::is('website-cms/blogs*') ? 'active' : '' }}">Blogs</a>
        <a href="{{ route('website.events.index') }}" class="sublink {{ Request::is('website-cms/events*') ? 'active' : '' }}">Events</a>
        <a href="{{ route('website.testimonials.index') }}" class="sublink {{ Request::is('website-cms/testimonials*') ? 'active' : '' }}">Testimonials</a>
        <a href="{{ route('website.faqs.index') }}" class="sublink {{ Request::is('website-cms/faqs*') ? 'active' : '' }}">FAQs</a>
    </div>

    @php $cmsAdmissionsActive = Request::is('website-cms/enquiries*', 'website-cms/admissions*'); @endphp
    <a href="#websiteCmsAdmissions" data-bs-toggle="collapse" aria-expanded="{{ $cmsAdmissionsActive ? 'true' : 'false' }}" class="sublink {{ $cmsAdmissionsActive ? 'active' : '' }}">
        <i class="bi bi-envelope-paper"></i> Admissions
    </a>
    <div class="collapse {{ $cmsAdmissionsActive ? 'show' : '' }} ps-3" id="websiteCmsAdmissions">
        <a href="{{ route('website.enquiries.index') }}" class="sublink {{ Request::is('website-cms/enquiries*') ? 'active' : '' }}">Leads</a>
        <a href="{{ route('website.admissions.index') }}" class="sublink {{ Request::is('website-cms/admissions*') ? 'active' : '' }}">Applications</a>
    </div>

    <a href="{{ route('website.analytics.index') }}" class="sublink {{ Request::is('website-cms/analytics*') ? 'active' : '' }}">
        <i class="bi bi-graph-up"></i> Analytics
    </a>
    <a href="{{ route('website.seo.index') }}" class="sublink {{ Request::is('website-cms/seo') || Request::is('website-cms/seo/*') ? 'active' : '' }}">
        <i class="bi bi-search"></i> SEO
    </a>
    <a href="/website" target="_blank" rel="noopener" class="sublink" style="color: #d4af37;">
        <i class="bi bi-box-arrow-up-right"></i> View Public Site
    </a>
</div>
