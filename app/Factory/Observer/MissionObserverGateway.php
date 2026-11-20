<?php
declare(strict_types=1);

namespace App\Factory\Observer;

use RuntimeException;

/**
 * Application boundary: mission policy and audit must be supplied by trusted services.
 * This is not an HTTP endpoint and does not authenticate a user by itself.
 */
final class MissionObserverGateway
{
    /** @param callable(AuthorizedMissionScope,string,array):bool $authorize */
    /** @param callable(array):void $audit */
    public function __construct(private $authorize, private $audit)
    {
        if (!is_callable($authorize) || !is_callable($audit)) {
            throw new RuntimeException('Trusted authorization and audit callbacks required');
        }
    }

    public function inspect(AuthorizedMissionScope $scope, string $capability, array $parameters = []): array
    {
        $scope->assertAllowed($capability);
        if (($this->authorize)($scope, $capability, $parameters) !== true) {
            throw new RuntimeException('Authorization service denied mission');
        }
        // Audit is required before reading; failure stops the request.
        ($this->audit)([
            'event' => 'factory.observer.read_requested',
            'mission_id' => $scope->missionId,
            'tenant_id' => $scope->tenantId,
            'actor_id' => $scope->actorId,
            'capability' => $capability,
        ]);
        $provider = new FactoryReadOnlyProvider($scope->projectUuids);
        return (new FactoryObserver($provider))->inspect($capability, $parameters);
    }
}
