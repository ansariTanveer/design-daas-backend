<?php

declare(strict_types=1);

namespace Application\Core\Util\Main;

use DI\Annotation\Inject;
use JsonSerializable;

final readonly class VersionDetails implements JsonSerializable
{
    /**
     * @Inject({
     *     "versionDetails": "application.runtime.version_details",
     * })
     * @param array{application_name: string, application_version: string} $versionDetails
     */
    public function __construct(private array $versionDetails)
    {
    }

    public function applicationName(): string
    {
        return $this->versionDetails['application_name'];
    }

    public function applicationVersion(): string
    {
        return $this->versionDetails['application_version'];
    }

    public function jsonSerialize(): object
    {
        return (object)$this->versionDetails;
    }
}
