<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class TestController extends AbstractController
{
    use ApiResponseTrait;
    #[Route('/api/test', name: 'api_test', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'success',
            'message' => 'La route de test fonctionne parfaitement !',
            'time' => (new \DateTime())->format('Y-m-d H:i:s')
        ]);
    }
}
