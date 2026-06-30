<?php

namespace App\Services\Settings;

use App\Models\Setting;

class SettingsService
{
    /**
     * Récupérer tous les settings indexés par key
     *
     * @return array
     */
    public function all(): array
    {
        return Setting::all()->pluck('value', 'key')->toArray();
    }

    /**
     * Récupérer les settings d'un groupe spécifique
     *
     * @param string $group
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function group(string $group): \Illuminate\Database\Eloquent\Collection
    {
        return Setting::getGroup($group);
    }

    /**
     * Récupérer un setting (proxy vers Setting::get)
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }

    /**
     * Définir un setting (proxy vers Setting::set)
     *
     * @param string $key
     * @param mixed $value
     * @param string $type
     * @param string $group
     * @return Setting
     */
    public function set(string $key, mixed $value, string $type = 'string', string $group = 'general'): Setting
    {
        return Setting::set($key, $value, $type, $group);
    }

    /**
     * Vider tout le cache settings (proxy vers Setting::flush)
     *
     * @return void
     */
    public function flush(): void
    {
        Setting::flush();
    }

    /**
     * Récupérer les settings d'un groupe formatés pour la vue (key => value)
     *
     * @param string $group
     * @return array
     */
    public function getForView(string $group): array
    {
        return Setting::where('group', $group)
            ->get()
            ->pluck('value', 'key')
            ->toArray();
    }

    /**
     * Mettre à jour plusieurs settings en batch
     *
     * @param array $data Format: ['key' => 'value', ...]
     * @param string $group
     * @return void
     */
    public function updateBatch(array $data, string $group): void
    {
        foreach ($data as $key => $value) {
            // Déterminer le type automatiquement
            $existingSetting = Setting::where('key', $key)->first();
            $type = $existingSetting?->type ?? $this->detectType($value);

            $this->set($key, $value, $type, $group);
        }
    }

    /**
     * Détecter automatiquement le type d'une valeur
     *
     * @param mixed $value
     * @return string
     */
    private function detectType(mixed $value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        }

        if (is_int($value)) {
            return 'integer';
        }

        if (is_float($value)) {
            return 'float';
        }

        if (is_array($value)) {
            return 'json';
        }

        return 'string';
    }
}
