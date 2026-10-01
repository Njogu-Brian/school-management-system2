<?php

namespace App\Http\Controllers;

use App\Models\GalleryImage;
use App\Models\Setting;
use App\Services\ImageOptimizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class GalleryController extends Controller
{
    public function __construct(private ImageOptimizer $imageOptimizer)
    {
    }

    /**
     * Preview page for uploaded login-background candidates.
     */
    public function index()
    {
        $images = GalleryImage::orderBy('sort_order')->orderBy('id')->get();
        $active = Setting::get('login_background');

        return view('gallery.index', compact('images', 'active'));
    }

    /**
     * Upload login-background candidates (Settings → Login Backgrounds).
     */
    public function upload(Request $request)
    {
        $request->validate([
            'images'   => 'required|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:10240',
        ]);

        $targetDir = public_images_path();
        if (! is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        $maxOrder = GalleryImage::max('sort_order') ?? 0;
        $saved = 0;
        $lastFilename = null;

        foreach ($request->file('images') as $file) {
            $filename = time().'_'.uniqid().'_'.preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
            $destination = $targetDir.DIRECTORY_SEPARATOR.$filename;
            $file->move($targetDir, $filename);

            if (! is_file($destination)) {
                continue;
            }

            // Login backgrounds fill the viewport — 1920×1080 is enough.
            $this->imageOptimizer->optimize($destination, 1920, 1080);
            $this->writeThumbnail($destination, $filename);

            GalleryImage::create([
                'filename'   => $filename,
                'sort_order' => ++$maxOrder,
            ]);
            $lastFilename = $filename;
            $saved++;
        }

        if ($saved === 0) {
            return redirect()->route('settings.index')
                ->withErrors('Upload failed — could not write images. Check that public/images is writable.')
                ->withFragment('tab-gallery');
        }

        // If nothing is set yet, activate the last uploaded image as the login background.
        if ($lastFilename && ! Setting::get('login_background')) {
            Setting::set('login_background', $lastFilename);
        }

        return redirect()->route('settings.index')
            ->with('success', $saved.' login background image(s) uploaded. All of them rotate on /login; use “Start with this” to choose the first slide.')
            ->withFragment('tab-gallery');
    }

    /**
     * Make a gallery image the first slide of the login carousel.
     */
    public function setLoginBackground(GalleryImage $galleryImage)
    {
        if (! $galleryImage->fileExists()) {
            return redirect()->route('settings.index')
                ->withErrors('That image file is missing on disk. Re-upload it, then try again.')
                ->withFragment('tab-gallery');
        }

        Setting::set('login_background', $galleryImage->filename);

        return redirect()->route('settings.index')
            ->with('success', 'Login carousel will start with this image. Open /login to confirm.')
            ->withFragment('tab-gallery');
    }

    /**
     * Delete a gallery image
     */
    public function destroy(GalleryImage $galleryImage)
    {
        $wasActive = Setting::get('login_background') === $galleryImage->filename;

        $path = public_images_path($galleryImage->filename);
        if (File::exists($path)) {
            File::delete($path);
        }

        $thumb = $galleryImage->thumbFilename();
        if ($thumb) {
            $thumbPath = public_images_path($thumb);
            if (File::exists($thumbPath)) {
                File::delete($thumbPath);
            }
        }

        $galleryImage->delete();

        if ($wasActive) {
            Setting::set('login_background', '');
        }

        return redirect()->route('settings.index')
            ->with('success', 'Image removed.'.($wasActive ? ' Login background was cleared — pick another.' : ''))
            ->withFragment('tab-gallery');
    }

    /**
     * Reorder gallery images
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:gallery_images,id',
        ]);

        foreach ($request->order as $position => $id) {
            GalleryImage::where('id', $id)->update(['sort_order' => $position]);
        }

        return redirect()->route('settings.index')->with('success', 'Order updated.')->withFragment('tab-gallery');
    }

    private function writeThumbnail(string $fullPath, string $filename): void
    {
        $info = pathinfo($filename);
        $thumbName = ($info['filename'] ?? 'img').'_thumb.'.($info['extension'] ?? 'jpg');
        $thumbPath = dirname($fullPath).DIRECTORY_SEPARATOR.$thumbName;

        if (! @copy($fullPath, $thumbPath)) {
            return;
        }

        $this->imageOptimizer->optimize($thumbPath, 480, 360, 78);
    }
}
