<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Website\AssistantKnowledgeArticle;
use App\Policies\Website\ManagesWebsiteCms;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssistantKnowledgeController extends Controller
{
    use ManagesWebsiteCms;

    public function __construct()
    {
        $this->middleware(fn ($r, $n) => $this->canManageWebsite($r->user()) ? $n($r) : abort(403));
    }

    public function index(): View
    {
        return view('website.assistant.index', [
            'articles' => AssistantKnowledgeArticle::orderByDesc('priority')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'topic' => 'required|string|max:100',
            'content' => 'required|string',
            'page_context' => 'nullable|string',
            'priority' => 'nullable|integer|min:0',
            'published' => 'nullable|boolean',
        ]);

        AssistantKnowledgeArticle::create([
            'title' => $validated['title'],
            'topic' => $validated['topic'],
            'content' => $validated['content'],
            'page_context' => $validated['page_context']
                ? array_map('trim', explode(',', $validated['page_context']))
                : null,
            'priority' => $validated['priority'] ?? 0,
            'published' => $request->boolean('published', true),
        ]);

        return back()->with('success', 'Knowledge article added.');
    }

    public function destroy(AssistantKnowledgeArticle $article): RedirectResponse
    {
        $article->delete();

        return back()->with('success', 'Article removed.');
    }
}
