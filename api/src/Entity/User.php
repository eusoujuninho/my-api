<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Put;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use App\State\UserProvider;
use App\State\UserProcessor;
use App\State\UserBulkImportProcessor;
use App\State\UserProfileProcessor;
use App\State\UserRelationProcessor;
use App\State\UserRelationProvider;

#[ApiResource(
    operations: [
        new GetCollection(
            provider: UserProvider::class,
            uriTemplate: '/api/users'
        ),
        new Post(
            processor: UserProcessor::class,
            uriTemplate: '/api/users'
        ),
        new Get(
            provider: UserProvider::class,
            uriTemplate: '/api/users/{id}'
        ),
        new Put(
            processor: UserProcessor::class,
            uriTemplate: '/api/users/{id}'
        ),
        new Patch(
            processor: UserProcessor::class,
            uriTemplate: '/api/users/{id}'
        ),
        new Delete(
            processor: UserProcessor::class,
            uriTemplate: '/api/users/{id}'
        ),
        new Post(
            uriTemplate: '/api/users/bulk-import',
            processor: UserBulkImportProcessor::class,
            name: 'user_bulk_import',
            description: 'Permite a importação de múltiplos usuários de uma só vez',
            inputFormats: [
                'json' => ['application/json'],
                'jsonld' => ['application/ld+json']
            ],
            outputFormats: [
                'json' => ['application/json'],
                'jsonld' => ['application/ld+json']
            ],
            input: false,
            output: false,
            validate: false,
            deserialize: false
        ),
        new Patch(
            uriTemplate: '/api/users/{id}/profile-picture',
            processor: UserProfileProcessor::class,
            name: 'update_profile_picture',
            description: 'Atualiza a foto de perfil do usuário',
            input: false,
            output: false,
            validate: false,
            deserialize: false
        ),
        new Patch(
            uriTemplate: '/api/users/{id}/cover-picture',
            processor: UserProfileProcessor::class,
            name: 'update_cover_picture',
            description: 'Atualiza a foto de capa do usuário',
            input: false,
            output: false,
            validate: false,
            deserialize: false
        ),
        new Patch(
            uriTemplate: '/api/users/{id}/short-bio',
            processor: UserProfileProcessor::class,
            name: 'update_short_bio',
            description: 'Atualiza a biografia curta do usuário',
            input: false,
            output: false,
            validate: false,
            deserialize: false
        ),
        new Patch(
            uriTemplate: '/api/users/{id}/long-bio',
            processor: UserProfileProcessor::class,
            name: 'update_long_bio',
            description: 'Atualiza a biografia longa do usuário',
            input: false,
            output: false,
            validate: false,
            deserialize: false
        ),
        new Put(
            uriTemplate: '/api/users/{id}/interests',
            processor: UserProfileProcessor::class,
            name: 'update_interests',
            description: 'Atualiza os interesses do usuário',
            input: false,
            output: false,
            validate: false,
            deserialize: false
        ),
        new Put(
            uriTemplate: '/api/users/{id}/social-links',
            processor: UserProfileProcessor::class,
            name: 'update_social_links',
            description: 'Atualiza os links para redes sociais do usuário',
            input: false,
            output: false,
            validate: false,
            deserialize: false
        ),
        new Get(
            uriTemplate: '/api/users/{id}/profile',
            processor: UserProfileProcessor::class,
            name: 'get_full_profile',
            description: 'Obtém o perfil completo do usuário',
            input: false,
            output: false,
            validate: false,
            deserialize: false
        ),
        new Get(
            uriTemplate: '/api/users/{id}/public-profile',
            processor: UserProfileProcessor::class,
            name: 'get_public_profile',
            description: 'Obtém o perfil público do usuário',
            input: false,
            output: false,
            validate: false,
            deserialize: false
        ),
        new Post(
            uriTemplate: '/api/users/{id}/follow',
            processor: UserRelationProcessor::class,
            name: 'follow_user',
            description: 'Seguir um usuário',
            input: false,
            output: false,
            validate: false,
            deserialize: false
        ),
        new Delete(
            uriTemplate: '/api/users/{id}/following/{targetId}',
            processor: UserRelationProcessor::class,
            name: 'unfollow_user',
            description: 'Deixar de seguir um usuário',
            input: false,
            output: false,
            validate: false,
            deserialize: false
        ),
        new Get(
            uriTemplate: '/api/users/{id}/followers',
            provider: UserRelationProvider::class,
            name: 'get_followers',
            description: 'Obtém a lista de seguidores do usuário',
            input: false,
            output: false,
            validate: false,
            deserialize: false
        ),
        new Get(
            uriTemplate: '/api/users/{id}/following',
            provider: UserRelationProvider::class,
            name: 'get_following',
            description: 'Obtém a lista de usuários que este usuário segue',
            input: false,
            output: false,
            validate: false,
            deserialize: false
        )
    ],
    normalizationContext: ['groups' => ['user:read']],
    denormalizationContext: ['groups' => ['user:write']]
)]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\Index(name: 'idx_users_language_code', columns: ['language_code'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Groups(['user:read', 'user:write'])]
    private ?string $email = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[Assert\NotBlank(groups: ['user:create'])]
    #[Assert\Length(min: 8, groups: ['user:create', 'user:write'])]
    #[Groups(['user:write'])]
    private ?string $plainPassword = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['user:read', 'user:write'])]
    private ?string $name = null;

    #[ORM\ManyToMany(targetEntity: Role::class, inversedBy: 'users')]
    #[Groups(['user:read', 'user:write'])]
    private Collection $userRoles;

    /**
     * Código de idioma do usuário
     */
    #[ORM\Column(length: 10, options: ['default' => 'en'])]
    #[Groups(['user:read', 'user:write'])]
    private string $languageCode = 'en';

    /**
     * Fuso horário do usuário
     */
    #[ORM\Column(length: 50, options: ['default' => 'UTC'])]
    #[Groups(['user:read', 'user:write'])]
    private string $timezone = 'UTC';

    /**
     * Preferências gerais do aplicativo no formato JSON: 
     * {"theme": "dark", "notifications_enabled": true, ...}
     */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    private array $appPreferences = [];

    /**
     * Preferências de notificações no formato JSON: 
     * {"email": true, "push": false, "marketing": false, ...}
     */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    private array $notificationPreferences = [];

    /**
     * URL da foto de perfil do usuário
     */
    #[ORM\Column(length: 1024, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    private ?string $profilePictureUrl = null;

    /**
     * URL da foto de capa do usuário
     */
    #[ORM\Column(length: 1024, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    private ?string $coverPictureUrl = null;

    /**
     * Biografia curta internacionalizada no formato JSON: 
     * {"en": "I am a developer", "pt-br": "Eu sou um desenvolvedor", ...}
     */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    private array $shortBio = [];

    /**
     * Biografia longa internacionalizada no formato JSON: 
     * {"en": "Detailed bio in English", "pt-br": "Biografia detalhada em português", ...}
     */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    private array $longBio = [];

    /**
     * Lista de interesses do usuário no formato JSON array: 
     * ["programming", "music", "sports", ...]
     */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    private array $interests = [];

    /**
     * Links para redes sociais no formato JSON: 
     * {"github": "https://github.com/user", "twitter": "https://twitter.com/user", ...}
     */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    private array $socialLinks = [];

    /**
     * Usuários que este usuário segue
     */
    #[ORM\ManyToMany(targetEntity: self::class, inversedBy: 'followers')]
    #[ORM\JoinTable(name: 'user_followers')]
    #[ORM\JoinColumn(name: 'follower_user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'following_user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Groups(['user:read'])]
    private Collection $following;

    /**
     * Usuários que seguem este usuário
     */
    #[ORM\ManyToMany(targetEntity: self::class, mappedBy: 'following')]
    #[Groups(['user:read'])]
    private Collection $followers;

    public function __construct()
    {
        $this->userRoles = new ArrayCollection();
        $this->following = new ArrayCollection();
        $this->followers = new ArrayCollection();
        $this->appPreferences = [];
        $this->notificationPreferences = [];
        $this->shortBio = [];
        $this->longBio = [];
        $this->interests = [];
        $this->socialLinks = [];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        
        // Add roles from the userRoles collection
        foreach ($this->userRoles as $role) {
            $roles[] = $role->getName();
        }
        
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): static
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        $this->plainPassword = null;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, Role>
     */
    public function getUserRoles(): Collection
    {
        return $this->userRoles;
    }

    public function addUserRole(Role $role): static
    {
        if (!$this->userRoles->contains($role)) {
            $this->userRoles->add($role);
        }

        return $this;
    }

    public function removeUserRole(Role $role): static
    {
        $this->userRoles->removeElement($role);

        return $this;
    }

    /**
     * Check if user has a specific permission
     */
    public function hasPermission(string $permissionName): bool
    {
        foreach ($this->userRoles as $role) {
            foreach ($role->getPermissions() as $permission) {
                if ($permission->getName() === $permissionName) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(string $roleName): bool
    {
        if (in_array($roleName, $this->roles)) {
            return true;
        }
        
        foreach ($this->userRoles as $role) {
            if ($role->getName() === $roleName) {
                return true;
            }
        }
        
        return false;
    }

    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }

    public function setLanguageCode(string $languageCode): static
    {
        $this->languageCode = $languageCode;

        return $this;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): static
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function getAppPreferences(): array
    {
        return $this->appPreferences;
    }

    public function setAppPreferences(array $appPreferences): static
    {
        $this->appPreferences = $appPreferences;

        return $this;
    }

    public function getNotificationPreferences(): array
    {
        return $this->notificationPreferences;
    }

    public function setNotificationPreferences(array $notificationPreferences): static
    {
        $this->notificationPreferences = $notificationPreferences;

        return $this;
    }

    public function getProfilePictureUrl(): ?string
    {
        return $this->profilePictureUrl;
    }

    public function setProfilePictureUrl(?string $profilePictureUrl): static
    {
        $this->profilePictureUrl = $profilePictureUrl;

        return $this;
    }

    public function getCoverPictureUrl(): ?string
    {
        return $this->coverPictureUrl;
    }

    public function setCoverPictureUrl(?string $coverPictureUrl): static
    {
        $this->coverPictureUrl = $coverPictureUrl;

        return $this;
    }

    public function getShortBio(): array
    {
        return $this->shortBio;
    }

    public function setShortBio(array $shortBio): static
    {
        $this->shortBio = $shortBio;

        return $this;
    }

    public function getLongBio(): array
    {
        return $this->longBio;
    }

    public function setLongBio(array $longBio): static
    {
        $this->longBio = $longBio;

        return $this;
    }

    public function getInterests(): array
    {
        return $this->interests;
    }

    public function setInterests(array $interests): static
    {
        $this->interests = $interests;

        return $this;
    }

    public function getSocialLinks(): array
    {
        return $this->socialLinks;
    }

    public function setSocialLinks(array $socialLinks): static
    {
        $this->socialLinks = $socialLinks;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getFollowing(): Collection
    {
        return $this->following;
    }

    public function addFollowing(self $following): static
    {
        if (!$this->following->contains($following) && $this !== $following) {
            $this->following->add($following);
            $following->addFollower($this);
        }

        return $this;
    }

    public function removeFollowing(self $following): static
    {
        if ($this->following->removeElement($following)) {
            $following->removeFollower($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getFollowers(): Collection
    {
        return $this->followers;
    }

    public function addFollower(self $follower): static
    {
        if (!$this->followers->contains($follower) && $this !== $follower) {
            $this->followers->add($follower);
        }

        return $this;
    }

    public function removeFollower(self $follower): static
    {
        $this->followers->removeElement($follower);

        return $this;
    }
} 