<?php

namespace App\Models\Concerns;

use App\Support\NameCase;

trait NormalizesNameAttributes
{
    /**
     * @return string[]
     */
    public static function nameAttributesToSentenceCase(): array
    {
        /** @var string[] $attrs */
        $attrs = property_exists(static::class, 'sentenceCaseNameAttributes')
            ? (array) (static::$sentenceCaseNameAttributes ?? [])
            : [];

        return array_values(array_unique(array_filter($attrs)));
    }

    protected static function bootNormalizesNameAttributes(): void
    {
        static::saving(function ($model) {
            foreach (static::nameAttributesToSentenceCase() as $attr) {
                if (!array_key_exists($attr, $model->getAttributes())) {
                    continue;
                }

                $raw = $model->getAttribute($attr);
                if (!is_string($raw) && $raw !== null) {
                    continue;
                }

                $normalized = NameCase::sentence($raw);
                if ($normalized !== $raw) {
                    $model->setAttribute($attr, $normalized);
                }
            }
        });
    }
}

