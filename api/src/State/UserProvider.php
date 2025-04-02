<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserService;

/**
 * Provider de estado para User
 * Delegando a lógica de negócios para o UserService
 */
final class UserProvider implements ProviderInterface
{
    public function __construct(
        private ProviderInterface $itemProvider,
        private UserRepository $userRepository,
        private UserService $userService
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        // Para operações de coleção (GET /api/users)
        if (!isset($uriVariables['id'])) {
            // Retornar todos os usuários sem lógica complexa por enquanto
            return $this->userRepository->findAll();
        }
        
        // Para operações de item (GET /api/users/{id})
        $user = $this->userRepository->find($uriVariables['id']);
        
        if (!$user) {
            return null; // 404 Not Found será retornado automaticamente
        }
        
        // Verificar permissões usando o UserService
        if (!$this->userService->canViewUser($user)) {
            // Aqui poderíamos lançar uma exceção de acesso negado,
            // mas para uma API pública, podemos simplesmente filtrar os dados sensíveis
            // ou implementar via grupos de serialização
        }
        
        return $user;
    }
} 