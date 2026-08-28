<?php

namespace App\Controller;

use App\Entity\Session;
use App\Repository\SecurityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;

#[Route('/api')]

#[OA\Tag(name: 'Sécurité et Authentification')]
class SecurityController extends AbstractController
{
    public function __construct(
        private SecurityRepository $repository,
        private EntityManagerInterface $entityManager
    ){}

    #[Route('/login', name: 'api_login', methods: ['POST'])]
    #[OA\RequestBody(
        description: 'Informations de connexion',
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'username', type: 'string', description: 'login'),
                new OA\Property(property: 'password', type: 'string', description: 'Mot de passe')
            ],
            type: 'object'
        )
    )]
    #[OA\Response(response: 200, description: 'Connexion réussie et retourne l\'utilisateur ou le token de session')]
    #[OA\Response(response: 401, description: 'Identifiants invalides')]
    public function login(#[CurrentUser] ?Session $user, LoggerInterface $logger): JsonResponse
    {
        $logger->debug('Tentative de connexion', ['user' => $user]);
        return $this->json(['error'=>0, $user]);
    }

    #[Route('/logout', name: 'api_logout', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Déconnexion réussie')]
    public function logout(): void
    {
        // controller can be blank: it will never be called!
        throw new \Exception('Don\'t forget to activate logout in your security.yaml');
    }


    #[Route('/me', name: 'api_me', methods: ['GET'])]
    public function me(#[CurrentUser] ?Session $user, LoggerInterface $logger): JsonResponse
    {
        try {
            if (!$user) {
                return $this->json(['message' => 'Non authentifié'], 401);
            }

            $result = $this->repository->me($user, $logger);

            if (isset($result['error']) && $result['error'] === 1) {
                return $this->json(['message' => $result['message'], 'error' => 1], 500);
            }

            return $this->json($result, 200);
        }catch (\Exception $exception){
            return $this->json(['message' => $exception->getMessage(), 'error' => 1], 500);
        }


    }
}
