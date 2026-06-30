<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
        'is_encrypted',
        'is_public',
        'updated_by',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
        'is_public' => 'boolean',
    ];

    /**
     * Clés à chiffrer automatiquement
     */
    const ENCRYPTED_KEYS = [
        'stripe_secret',
        'stripe_webhook_secret',
        'monetbil_service_key',
        'monetbil_service_secret',
        'openai_api_key',
        'google_client_secret',
        'recaptcha_secret_key',
        'mail_password',
        'aws_secret_access_key',
        'sentry_dsn',
    ];

    /**
     * Accessor/Mutator pour value avec chiffrement/déchiffrement automatique
     */
    protected function value(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($this->is_encrypted && $value) {
                    try {
                        return Crypt::decryptString($value);
                    } catch (\Exception $e) {
                        return $value; // Fallback si déchiffrement échoue
                    }
                }

                // Cast selon type
                return match ($this->type) {
                    'boolean' => (bool) filter_var($value, FILTER_VALIDATE_BOOLEAN),
                    'integer' => (int) $value,
                    'float' => (float) $value,
                    'json' => json_decode($value, true),
                    default => $value,
                };
            },
            set: function ($value) {
                // Chiffrer si clé sensible
                if (in_array($this->key, self::ENCRYPTED_KEYS) || $this->is_encrypted) {
                    $this->is_encrypted = true;
                    return Crypt::encryptString($value);
                }

                // Encoder JSON si nécessaire
                if ($this->type === 'json' && is_array($value)) {
                    return json_encode($value);
                }

                return $value;
            }
        );
    }

    /**
     * Récupérer un setting depuis cache Redis → DB → default
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = "setting:{$key}";

        return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();

            if (!$setting) {
                return $default;
            }

            return $setting->value;
        });
    }

    /**
     * Définir un setting en DB + invalider cache
     *
     * @param string $key
     * @param mixed $value
     * @param string $type
     * @param string $group
     * @return Setting
     */
    public static function set(string $key, mixed $value, string $type = 'string', string $group = 'general'): Setting
    {
        $setting = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'group' => $group,
                'updated_by' => auth()->id(),
            ]
        );

        // Invalider cache
        Cache::forget("setting:{$key}");

        return $setting;
    }

    /**
     * Récupérer tous les settings d'un groupe
     *
     * @param string $group
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getGroup(string $group): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('group', $group)->get();
    }

    /**
     * Vider tout le cache settings
     *
     * @return void
     */
    public static function flush(): void
    {
        // Laravel ne supporte pas la suppression par pattern directement
        // On vide les clés connues
        $allSettings = self::pluck('key');

        foreach ($allSettings as $key) {
            Cache::forget("setting:{$key}");
        }
    }

    /**
     * Relation vers User qui a modifié
     */
    public function updatedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    /**
     * Scope pour filtrer par groupe
     */
    public function scopeGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Scope pour filtrer publics
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }
}
