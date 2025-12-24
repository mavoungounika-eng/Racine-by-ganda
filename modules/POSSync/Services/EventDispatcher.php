<?php

namespace Modules\POSSync\Services;

use Illuminate\Support\Facades\Log;

class EventDispatcher
{
    /**
     * Mapping des types d'événements vers leurs handlers
     */
    protected array $eventHandlers = [
        'PosSaleCreated' => \Modules\POSSync\Jobs\ProcessPosSale::class,
        'PosPaymentRecorded' => \Modules\POSSync\Jobs\ProcessPosPayment::class,
        'PosSaleFinalized' => \Modules\POSSync\Jobs\FinalizePosS ale::class,
        'PosCashDrawerClosed' => \Modules\POSSync\Jobs\ProcessCashDrawerClosure::class,
    ];

    /**
     * Dispatcher un événement vers son handler approprié
     * 
     * @param string $eventType
     * @param array $payload
     * @return void
     * @throws \Exception
     */
    public function dispatch(string $eventType, array $payload): void
    {
        if (!isset($this->eventHandlers[$eventType])) {
            Log::warning('Unknown POS event type', [
                'event_type' => $eventType,
                'payload' => $payload
            ]);
            throw new \Exception("Unknown event type: {$eventType}");
        }

        $handlerClass = $this->eventHandlers[$eventType];

        // Dispatcher le job de manière asynchrone (queue)
        dispatch(new $handlerClass($payload));

        Log::info('POS event dispatched', [
            'event_type' => $eventType,
            'handler' => $handlerClass
        ]);
    }

    /**
     * Enregistrer un nouveau handler pour un type d'événement
     * 
     * @param string $eventType
     * @param string $handlerClass
     * @return void
     */
    public function registerHandler(string $eventType, string $handlerClass): void
    {
        $this->eventHandlers[$eventType] = $handlerClass;
    }

    /**
     * Obtenir tous les handlers enregistrés
     * 
     * @return array
     */
    public function getHandlers(): array
    {
        return $this->eventHandlers;
    }
}
