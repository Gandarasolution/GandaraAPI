<?php

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class ApiResponseSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        #[Autowire(param: 'kernel.environment')]
        private readonly string $environment,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => 'onResponse'];
    }

    public function onResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if (
            !str_starts_with($request->getPathInfo(), '/api')
            || !$response instanceof JsonResponse
            || $response->getStatusCode() === 204
        ) {
            return;
        }

        $payload = json_decode($response->getContent() ?: 'null', true);

        if (!is_array($payload)) {
            $payload = ['data' => $payload];
        }

        unset($payload['error']);

        $status = $response->getStatusCode();

        $technicalMessage =
            isset($payload['message']) && is_string($payload['message'])
                ? $payload['message']
                : null;

        if ($status >= 400) {
            $publicMessage = match (true) {
                $status >= 500 =>
                'Une erreur interne est survenue. Veuillez réessayer plus tard.',

                $technicalMessage !== null =>
                $technicalMessage,

                default =>
                'La requête n’a pas pu être traitée.',
            };

            $payload = [
                'success' => false,
                'message' => $publicMessage,
                'status' => $status,
            ];

            if ($status >= 500 && $this->environment !== 'prod') {
                $payload['debug_message'] = $technicalMessage;
            }
        } elseif (array_is_list($payload)) {
            $payload = [
                'success' => true,
                'data' => $payload,
            ];
        } else {
            $payload = ['success' => true] + $payload;
        }

        $context = [
            'status' => $status,
            'method' => $request->getMethod(),
            'route' => $request->attributes->get('_route'),
            'path' => $request->getPathInfo(),
        ];

        if ($status >= 500) {
            $this->logger->error(
                'Réponse API en erreur',
                $context + [
                    'technical_message' => $technicalMessage,
                ]
            );
        } elseif ($status >= 400) {
            $this->logger->warning(
                'Requête API rejetée',
                $context + [
                    'message' => $technicalMessage,
                ]
            );
        }

        $response->setData($payload);
    }}
