<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use App\Service\UserService;

/**
 * Processador de estado para User
 * Delegando a lógica de negócios para o UserService
 */
final class UserProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $persistProcessor,
        private UserService $userService
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        // Verificar se é uma operação de gravação com dados de usuário
        if ($data instanceof User) {
            // Determinar se é uma criação ou atualização com base na existência de ID
            if (!$data->getId()) {
                // É uma criação de usuário
                // Não fazemos o flush aqui pois o API Platform se encarregará disso
                $this->prepareForCreate($data);
            } else {
                // É uma atualização de usuário
                $this->prepareForUpdate($data);
            }
        }

        // Delegamos a persistência real para o processador padrão do API Platform
        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
    
    /**
     * Prepara um usuário para criação, aplicando lógica de negócios
     */
    private function prepareForCreate(User $user): void
    {
        // Processar senha
        if ($user->getPlainPassword()) {
            // Delegamos para o serviço apenas a lógica, não a persistência
            $this->userService->createUser($user);
            
            // Como o userService faz o flush, precisamos cancelar essa operação no processor
            // para evitar duplo flush
            return;
        }
    }
    
    /**
     * Prepara um usuário para atualização, aplicando lógica de negócios
     */
    private function prepareForUpdate(User $user): void
    {
        // Delegar a lógica para o serviço apenas, sem persistência
        $this->userService->updateUser($user);
    }
} 