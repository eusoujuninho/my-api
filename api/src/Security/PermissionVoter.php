<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PermissionVoter extends Voter
{
    // Prefixo para identificar permissões no sistema de votação
    private const PERMISSION_PREFIX = 'PERMISSION_';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Se o atributo começar com PERMISSION_, este voter vai tratar
        return str_starts_with($attribute, self::PERMISSION_PREFIX);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // Se o usuário não estiver logado, negar acesso
        if (!$user instanceof User) {
            return false;
        }

        // Extrair o nome da permissão do atributo
        $permission = substr($attribute, strlen(self::PERMISSION_PREFIX));

        // Verificar se o usuário tem a permissão
        return $user->hasPermission($permission);
    }

    /**
     * Converte um nome de permissão para o formato usado pelo voter
     */
    public static function formatPermission(string $permission): string
    {
        return self::PERMISSION_PREFIX . $permission;
    }
} 