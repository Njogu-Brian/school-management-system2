<?php

namespace App\Http\Controllers;

use App\Models\GalleryImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class GalleryController extends Controller
{
    /**
     * Public gallery page with slideshow
     */
    public function index()
    {
        $images = GalleryImage::orderBy('sort_order')->orderBy('id')->get();

        return view('gallery.index', compact('images'));
    }

    /**
     * Upload gallery images (from Settings)
     */
    public function upload(Request $request)
    {
        $request->validate([
            'images'   => 'required|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $targetDir = public_images_path();
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        $maxOrder = GalleryImage::max('sort_order') ?? 0;

        foreach ($request->file('images') as $file) {
            $filename = time() . '_' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
            $file->move($targetDir, $filename);
            GalleryImage::create([
                'filename'   => $filename,
                'sort_order' => ++$maxOrder,
            ]);
        }

        return redirect()->route('settings.index')->with('success', count($request->file('images')) . ' image(s) added to gallery.')->withFragment('tab-gallery');
    }

    /**
     * Delete a gallery image
     */
    public function destroy(GalleryImage $galleryImage)
    {
        $path = public_images_path($galleryImage->filename);
        if (File::exists($path)) {
            File::delete($path);
        }
        $galleryImage->delete();

        return redirect()->route('settings.index')->with('success', 'Image removed from gallery.')->withFragment('tab-gallery');
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

        return redirect()->route('settings.index')->with('success', 'Gallery order updated.')->withFragment('tab-gallery');
    }
}
