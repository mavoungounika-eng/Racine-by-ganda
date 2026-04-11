<?php

namespace Modules\Accounting\Services;

use Modules\Accounting\Models\ChartOfAccount;
use Modules\Accounting\Exceptions\LedgerException;
use Illuminate\Support\Collection;

class ChartOfAccountsService
{
    /**
     * Obtenir un compte par son code
     */
    public function getAccount(string $code): ?ChartOfAccount
    {
        return ChartOfAccount::where('code', $code)->first();
    }

    /**
     * Obtenir tous les comptes actifs
     */
    public function getActiveAccounts(): Collection
    {
        return ChartOfAccount::active()->orderBy('code')->get();
    }

    /**
     * Obtenir comptes par type
     */
    public function getAccountsByType(string $type): Collection
    {
        return ChartOfAccount::active()->ofType($type)->orderBy('code')->get();
    }

    /**
     * Obtenir comptes avec hiérarchie
     */
    public function getAccountsWithHierarchy(): Collection
    {
        return ChartOfAccount::active()
            ->whereNull('parent_code')
            ->with('children')
            ->orderBy('code')
            ->get();
    }

    /**
     * Créer un nouveau compte
     */
    public function createAccount(array $data): ChartOfAccount
    {
        // Vérifier code unique
        if (ChartOfAccount::where('code', $data['code'])->exists()) {
            throw new LedgerException("Le compte {$data['code']} existe déjà");
        }

        // Vérifier parent existe si fourni
        if (isset($data['parent_code']) && $data['parent_code']) {
            $parent = $this->getAccount($data['parent_code']);
            if (!$parent) {
                throw new LedgerException("Le compte parent {$data['parent_code']} n'existe pas");
            }
        }

        return ChartOfAccount::create($data);
    }

    /**
     * Mettre à jour un compte
     */
    public function updateAccount(string $code, array $data): ChartOfAccount
    {
        $account = ChartOfAccount::where('code', $code)->firstOrFail();

        // Protéger comptes système
        if ($account->is_system && isset($data['code'])) {
            throw new LedgerException("Le code du compte système {$code} ne peut pas être modifié");
        }

        // Empêcher modification type si compte a des écritures
        if (isset($data['account_type']) && $account->entryLines()->exists()) {
            throw new LedgerException("Le type du compte {$code} ne peut pas être modifié car il a des écritures");
        }

        $account->update($data);

        return $account->fresh();
    }

    /**
     * Désactiver un compte (soft delete logique)
     */
    public function deactivateAccount(string $code): ChartOfAccount
    {
        $account = ChartOfAccount::where('code', $code)->firstOrFail();

        // Protéger comptes système
        if ($account->is_system) {
            throw new LedgerException("Le compte système {$code} ne peut pas être désactivé");
        }

        // Vérifier pas d'écritures non postées
        if ($account->entryLines()->whereHas('entry', fn($q) => $q->draft())->exists()) {
            throw new LedgerException("Le compte {$code} a des écritures brouillon");
        }

        $account->update(['is_active' => false]);

        return $account->fresh();
    }

    /**
     * Valider code comptable (format OHADA)
     */
    public function validateAccountCode(string $code): bool
    {
        // Code OHADA: 1-8 caractères numériques
        return preg_match('/^[1-8][0-9]{0,7}$/', $code) === 1;
    }

    /**
     * Obtenir comptes soumis à TVA
     */
    public function getVatAccounts(): Collection
    {
        return ChartOfAccount::active()
            ->where('requires_vat', true)
            ->orderBy('code')
            ->get();
    }

    /**
     * Rechercher comptes
     */
    public function searchAccounts(string $query): Collection
    {
        return ChartOfAccount::active()
            ->where(function ($q) use ($query) {
                $q->where('code', 'like', "%{$query}%")
                  ->orWhere('label', 'like', "%{$query}%");
            })
            ->orderBy('code')
            ->limit(50)
            ->get();
    }
}
