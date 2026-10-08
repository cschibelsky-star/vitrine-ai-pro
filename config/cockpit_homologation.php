<?php

/**
 * Cockpit homologation workflow configuration.
 *
 * Reuse existing project cards. Do not equate "latest Git commit"
 * with the running production release.
 *
 * This is a contract for an upcoming persistent workflow, not fabricated
 * environment telemetry or a deploy executor.
 */
return [
    'phases' => [
        'em_teste' => 'Em homologação',
        'correcao_pendente' => 'Correções pendentes',
        'aguardando_aprovacao' => 'Aguardando aprovação',
        'aprovado' => 'Aprovado para publicação',
        'publicado' => 'Publicado',
    ],
    'test_areas' => [
        'acesso' => 'Login, senha, recuperação e permissões',
        'navegacao' => 'Menus, páginas, botões e responsividade',
        'funcionalidades' => 'Cadastros, formulários e fluxos de negócio',
        'integracoes' => 'IA, integrações e automações',
        'seguranca' => 'Autorização e segurança',
        'publicacao' => 'Revisão final e aprovação de release',
    ],
    'issue_types' => ['erro', 'melhoria', 'nova_funcionalidade', 'ajuste_visual'],
    'issue_statuses' => ['aberta', 'em_execucao', 'em_reteste', 'resolvida', 'cancelada'],
    'required_release_fields' => [
        'project_id', 'hml_url', 'production_url',
        'hml_commit_sha', 'production_commit_sha',
        'approval_commit_sha', 'approval_actor_id',
        'approval_timestamp', 'test_evidence', 'rollback_reference',
    ],
    // Every new commit after approval must invalidate prior approval.
    'invalidate_approval_on_new_sha' => true,
];
