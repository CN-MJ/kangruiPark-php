<?php

namespace App\Support;

use App\Models\EnterpriseService;

class EnterpriseServicePayload
{
    public const MAX_IMAGES = 3;

    public const DEFAULT_PRICE = '面议';

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public static function normalize(array $validated): array
    {
        if (array_key_exists('price_text', $validated)) {
            $price = trim((string) ($validated['price_text'] ?? ''));
            $validated['price_text'] = $price !== '' ? $price : self::DEFAULT_PRICE;
        }

        if (array_key_exists('images', $validated)) {
            $images = is_array($validated['images']) ? $validated['images'] : [];
            $normalized = [];
            foreach ($images as $item) {
                if (! is_string($item)) {
                    continue;
                }
                $trimmed = trim($item);
                if ($trimmed === '' || preg_match('#^(wxfile:|https?://(tmp|usr)/)#i', $trimmed)) {
                    continue;
                }
                $relative = MediaUrl::toRelative($trimmed);
                if ($relative) {
                    $normalized[] = $relative;
                } elseif (str_starts_with(ltrim($trimmed, '/'), 'uploads/')) {
                    $normalized[] = ltrim($trimmed, '/');
                }
            }
            $validated['images'] = array_values(array_slice(array_unique($normalized), 0, self::MAX_IMAGES));
        }

        return $validated;
    }

    /**
     * @return array<string, mixed>
     */
    public static function format(EnterpriseService $s, bool $withDescription = true): array
    {
        $images = MediaUrl::absoluteList(
            is_array($s->images) ? array_values($s->images) : [],
            request(),
        );

        $data = [
            'id' => $s->id,
            'enterprise_id' => $s->enterprise_id,
            'name' => $s->name,
            'summary' => $s->summary,
            'price_text' => $s->price_text ?: self::DEFAULT_PRICE,
            'images' => $images,
            'cover' => $images[0] ?? null,
            'status' => (int) $s->status,
            'status_text' => (int) $s->status === EnterpriseService::STATUS_ON ? '上架' : '下架',
            'sort_order' => (int) $s->sort_order,
            'created_at' => optional($s->created_at)?->toDateTimeString(),
            'updated_at' => optional($s->updated_at)?->toDateTimeString(),
        ];

        if ($withDescription) {
            $data['description'] = $s->description;
        }

        return $data;
    }
}
