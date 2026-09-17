<?php

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class ApiResponseSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => 'onResponse'];
    }

    public function onResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        // On ignore les routes non-API, les réponses non-JSON ou vides (204)
        if (!str_starts_with($request->getPathInfo(), '/api') || !$response instanceof JsonResponse || $response->getStatusCode() === 204) {
            return;
        }

        $payload = json_decode($response->getContent() ?: 'null', true);
        if (!is_array($payload)) {
            $payload = ['data' => $payload];
        }

        // Nettoyage de l'ancienne clé "error" si elle traîne encore dans certains contrôleurs
        if (array_key_exists('error', $payload)) {
            unset($payload['error']);
        }

        $status = $response->getStatusCode();
        $technicalMessage = isset($payload['message']) && is_string($payload['message'])
            ? $payload['message']
            : null;

        // --- GESTION DES ERREURS (400 et +) ---
        if ($status >= 400) {
            $message = $technicalMessage ?? 'La requête n’a pas pu être traitée.';

            $payload = [
                'success' => false,
                'message' => $status >= 500
                    ? 'Une erreur interne est survenue. Consultez les journaux avec la route et le statut HTTP.'
                    : $message,
                'status' => $status,
            ];

            if ($status >= 500 && ($_ENV['APP_ENV'] ?? 'prod') !== 'prod') {
                $payload['debug_message'] = $technicalMessage;
            }

            // --- GESTION DES SUCCÈS ---
        } elseif (array_is_list($payload)) {
            // Si c'est un simple tableau indexé [0, 1, 2...]
            $payload = ['success' => true, 'data' => $payload];
        } else {
            // Si c'est déjà un tableau associatif (ex: ['data' => ..., 'TotalLignes' => ...])
            $payload = ['success' => true] + $payload;
        }

        $context = [
            'status' => $status,
            'method' => $request->getMethod(),
            'route' => $request->attributes->get('_route'),
            'path' => $request->getPathInfo(),
        ];

        // LOG DES ERREURS POUR LE DÉVELOPPEUR
        if ($status >= 500) {
            $this->logger->error('Réponse API en erreur', $context + ['technical_message' => $technicalMessage]);
        } elseif ($status >= 400) {
            $this->logger->warning('Requête API rejetée', $context + ['message' => $payload['message'] ?? null]);
        }

        $response->setData($payload);
    }
}
