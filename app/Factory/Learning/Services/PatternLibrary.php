<?php

declare(strict_types=1);

namespace App\Factory\Learning\Services;

use Illuminate\Support\Facades\File;

class PatternLibrary
{
    public function inspect(string $slug): array
    {
        $modulePath = storage_path('app/factory/builds/' . $slug);
        $manifestPath = $modulePath . '/module.json';
        $manifest = File::exists($manifestPath)
            ? json_decode((string) File::get($manifestPath), true)
            : [];

        $detected = [];
        $suggestions = [];

        $text = strtolower($slug . ' ' . json_encode($manifest, JSON_UNESCAPED_UNICODE));

        if (str_contains($text, 'fornecedor')) {
            $detected[] = 'supplier_management';
            $suggestions = array_merge($suggestions, [
                'Adicionar documentos do fornecedor',
                'Adicionar contratos vinculados',
                'Adicionar histórico de interações',
                'Adicionar dashboard de fornecedores ativos/inativos',
                'Adicionar auditoria de alterações',
                'Adicionar API pública protegida',
            ]);
        }

        if (str_contains($text, 'contrato')) {
            $detected[] = 'contract_management';
            $suggestions = array_merge($suggestions, [
                'Adicionar vencimentos próximos',
                'Adicionar alertas por prazo',
                'Adicionar valor total contratado',
                'Adicionar documentos do contrato',
            ]);
        }

        if (str_contains($text, 'documento')) {
            $detected[] = 'document_management';
            $suggestions = array_merge($suggestions, [
                'Adicionar upload de arquivo',
                'Adicionar validade do documento',
                'Adicionar status de aprovação',
                'Adicionar revisão administrativa',
            ]);
        }

        if (str_contains($text, 'licitacao') || str_contains($text, 'licitacoes')) {
            $detected[] = 'public_procurement';
            $suggestions = array_merge($suggestions, [
                'Adicionar propostas',
                'Adicionar fases da licitação',
                'Adicionar fornecedores participantes',
                'Adicionar julgamento/resultado',
                'Adicionar checklist documental',
            ]);
        }

        if ($detected === []) {
            $detected[] = 'generic_crud';
            $suggestions[] = 'Adicionar dashboard básico';
            $suggestions[] = 'Adicionar permissões';
            $suggestions[] = 'Adicionar filtros e busca';
        }

        return [
            'module' => $slug,
            'patterns' => array_values(array_unique($detected)),
            'suggestions' => array_values(array_unique($suggestions)),
            'inspected_at' => now()->toISOString(),
        ];
    }
}
