<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Role;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Processador de estado para Role
 * Manipula a lógica de negócios ao criar/atualizar papéis
 */
final class RoleProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $persistProcessor,
        private Security $security
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        // Verificar se o usuário tem permissão para manipular papéis (ROLE_ADMIN)
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Apenas administradores podem gerenciar papéis.');
        }

        // Lógica de negócios específica para papéis
        if ($data instanceof Role) {
            // Verificar se o nome do papel está em formato válido
            if ($data->getName() && !preg_match('/^ROLE_[A-Z0-9_]+$/', $data->getName())) {
                $data->setName('ROLE_' . strtoupper(preg_replace('/[^A-Z0-9_]/i', '_', $data->getName())));
            }
        }

        // Delegar para o processador padrão
        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
} 