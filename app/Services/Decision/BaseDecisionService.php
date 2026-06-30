<?php

namespace App\Services\Decision;

use App\Models\AICalculationLog;
use Illuminate\Support\Facades\Cache;

abstract class BaseDecisionService
{
    /**
     * Nom du module (à définir dans chaque service)
     */
    abstract protected function getModuleName(): string;

    /**
     * Vérifie si le module est activé
     */
    protected function isEnabled(): bool
    {
        $moduleName = $this->getModuleName();
        return config('ai_decisional.enabled') && 
               config("ai_decisional.modules.{$moduleName}", false);
    }

    /**
     * Exécute un calcul avec logging automatique
     * 
     * @param string $calculationType
     * @param array $inputData
     * @param callable $calculation
     * @return mixed
     */
    protected function executeCalculation(
        string $calculationType,
        array $inputData,
        callable $calculation
    ): mixed {
        if (!$this->isEnabled()) {
            return null;
        }

        $startTime = microtime(true);
        $success = true;
        $errorMessage = null;
        $outputData = null;

        try {
            $outputData = $calculation($inputData);
        } catch (\Exception $e) {
            $success = false;
            $errorMessage = $e->getMessage();
            \Log::error("AI Calculation Error [{$this->getModuleName()}]", [
                'type' => $calculationType,
                'error' => $errorMessage,
            ]);
        }

        $calculationTime = microtime(true) - $startTime;

        // Log du calcul
        if (config('ai_decisional.logging.log_calculations')) {
            try {
                AICalculationLog::create([
                    'module' => $this->getModuleName(),
                    'calculation_type' => $calculationType,
                    'input_data' => $inputData,
                    'output_data' => $outputData,
                    'calculation_time' => $calculationTime,
                    'success' => $success,
                    'error_message' => $errorMessage,
                    'calculated_at' => now(),
                ]);
            } catch (\Exception $e) {
                \Log::error("Failed to log AI calculation", [
                    'module' => $this->getModuleName(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $outputData;
    }

    /**
     * Cache un résultat de calcul
     * 
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl
     * @return void
     */
    protected function cacheResult(string $key, mixed $value, ?int $ttl = null): void
    {
        if (!config('ai_decisional.performance.cache_enabled')) {
            return;
        }

        $ttl = $ttl ?? config('ai_decisional.performance.cache_ttl');
        Cache::put("ai_decisional:{$key}", $value, $ttl);
    }

    /**
     * Récupère un résultat du cache
     * 
     * @param string $key
     * @return mixed
     */
    protected function getCachedResult(string $key): mixed
    {
        if (!config('ai_decisional.performance.cache_enabled')) {
            return null;
        }

        return Cache::get("ai_decisional:{$key}");
    }
}
