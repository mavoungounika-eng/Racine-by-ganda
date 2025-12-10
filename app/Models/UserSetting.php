<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSetting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'display_mode',
        'accent_palette',
        'animation_intensity',
        'visual_style',
        'contrast_level',
        'golden_light_filter',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'golden_light_filter' => 'boolean',
    ];

    /**
     * Relation avec User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtenir les paramètres par défaut
     */
    public static function defaults(): array
    {
        return [
            'display_mode' => 'dark',
            'accent_palette' => 'orange',
            'animation_intensity' => 'standard',
            'visual_style' => 'neutral',
            'contrast_level' => 'normal',
            'golden_light_filter' => false,
        ];
    }

    /**
     * Créer ou récupérer les paramètres pour un utilisateur
     */
    public static function forUser(int $userId): self
    {
        return static::firstOrCreate(
            ['user_id' => $userId],
            static::defaults()
        );
    }

    /**
     * Obtenir la classe CSS du thème
     */
    public function getThemeClass(): string
    {
        return match ($this->display_mode) {
            'light' => 'theme-light',
            'dark' => 'theme-dark',
            'auto' => $this->getAutoThemeClass(),
            default => 'theme-dark',
        };
    }

    /**
     * Déterminer le thème auto selon l'heure
     */
    private function getAutoThemeClass(): string
    {
        $hour = now()->hour;
        return ($hour >= 6 && $hour < 18) ? 'theme-light' : 'theme-dark';
    }

    /**
     * Obtenir la couleur d'accent
     */
    public function getAccentColor(): string
    {
        return match ($this->accent_palette) {
            'orange' => '#ED5F1E',
            'yellow' => '#FFB800',
            'gold' => '#D4AF37',
            'red' => '#DC2626',
            default => '#ED5F1E',
        };
    }

    /**
     * Obtenir la classe d'animation
     */
    public function getAnimationClass(): string
    {
        return match ($this->animation_intensity) {
            'none' => 'animations-none',
            'soft' => 'animations-soft',
            'standard' => 'animations-standard',
            'luxury' => 'animations-luxury',
            default => 'animations-standard',
        };
    }

    /**
     * Obtenir la classe de style visuel
     */
    public function getVisualStyleClass(): string
    {
        return match ($this->visual_style) {
            'female' => 'style-female',
            'male' => 'style-male',
            'neutral' => 'style-neutral',
            default => 'style-neutral',
        };
    }

    /**
     * Obtenir toutes les classes CSS combinées
     */
    public function getAllClasses(): string
    {
        $classes = [
            $this->getThemeClass(),
            $this->getAnimationClass(),
            $this->getVisualStyleClass(),
            'contrast-' . $this->contrast_level,
        ];

        if ($this->golden_light_filter) {
            $classes[] = 'golden-light-active';
        }

        return implode(' ', $classes);
    }

    /**
     * Scope pour récupérer par utilisateur
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
