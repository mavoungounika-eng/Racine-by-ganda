<?php

namespace Modules\ERPProduction\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityDefect extends Model
{
    protected $table = 'erp_quality_defects';

    protected $fillable = [
        'quality_check_id',
        'defect_code',
        'defect_category',
        'severity',
        'description',
    ];

    /**
     * Relations
     */
    public function qualityCheck(): BelongsTo
    {
        return $this->belongsTo(QualityCheck::class);
    }

    /**
     * Scopes
     */
    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    public function scopeMajor($query)
    {
        return $query->where('severity', 'major');
    }

    public function scopeMinor($query)
    {
        return $query->where('severity', 'minor');
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('defect_category', $category);
    }

    /**
     * Méthodes métier
     */
    
    /**
     * Vérifier si défaut critique
     */
    public function isCritical(): bool
    {
        return $this->severity === 'critical';
    }

    /**
     * Vérifier si défaut majeur
     */
    public function isMajor(): bool
    {
        return $this->severity === 'major';
    }

    /**
     * Vérifier si défaut mineur
     */
    public function isMinor(): bool
    {
        return $this->severity === 'minor';
    }

    /**
     * Obtenir label complet
     */
    public function getFullLabel(): string
    {
        $categoryLabel = match ($this->defect_category) {
            'material' => 'Matière',
            'process' => 'Process',
            'human' => 'Humain',
            'machine' => 'Machine',
        };

        $severityLabel = match ($this->severity) {
            'critical' => 'CRITIQUE',
            'major' => 'Majeur',
            'minor' => 'Mineur',
        };

        return "[{$severityLabel}] {$categoryLabel} - {$this->defect_code}: {$this->description}";
    }
}
