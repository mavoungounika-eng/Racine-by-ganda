<?php

namespace Modules\CMS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CmsSetting extends Model
{
    protected $fillable = [
        'key', 'value', 'type', 'group', 'label', 'description'
    ];

    /**
     * Récupère une valeur de paramètre
     */
    public static function get(string $key, $default = null)
    {
        $setting = Cache::remember("cms_setting_{$key}", 3600, function () use ($key) {
            return static::where('key', $key)->first();
        });

        if (!$setting) {
            return $default;
        }

        return match($setting->type) {
            'boolean' => (bool) $setting->value,
            'json' => json_decode($setting->value, true),
            'integer' => (int) $setting->value,
            default => $setting->value,
        };
    }

    /**
     * Définit une valeur de paramètre
     */
    public static function set(string $key, $value, string $type = 'text', string $group = 'general'): void
    {
        if (is_array($value)) {
            $value = json_encode($value);
            $type = 'json';
        }

        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type, 'group' => $group]
        );

        Cache::forget("cms_setting_{$key}");
    }

    /**
     * Récupère tous les paramètres d'un groupe
     */
    public static function getGroup(string $group): array
    {
        return static::where('group', $group)
            ->pluck('value', 'key')
            ->toArray();
    }
}

