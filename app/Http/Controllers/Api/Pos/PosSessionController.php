<?php

namespace App\Http\Controllers\Api\Pos;

use App\Http\Controllers\Controller;
use App\Models\PosSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PosSessionController extends Controller
{
    private const SEUIL_FANTOME_HEURES = 24;

    public function check(Request $request): JsonResponse
    {
        $request->validate([
            'operateur_id' => 'required|exists:users,id',
            'machine_id'   => 'required|string',
            'machine_name' => 'nullable|string|max:100',
        ]);

        $this->cleanupStale();

        $session = PosSession::open()
            ->where('opened_by', $request->operateur_id)
            ->with('opener')
            ->first();

        if (!$session) {
            return response()->json(['has_session' => false, 'session' => null]);
        }

        return response()->json([
            'has_session' => true,
            'session'     => $session->toSessionAlert(),
        ]);
    }

    public function resume(Request $request): JsonResponse
    {
        $request->validate([
            'session_id'   => 'required|exists:pos_sessions,id',
            'operateur_id' => 'required|exists:users,id',
            'machine_id'   => 'required|string',
            'machine_name' => 'nullable|string|max:100',
        ]);

        $session = PosSession::open()->findOrFail($request->session_id);

        DB::transaction(function () use ($session, $request) {
            $session->update([
                'machine_id'       => $request->machine_id,
                'machine_name'     => $request->machine_name,
                'resumed_at'       => now(),
                'resumed_by'       => $request->operateur_id,
                'last_activity_at' => now(),
            ]);
        });

        Log::info('POS session reprise', [
            'session_id'  => $session->id,
            'resumed_by'  => $request->operateur_id,
            'machine_id'  => $request->machine_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Session reprise avec succès',
            'resume'  => $session->fresh()->load('opener')->toResumePayload(),
        ]);
    }

    public function forceClose(Request $request): JsonResponse
    {
        $request->validate([
            'session_id'   => 'required|exists:pos_sessions,id',
            'operateur_id' => 'required|exists:users,id',
            'machine_id'   => 'required|string',
            'machine_name' => 'nullable|string|max:100',
            'opening_cash' => 'required|numeric|min:0',
        ]);

        $newSession = DB::transaction(function () use ($request) {
            $fantome = PosSession::open()->findOrFail($request->session_id);
            $fantome->update([
                'status'    => 'closed',
                'closed_at' => now(),
                'closed_by' => $request->operateur_id,
                'is_active' => null,
                'notes'     => trim(($fantome->notes ?? '') . "\n[Clôturée automatiquement — session fantôme le " . now()->format('d/m/Y H:i') . "]"),
            ]);

            Log::warning('POS session fantôme clôturée', [
                'session_id'     => $fantome->id,
                'closed_by'      => $request->operateur_id,
                'was_open_since' => $fantome->opened_at,
            ]);

            return PosSession::create([
                'machine_id'       => $request->machine_id,
                'machine_name'     => $request->machine_name,
                'opened_by'        => $request->operateur_id,
                'opened_at'        => now(),
                'opening_cash'     => $request->opening_cash,
                'status'           => 'open',
                'is_active'        => 1,
                'last_activity_at' => now(),
            ]);
        });

        return response()->json([
            'success'     => true,
            'message'     => 'Session fantôme clôturée, nouvelle session ouverte',
            'new_session' => $newSession->load('opener')->toResumePayload(),
        ]);
    }

    public function heartbeat(Request $request): JsonResponse
    {
        $request->validate([
            'session_id'      => 'required|exists:pos_sessions,id',
            'total_ventes'    => 'nullable|numeric|min:0',
            'nombre_tickets'  => 'nullable|integer|min:0',
            'panier_snapshot' => 'nullable|array',
        ]);

        $session = PosSession::open()->findOrFail($request->session_id);
        $session->update([
            'last_activity_at' => now(),
            'total_ventes'     => $request->total_ventes ?? $session->total_ventes,
            'nombre_tickets'   => $request->nombre_tickets ?? $session->nombre_tickets,
            'panier_snapshot'  => $request->panier_snapshot ?? $session->panier_snapshot,
        ]);

        return response()->json(['success' => true]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = PosSession::with(['opener', 'closer', 'resumedBy'])
            ->orderBy('opened_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('operateur_id')) {
            $query->where('opened_by', $request->operateur_id);
        }
        if ($request->filled('date_debut')) {
            $query->where('opened_at', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $query->where('opened_at', '<=', $request->date_fin . ' 23:59:59');
        }
        if ($request->boolean('fantomes_only')) {
            $query->fantome(self::SEUIL_FANTOME_HEURES);
        }

        return response()->json($query->paginate($request->per_page ?? 20));
    }

    public function adminForceClose(Request $request, int $id): JsonResponse
    {
        $session = PosSession::open()->findOrFail($id);
        $session->update([
            'status'    => 'closed',
            'closed_at' => now(),
            'closed_by' => $request->user()->id,
            'is_active' => null,
            'notes'     => trim(($session->notes ?? '') . "\n[Clôturée par admin " . $request->user()->name . " le " . now()->format('d/m/Y H:i') . "]"),
        ]);

        Log::info('POS session clôturée par admin', [
            'session_id' => $session->id,
            'admin_id'   => $request->user()->id,
        ]);

        return response()->json(['success' => true, 'message' => 'Session clôturée par l\'administrateur']);
    }

    private function cleanupStale(): int
    {
        $sessions = PosSession::fantome(self::SEUIL_FANTOME_HEURES)->get();
        $count = $sessions->count();

        if ($count > 0) {
            foreach ($sessions as $s) {
                $s->update([
                    'status'    => 'closed',
                    'closed_at' => now(),
                    'is_active' => null,
                    'notes'     => trim(($s->notes ?? '') . "\n[Auto-clôturée après inactivité > " . self::SEUIL_FANTOME_HEURES . "h]"),
                ]);
            }
            Log::info("POS cleanup: {$count} session(s) fantôme(s) auto-clôturée(s)");
        }

        return $count;
    }
}
