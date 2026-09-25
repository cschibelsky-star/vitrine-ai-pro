<?php

declare(strict_types=1);

namespace App\Factory\Production\Services;

use RuntimeException;

final class ProjectUrlProvisioner
{
    public function provisionCommand(
        string $projectId,
        string $upstream,
        string $healthPath = '/health',
        ?string $friendlySlug = null,
        string $kind = 'project',
    ): array {
        if (! in_array($kind, ['project', 'customer'], true)) {
            throw new RuntimeException('Tipo de URL inválido.');
        }

        if ($projectId === '' || $upstream === '') {
            throw new RuntimeException('projectId e upstream são obrigatórios.');
        }

        $arguments = [
            'python3',
            'routing/provision_route.py',
            '--kind',
            $kind,
            '--project-id',
            $projectId,
            '--upstream',
            $upstream,
            '--health-path',
            $healthPath,
        ];

        if ($friendlySlug !== null && trim($friendlySlug) !== '') {
            $arguments[] = '--friendly';
            $arguments[] = trim($friendlySlug);
        }

        return [
            'registry' => 'Vitrine-IA-Pro-VPS/routing/routes.json',
            'allocator' => 'Vitrine-IA-Pro-VPS/routing/provision_route.py',
            'arguments' => $arguments,
            'identity_policy' => $kind === 'project'
                ? 'p######.hml.vitrineiapro.com.br'
                : 'c######.vitrineiapro.com.br',
            'activation' => [
                'command' => ['python3', 'routing/activate_route.py', '--route-id', '<route-id>'],
                'pipeline' => ['RESERVED', 'DNS_PENDING', 'ROUTE_PENDING', 'TLS_PENDING', 'HEALTH_PENDING', 'READY'],
                'ready_gate' => 'A URL só pode ser entregue quando DNS, proxy, TLS e healthcheck estiverem válidos.',
                'dns_provider' => 'provider-managed',
            ],
            'note' => 'A execução deve ocorrer no workspace do registry operacional, que é a única fonte autorizada a reservar códigos e ativar a URL ponta a ponta.',
        ];
    }
}
