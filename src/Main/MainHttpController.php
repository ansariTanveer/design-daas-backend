<?php

declare(strict_types=1);

namespace Application\Core\Main;

use Application\Common\HttpResponse\HttpResponseFactory;
use Application\Core\Util\Main\VersionDetails;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Annotation\Route;

final readonly class MainHttpController
{
    public function __construct(
        private VersionDetails $versionDetails,
        private HttpResponseFactory $responseFactory,
    ) {
    }

    #[Route(path: '/version', name: 'version', methods: ['GET'])]
    public function versionAction(): ResponseInterface
    {
        return $this->responseFactory->buildJson(
            200,
            $this->versionDetails,
        );
    }
}
