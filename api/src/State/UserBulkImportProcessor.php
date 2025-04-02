<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Service\UserService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Processor de estado para importação em massa de usuários
 * Delegando a lógica de negócios para o UserService
 */
final class UserBulkImportProcessor implements ProcessorInterface
{
    public function __construct(
        private UserService $userService,
        private Security $security
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Response
    {
        // Obter o corpo da requisição diretamente
        $request = $context['request'] ?? null;
        if (!$request) {
            return new JsonResponse(['error' => 'Requisição inválida'], Response::HTTP_BAD_REQUEST);
        }
        
        try {
            // Verificar permissões (opcional para ambiente de desenvolvimento)
            if (!$this->security->isGranted('ROLE_ADMIN')) {
                return new JsonResponse(['error' => 'Apenas administradores podem importar usuários em massa.'], Response::HTTP_FORBIDDEN);
            }
            
            $usersData = json_decode($request->getContent(), true);
            
            if (!is_array($usersData)) {
                return new JsonResponse(['error' => 'Formato de dados inválido. Esperado um array de usuários.'], Response::HTTP_BAD_REQUEST);
            }
            
            // Delegar a lógica de importação para o UserService
            $results = $this->userService->importUsers($usersData);
            
            return new JsonResponse($results, Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erro ao processar a importação: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
} 