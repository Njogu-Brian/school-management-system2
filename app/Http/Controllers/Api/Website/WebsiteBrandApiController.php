<?php

namespace App\Http\Controllers\Api\Website;

use App\Http\Controllers\Controller;
use App\Models\Website\WebsiteBrandItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class WebsiteBrandApiController extends Controller
{
    public function index(): JsonResponse
    {
        $data = Cache::remember('website.api.brand', 300, function () {
            $items = WebsiteBrandItem::query()
                ->active()
                ->orderBy('sort_order')
                ->get();

            $grouped = [];
            foreach ($items as $item) {
                $grouped[$item->block_type][] = [
                    'id' => $item->id,
                    'title' => $item->title,
                    'subtitle' => $item->subtitle,
                    'body' => $item->body,
                    'image_url' => $item->image_url,
                    'link_url' => $item->link_url,
                    'video_url' => $item->video_url,
                    'settings' => $item->settings ?? [],
                    'sort_order' => $item->sort_order,
                ];
            }

            return $grouped;
        });

        return response()->json(['data' => $data]);
    }
}
