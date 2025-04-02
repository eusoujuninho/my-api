<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Doctrine\Common\Collections\Criteria;

/**
 * Serviço para gerenciamento de usuários
 * Contém toda a lógica de negócios relacionada aos usuários
 */
class UserService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private Security $security,
        private ValidatorInterface $validator
    ) {
    }

    /**
     * Cria um novo usuário
     */
    public function createUser(User $user): User
    {
        // Processar senha
        if ($user->getPlainPassword()) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $user->getPlainPassword()));
            $user->eraseCredentials();
        }

        // Garantir que todo usuário tenha pelo menos ROLE_USER
        $roles = $user->getRoles();
        if (!in_array('ROLE_USER', $roles)) {
            $roles[] = 'ROLE_USER';
            $user->setRoles($roles);
        }
        
        // Valores padrão para novos usuários
        if (!$user->getLanguageCode()) {
            $user->setLanguageCode('en');
        }
        
        if (!$user->getTimezone()) {
            $user->setTimezone('UTC');
        }

        // Persistir usuário
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        return $user;
    }

    /**
     * Atualiza um usuário existente
     */
    public function updateUser(User $user): User
    {
        // Verificar permissões
        $this->checkUserEditPermission($user);

        // Processar senha se fornecida
        if ($user->getPlainPassword()) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $user->getPlainPassword()));
            $user->eraseCredentials();
        }

        // Persistir as alterações
        $this->entityManager->flush();
        
        return $user;
    }

    /**
     * Importa múltiplos usuários em massa
     * 
     * @param array $usersData Array contendo dados dos usuários a serem importados
     * @return array Resultados da importação
     */
    public function importUsers(array $usersData): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];
        
        foreach ($usersData as $index => $userData) {
            try {
                $user = new User();
                
                // Verificar dados obrigatórios
                if (empty($userData['email']) || empty($userData['name']) || empty($userData['plainPassword'])) {
                    throw new \InvalidArgumentException('Email, nome e senha são obrigatórios para cada usuário.');
                }
                
                $user->setEmail($userData['email']);
                $user->setName($userData['name']);
                $user->setPassword($this->passwordHasher->hashPassword($user, $userData['plainPassword']));
                
                // Definir ROLE_USER por padrão
                $user->setRoles(['ROLE_USER']);
                
                // Definir propriedades opcionais
                if (!empty($userData['languageCode'])) {
                    $user->setLanguageCode($userData['languageCode']);
                } else {
                    $user->setLanguageCode('en');
                }
                
                if (!empty($userData['timezone'])) {
                    $user->setTimezone($userData['timezone']);
                } else {
                    $user->setTimezone('UTC');
                }
                
                // Campos opcionais adicionais
                if (!empty($userData['profilePictureUrl'])) {
                    $user->setProfilePictureUrl($userData['profilePictureUrl']);
                }
                
                if (!empty($userData['appPreferences']) && is_array($userData['appPreferences'])) {
                    $user->setAppPreferences($userData['appPreferences']);
                }
                
                if (!empty($userData['notificationPreferences']) && is_array($userData['notificationPreferences'])) {
                    $user->setNotificationPreferences($userData['notificationPreferences']);
                }
                
                // Validar usuário
                $violations = $this->validator->validate($user);
                if (count($violations) > 0) {
                    $errorMessages = [];
                    foreach ($violations as $violation) {
                        $errorMessages[] = $violation->getPropertyPath() . ': ' . $violation->getMessage();
                    }
                    throw new \Exception('Erros de validação: ' . implode(', ', $errorMessages));
                }
                
                // Persistir o usuário
                $this->entityManager->persist($user);
                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'index' => $index,
                    'email' => $userData['email'] ?? 'N/A',
                    'message' => $e->getMessage()
                ];
            }
        }
        
        // Fazer flush de todas as entidades de uma vez para melhor performance
        $this->entityManager->flush();
        
        return $results;
    }

    /**
     * Verifica se um usuário tem permissão para visualizar outro usuário
     */
    public function canViewUser(User $targetUser): bool
    {
        $currentUser = $this->security->getUser();
        $isAdmin = $this->security->isGranted('ROLE_ADMIN');
        
        // Administradores podem ver qualquer usuário
        if ($isAdmin) {
            return true;
        }
        
        // Usuários podem ver seus próprios dados
        if ($currentUser instanceof User && $currentUser->getId() === $targetUser->getId()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Atualiza a foto de perfil do usuário
     */
    public function updateProfilePicture(User $user, string $pictureUrl): User
    {
        $this->checkUserEditPermission($user);
        
        $user->setProfilePictureUrl($pictureUrl);
        $this->entityManager->flush();
        
        return $user;
    }
    
    /**
     * Atualiza a foto de capa do usuário
     */
    public function updateCoverPicture(User $user, string $pictureUrl): User
    {
        $this->checkUserEditPermission($user);
        
        $user->setCoverPictureUrl($pictureUrl);
        $this->entityManager->flush();
        
        return $user;
    }
    
    /**
     * Atualiza a biografia curta do usuário em um idioma específico
     * Se nenhum idioma for especificado, usa o idioma padrão do usuário
     */
    public function updateShortBio(User $user, string $content, ?string $language = null): User
    {
        $this->checkUserEditPermission($user);
        
        $language = $language ?? $user->getLanguageCode();
        $shortBio = $user->getShortBio();
        $shortBio[$language] = $content;
        $user->setShortBio($shortBio);
        
        $this->entityManager->flush();
        
        return $user;
    }
    
    /**
     * Atualiza a biografia longa do usuário em um idioma específico
     * Se nenhum idioma for especificado, usa o idioma padrão do usuário
     */
    public function updateLongBio(User $user, string $content, ?string $language = null): User
    {
        $this->checkUserEditPermission($user);
        
        $language = $language ?? $user->getLanguageCode();
        $longBio = $user->getLongBio();
        $longBio[$language] = $content;
        $user->setLongBio($longBio);
        
        $this->entityManager->flush();
        
        return $user;
    }
    
    /**
     * Atualiza os interesses do usuário
     */
    public function updateInterests(User $user, array $interests): User
    {
        $this->checkUserEditPermission($user);
        
        $user->setInterests($interests);
        $this->entityManager->flush();
        
        return $user;
    }
    
    /**
     * Atualiza os links para redes sociais do usuário
     */
    public function updateSocialLinks(User $user, array $socialLinks): User
    {
        $this->checkUserEditPermission($user);
        
        $user->setSocialLinks($socialLinks);
        $this->entityManager->flush();
        
        return $user;
    }
    
    /**
     * Usuário atual segue outro usuário
     */
    public function followUser(User $follower, User $userToFollow): User
    {
        // Verificar se não é auto-follow
        if ($follower->getId() === $userToFollow->getId()) {
            throw new \InvalidArgumentException('Um usuário não pode seguir a si mesmo.');
        }
        
        // Verificar se já segue
        if ($follower->getFollowing()->contains($userToFollow)) {
            throw new \InvalidArgumentException('Você já segue este usuário.');
        }
        
        $follower->addFollowing($userToFollow);
        $this->entityManager->flush();
        
        return $follower;
    }
    
    /**
     * Usuário atual deixa de seguir outro usuário
     */
    public function unfollowUser(User $follower, User $userToUnfollow): User
    {
        // Verificar se não é auto-unfollow
        if ($follower->getId() === $userToUnfollow->getId()) {
            throw new \InvalidArgumentException('Um usuário não pode deixar de seguir a si mesmo.');
        }
        
        // Verificar se realmente segue
        if (!$follower->getFollowing()->contains($userToUnfollow)) {
            throw new \InvalidArgumentException('Você não segue este usuário.');
        }
        
        $follower->removeFollowing($userToUnfollow);
        $this->entityManager->flush();
        
        return $follower;
    }
    
    /**
     * Obtém a lista de seguidores de um usuário com paginação
     */
    public function getFollowers(User $user, int $page = 1, int $itemsPerPage = 10): array
    {
        $offset = ($page - 1) * $itemsPerPage;
        
        $followers = $user->getFollowers()->slice($offset, $itemsPerPage);
        $totalItems = $user->getFollowers()->count();
        
        return [
            'items' => $followers,
            'totalItems' => $totalItems,
            'page' => $page,
            'itemsPerPage' => $itemsPerPage,
            'totalPages' => ceil($totalItems / $itemsPerPage)
        ];
    }
    
    /**
     * Obtém a lista de usuários que um usuário segue com paginação
     */
    public function getFollowing(User $user, int $page = 1, int $itemsPerPage = 10): array
    {
        $offset = ($page - 1) * $itemsPerPage;
        
        $following = $user->getFollowing()->slice($offset, $itemsPerPage);
        $totalItems = $user->getFollowing()->count();
        
        return [
            'items' => $following,
            'totalItems' => $totalItems,
            'page' => $page,
            'itemsPerPage' => $itemsPerPage,
            'totalPages' => ceil($totalItems / $itemsPerPage)
        ];
    }
    
    /**
     * Obtém o perfil completo do usuário (para usuário autenticado)
     */
    public function getFullProfile(User $user): array
    {
        // Verificar permissões
        if (!$this->canViewUser($user)) {
            throw new AccessDeniedException('Você não tem permissão para visualizar este perfil completo.');
        }
        
        // Montar perfil completo
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'roles' => $user->getRoles(),
            'languageCode' => $user->getLanguageCode(),
            'timezone' => $user->getTimezone(),
            'profilePictureUrl' => $user->getProfilePictureUrl(),
            'coverPictureUrl' => $user->getCoverPictureUrl(),
            'shortBio' => $user->getShortBio(),
            'longBio' => $user->getLongBio(),
            'interests' => $user->getInterests(),
            'socialLinks' => $user->getSocialLinks(),
            'appPreferences' => $user->getAppPreferences(),
            'notificationPreferences' => $user->getNotificationPreferences(),
            'followersCount' => $user->getFollowers()->count(),
            'followingCount' => $user->getFollowing()->count()
        ];
    }
    
    /**
     * Obtém o perfil público do usuário (para qualquer visitante)
     */
    public function getPublicProfile(User $user): array
    {
        // Montar perfil público (apenas campos não sensíveis)
        return [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'profilePictureUrl' => $user->getProfilePictureUrl(),
            'coverPictureUrl' => $user->getCoverPictureUrl(),
            'shortBio' => $user->getShortBio(),
            'longBio' => $user->getLongBio(),
            'interests' => $user->getInterests(),
            'socialLinks' => $user->getSocialLinks(),
            'followersCount' => $user->getFollowers()->count(),
            'followingCount' => $user->getFollowing()->count()
        ];
    }
    
    /**
     * Método auxiliar para verificar permissões de edição de usuário
     */
    private function checkUserEditPermission(User $user): void
    {
        $currentUser = $this->security->getUser();
        $isAdmin = $this->security->isGranted('ROLE_ADMIN');
        
        // Um usuário normal só pode editar seu próprio perfil
        if (!$isAdmin && $currentUser instanceof User && $currentUser->getId() !== $user->getId()) {
            throw new AccessDeniedException('Você não tem permissão para editar este usuário.');
        }
    }
} 