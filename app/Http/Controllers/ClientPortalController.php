<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyModule;
use App\Models\Contract;
use App\Models\License;
use App\Models\Payment;
use App\Models\SupportTicket;
use Illuminate\Http\Request;

class ClientPortalController extends Controller
{
    protected function companyDisplayName($company): string
    {
        return (string) (
            ($company->name ?? null)
            ?: ($company->nome ?? null)
            ?: ($company->company_name ?? null)
            ?: ($company->razao_social ?? null)
            ?: ($company->fantasy_name ?? null)
            ?: ($company->fantasia ?? null)
            ?: ($company->nome_fantasia ?? null)
            ?: ('Cliente #' . ($company->id ?? ''))
        );
    }

    protected function companyDomain($company): string
    {
        return (string) (
            ($company->primary_domain ?? null)
            ?: ($company->domain ?? null)
            ?: ($company->dominio_principal ?? null)
            ?: ($company->dominio ?? null)
            ?: ($company->url_principal ?? null)
            ?: '-'
        );
    }

    protected function companyInstanceType($company): string
    {
        return (string) (
            ($company->instance_type ?? null)
            ?: ($company->tipo_instancia ?? null)
            ?: ($company->tipo ?? null)
            ?: '-'
        );
    }

    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return redirect('/cliente/login');
        }

        if (method_exists($user, 'isClient') && $user->isClient()) {
            if (! $user->company_id) {
                abort(403, 'Usuário cliente sem empresa vinculada.');
            }

            $company = $user->company;
        } else {
            $company = Company::query()->first();

            if (! $company) {
                abort(404, 'Nenhuma empresa cadastrada para exibir na área do cliente.');
            }
        }

        $companyName = $this->companyDisplayName($company);
        $companyDomain = $this->companyDomain($company);
        $companyInstanceType = $this->companyInstanceType($company);

        $licenses = class_exists(License::class)
            ? License::query()->where('company_id', $company->id)->latest()->get()
            : collect();

        $modules = class_exists(CompanyModule::class)
            ? CompanyModule::query()->where('company_id', $company->id)->with(['module', 'plan'])->latest()->get()
            : collect();

        $contracts = class_exists(Contract::class)
            ? Contract::query()->where('company_id', $company->id)->latest()->get()
            : collect();

        $payments = class_exists(Payment::class)
            ? Payment::query()->where('company_id', $company->id)->latest()->get()
            : collect();

        $tickets = class_exists(SupportTicket::class)
            ? SupportTicket::query()->where('company_id', $company->id)->latest()->get()
            : collect();

        return view('client-portal.dashboard', compact(
            'user',
            'company',
            'companyName',
            'companyDomain',
            'companyInstanceType',
            'licenses',
            'modules',
            'contracts',
            'payments',
            'tickets'
        ));
    }
}
