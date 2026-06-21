<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Website\StoreBlogRequest;
use App\Models\Website\Blog;
use App\Policies\Website\ManagesWebsiteCms;
use App\Services\Website\WebsiteMediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    use ManagesWebsiteCms;

    public function __construct(private WebsiteMediaService $media)
    {
        $this->middleware(function ($request, $next) {
            abort_unless($this->canManageWebsite($request->user()), 403);

            return $next($request);
        });
    }

    public function index(): View
    {
        $blogs = Blog::query()->with('author')->latest()->paginate(20);

        return view('website.blogs.index', compact('blogs'));
    }

    public function create(): View
    {
        return view('website.blogs.create');
    }

    public function store(StoreBlogRequest $request): RedirectResponse
    {
        $data = $request->validated();
        unset($data['featured_image']);

        if ($request->hasFile('featured_image')) {
            $data['featured_image'] = $this->media->store($request->file('featured_image'), 'blogs');
        }

        $data['published'] = $request->boolean('published');
        $data['author_id'] = $request->user()->id;

        Blog::create($data);

        return redirect()->route('website.blogs.index')->with('success', 'Blog post created.');
    }

    public function edit(Blog $blog): View
    {
        return view('website.blogs.edit', compact('blog'));
    }

    public function update(Request $request, Blog $blog): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:blogs,slug,'.$blog->id,
            'excerpt' => 'nullable|string|max:2000',
            'body' => 'required|string',
            'featured_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'published' => 'nullable|boolean',
            'published_at' => 'nullable|date',
        ]);

        $data = $validated;
        unset($data['featured_image']);

        if ($request->hasFile('featured_image')) {
            $this->media->delete($blog->featured_image);
            $data['featured_image'] = $this->media->store($request->file('featured_image'), 'blogs');
        }

        $data['published'] = $request->boolean('published');
        $blog->update($data);

        return redirect()->route('website.blogs.index')->with('success', 'Blog post updated.');
    }

    public function destroy(Blog $blog): RedirectResponse
    {
        $this->media->delete($blog->featured_image);
        $blog->delete();

        return redirect()->route('website.blogs.index')->with('success', 'Blog post deleted.');
    }
}
