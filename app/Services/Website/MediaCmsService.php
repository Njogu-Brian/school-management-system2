<?php

namespace App\Services\Website;

use App\Models\Website\ContentCalendarItem;
use App\Models\Website\MediaLibraryItem;
use App\Models\Website\MediaTag;
use Illuminate\Support\Str;

class MediaCmsService
{
    public function tags(): array
    {
        return MediaTag::orderBy('name')->get()->all();
    }

    public function createTag(string $name): MediaTag
    {
        return MediaTag::firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name]
        );
    }

    public function attachTag(MediaLibraryItem $item, MediaTag $tag): void
    {
        $item->tags()->syncWithoutDetaching([$tag->id]);
    }

    public function scheduledItems()
    {
        return MediaLibraryItem::query()
            ->whereNotNull('scheduled_publish_at')
            ->where('scheduled_publish_at', '<=', now())
            ->get();
    }

    public function publishScheduled(): int
    {
        $count = 0;
        foreach ($this->scheduledItems() as $item) {
            $item->update(['scheduled_publish_at' => null, 'is_featured' => true]);
            $count++;
        }

        return $count;
    }
}
