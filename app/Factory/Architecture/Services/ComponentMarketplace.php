<?php

declare(strict_types=1);

namespace App\Factory\Architecture\Services;

class ComponentMarketplace
{
    public function componentsFor(string $domain): array
    {
        $base = [
            ['key' => 'audit_log', 'label' => 'Auditoria', 'type' => 'core'],
            ['key' => 'timeline', 'label' => 'Timeline', 'type' => 'core'],
            ['key' => 'comments', 'label' => 'Comentários', 'type' => 'collaboration'],
            ['key' => 'dashboard', 'label' => 'Dashboard', 'type' => 'analytics'],
            ['key' => 'rest_api', 'label' => 'API REST', 'type' => 'integration'],
        ];

        $domainComponents = match ($domain) {
            'compras_publicas' => [
                ['key' => 'document_upload', 'label' => 'Upload de Documentos', 'type' => 'files'],
                ['key' => 'approval_workflow', 'label' => 'Workflow de Aprovação', 'type' => 'workflow'],
                ['key' => 'expiration_alerts', 'label' => 'Alertas de Vencimento', 'type' => 'automation'],
                ['key' => 'pdf_export', 'label' => 'Exportação PDF', 'type' => 'export'],
                ['key' => 'excel_export', 'label' => 'Exportação Excel', 'type' => 'export'],
                ['key' => 'checklist', 'label' => 'Checklist Documental', 'type' => 'quality'],
            ],
            'turismo_guia_digital' => [
                ['key' => 'gallery', 'label' => 'Galeria de Imagens', 'type' => 'media'],
                ['key' => 'map_points', 'label' => 'Mapa de Pontos', 'type' => 'geo'],
                ['key' => 'event_calendar', 'label' => 'Calendário de Eventos', 'type' => 'calendar'],
                ['key' => 'public_landing', 'label' => 'Landing Pública', 'type' => 'frontend'],
            ],
            'saude_sismed' => [
                ['key' => 'patient_history', 'label' => 'Histórico do Paciente', 'type' => 'records'],
                ['key' => 'appointment_calendar', 'label' => 'Agenda de Atendimentos', 'type' => 'calendar'],
                ['key' => 'document_upload', 'label' => 'Upload de Documentos', 'type' => 'files'],
                ['key' => 'access_control', 'label' => 'Controle de Acesso', 'type' => 'security'],
            ],
            'portal_news' => [
                ['key' => 'rss_collector', 'label' => 'Coletor RSS', 'type' => 'automation'],
                ['key' => 'editorial_review', 'label' => 'Revisão Editorial', 'type' => 'workflow'],
                ['key' => 'seo_tools', 'label' => 'SEO', 'type' => 'marketing'],
                ['key' => 'media_library', 'label' => 'Biblioteca de Mídia', 'type' => 'media'],
            ],
            'tv_digital' => [
                ['key' => 'video_library', 'label' => 'Biblioteca de Vídeos', 'type' => 'media'],
                ['key' => 'live_stream', 'label' => 'Transmissão ao Vivo', 'type' => 'streaming'],
                ['key' => 'ai_reporter', 'label' => 'Repórter IA', 'type' => 'ai'],
                ['key' => 'sponsor_banners', 'label' => 'Banners de Patrocinadores', 'type' => 'ads'],
            ],
            default => [
                ['key' => 'document_upload', 'label' => 'Upload de Arquivos', 'type' => 'files'],
                ['key' => 'status_workflow', 'label' => 'Fluxo de Status', 'type' => 'workflow'],
            ],
        };

        return array_values(array_merge($base, $domainComponents));
    }
}
