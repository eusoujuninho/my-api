<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Permission;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Processador de estado para Permission
 * Manipula a lógica de negócios ao criar/atualizar permissões
 */
final class PermissionProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $persistProcessor,
        private Security $security
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        // Verificar se o usuário tem permissão para manipular permissões (ROLE_ADMIN)
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Apenas administradores podem gerenciar permissões.');
        }

        // Lógica de negócios específica para permissões
        if ($data instanceof Permission) {
            // Aqui poderia ser implementada lógica específica para permissões
            // Por exemplo, validação de nomes, sanitização, etc.
            
            // Garantir que os nomes de permissão estejam em um formato consistente
            if ($data->getName()) {
                // Converter para snake_case
                $name = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $data->getName()));
                $data->setName($name);
            }
        }

        // Delegar para o processador padrão
        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
} 