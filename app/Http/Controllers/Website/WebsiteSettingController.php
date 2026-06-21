<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Website\UpdateWebsiteSettingsRequest;
use App\Models\Website\WebsiteSetting;
use App\Policies\Website\ManagesWebsiteCms;
use App\Services\Website\WebsiteMediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WebsiteSettingController extends Controller
{
    use ManagesWebsiteCms;

    public function __construct(private WebsiteMediaService $media)
    {
        $this->middleware(function ($request, $next) {
            abort_unless($this->canManageWebsite($request->user()), 403);

            return $next($request);
        });
    }

    public function edit(): View
    {
        $settings = WebsiteSetting::current();

        return view('website.settings.edit', compact('settings'));
    }

    public function update(UpdateWebsiteSettingsRequest $request): RedirectResponse
    {
        $settings = WebsiteSetting::current();
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $this->media->delete($settings->logo);
            $data['logo'] = $this->media->store($request->file('logo'), 'branding');
        } else {
            unset($data['logo']);
        }

        if ($request->hasFile('favicon')) {
            $this->media->delete($settings->favicon);
            $data['favicon'] = $this->media->store($request->file('favicon'), 'branding');
        } else {
            unset($data['favicon']);
        }

        $data['admissions_open'] = $request->boolean('admissions_open');

        $settings->update($data);

        return redirect()->route('website.settings.edit')->with('success', 'Site settings updated.');
    }
}
