<?php

namespace App\Controller;

use App\Entity\Session;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Sécurité et Authentification')]
class SecurityController extends AbstractController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    #[OA\RequestBody(
        description: 'Informations de connexion',
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'username', type: 'string', description: 'nom d\'utilisateur / email'),
                new OA\Property(property: 'password', type: 'string', description: 'Mot de passe')
            ],
            type: 'object'
        )
    )]
    #[OA\Response(response: 200, description: 'Connexion réussie et retourne l\'utilisateur ou le token de session')]
    #[OA\Response(response: 401, description: 'Identifiants invalides')]
    public function login(#[CurrentUser] ?Session $user): JsonResponse
    {
        return $this->json(['error'=>0, $user]);
    }

    #[Route('/api/logout', name: 'api_logout', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Déconnexion réussie')]
    public function logout(): void
    {
        // controller can be blank: it will never be called!
        throw new \Exception('Don\'t forget to activate logout in your security.yaml');
    }
}
