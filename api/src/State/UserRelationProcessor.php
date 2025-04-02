<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Processor para operações de relações entre usuários (follow/unfollow)
 */
final class UserRelationProcessor implements ProcessorInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private UserService $userService,
        private Security $security
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Response
    {
        // Garantir que o usuário está autenticado
        $currentUser = $this->security->getUser();
        if (!$currentUser instanceof User) {
            return new JsonResponse(['error' => 'Você precisa estar autenticado para realizar esta operação'], Response::HTTP_UNAUTHORIZED);
        }

        // Verificar se o ID do usuário alvo está disponível
        if (!isset($uriVariables['id'])) {
            throw new BadRequestHttpException('ID de usuário não fornecido');
        }

        // Buscar o usuário alvo pelo ID
        $targetUser = $this->userRepository->find($uriVariables['id']);
        if (!$targetUser) {
            throw new NotFoundHttpException('Usuário alvo não encontrado');
        }

        $request = $context['request'] ?? null;
        if (!$request) {
            throw new BadRequestHttpException('Requisição inválida');
        }

        try {
            $path = $request->getPathInfo();
            $method = $request->getMethod();

            // Operação de follow
            if (str_ends_with($path, '/follow') && $method === 'POST') {
                $this->userService->followUser($currentUser, $targetUser);
                
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Você agora está seguindo este usuário',
                    'follower' => [
                        'id' => $currentUser->getId(),
                        'name' => $currentUser->getName()
                    ],
                    'following' => [
                        'id' => $targetUser->getId(),
                        'name' => $targetUser->getName()
                    ]
                ]);
            }

            // Operação de unfollow
            if (preg_match('#/following/(\d+)$#', $path, $matches) && $method === 'DELETE') {
                $targetUserId = (int) $matches[1];
                $targetUserToUnfollow = $this->userRepository->find($targetUserId);
                
                if (!$targetUserToUnfollow) {
                    throw new NotFoundHttpException('Usuário a deixar de seguir não encontrado');
                }
                
                $this->userService->unfollowUser($currentUser, $targetUserToUnfollow);
                
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Você deixou de seguir este usuário',
                    'follower' => [
                        'id' => $currentUser->getId(),
                        'name' => $currentUser->getName()
                    ],
                    'unfollowed' => [
                        'id' => $targetUserToUnfollow->getId(),
                        'name' => $targetUserToUnfollow->getName()
                    ]
                ]);
            }

            throw new BadRequestHttpException('Operação de relação não suportada');
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erro ao processar operação de relação: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
} 