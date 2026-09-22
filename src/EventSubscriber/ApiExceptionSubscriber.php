<?php

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,

        #[Autowire(param: 'kernel.environment')]
        private readonly string $environment,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();

        // On n'intercepte que les requêtes de l'API
        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $exception = $event->getThrowable();

        $status = $exception instanceof HttpExceptionInterface
            ? $exception->getStatusCode()
            : 500;

        if ($status >= 500) {
            $this->logger->error(
                'Erreur API',
                [
                    'exception' => $exception,
                    'route' => $request->attributes->get('_route'),
                    'status' => $status,
                ],
            );
        }

        $payload = [
            'success' => false,
            'message' => $status >= 500
                ? 'Une erreur interne est survenue.'
                : $exception->getMessage(),
            'status' => $status,
        ];

        // Informations supplémentaires hors production
        if ($status >= 500 && $this->environment !== 'prod') {
            $payload['debug_message'] = $exception->getMessage();
            $payload['trace'] = $exception->getTrace();
        }


        $headers = $exception instanceof HttpExceptionInterface
            ? $exception->getHeaders()
            : [];

        $event->setResponse(
            new JsonResponse($payload, $status, $headers)
        );
    }
}
