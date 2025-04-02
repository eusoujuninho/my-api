<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Provider para listar relações entre usuários (seguidores e seguindo)
 */
final class UserRelationProvider implements ProviderInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private UserService $userService
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): JsonResponse
    {
        // Verificar se o ID do usuário está disponível
        if (!isset($uriVariables['id'])) {
            throw new BadRequestHttpException('ID de usuário não fornecido');
        }

        // Buscar o usuário pelo ID
        $user = $this->userRepository->find($uriVariables['id']);
        if (!$user) {
            throw new NotFoundHttpException('Usuário não encontrado');
        }

        // Obter a requisição para extrair parâmetros
        $request = $context['request'] ?? null;
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('Requisição inválida');
        }

        try {
            $page = (int) $request->query->get('page', 1);
            $itemsPerPage = (int) $request->query->get('itemsPerPage', 10);
            
            // Garantir valores válidos
            $page = max(1, $page);
            $itemsPerPage = min(max(1, $itemsPerPage), 100); // Limitar entre 1 e 100
            
            $path = $request->getPathInfo();
            
            // Listar seguidores
            if (str_ends_with($path, '/followers')) {
                $result = $this->userService->getFollowers($user, $page, $itemsPerPage);
                return new JsonResponse($result);
            }
            
            // Listar usuários seguidos
            if (str_ends_with($path, '/following')) {
                $result = $this->userService->getFollowing($user, $page, $itemsPerPage);
                return new JsonResponse($result);
            }
            
            throw new BadRequestHttpException('Operação de relação não suportada');
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Erro ao listar relações: ' . $e->getMessage()],
                $e instanceof BadRequestHttpException ? Response::HTTP_BAD_REQUEST : Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
} 