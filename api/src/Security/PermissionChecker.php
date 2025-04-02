<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class PermissionChecker
{
    public function __construct(
        private Security $security
    ) {
    }

    /**
     * Check if the current user has a specific permission
     */
    public function hasPermission(string $permission): bool
    {
        $user = $this->security->getUser();
        
        if (!$user instanceof User) {
            return false;
        }
        
        // Admin role has all permissions
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }
        
        return $user->hasPermission($permission);
    }

    /**
     * Check if the current user has any of the specified permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if the current user has all of the specified permissions
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Denies access if the current user doesn't have the required permission
     *
     * @throws AccessDeniedException When the user doesn't have the required permission
     */
    public function denyAccessUnlessGranted(string $permission): void
    {
        if (!$this->hasPermission($permission)) {
            throw new AccessDeniedException('Access Denied: You do not have the required permission.');
        }
    }
} 