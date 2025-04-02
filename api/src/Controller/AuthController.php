<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Controller para endpoints de autenticação
 */
#[Route('/api', name: 'api_')]
class AuthController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private TokenStorageInterface $tokenStorage,
        private Security $security,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Endpoint de login
     */
    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (empty($data['email']) || empty($data['password'])) {
            return $this->json(['message' => 'Email e senha são obrigatórios'], Response::HTTP_BAD_REQUEST);
        }
        
        // Procurar usuário pelo email
        $user = $this->userRepository->findOneBy(['email' => $data['email']]);
        
        if (!$user) {
            return $this->json(['message' => 'Credenciais inválidas'], Response::HTTP_UNAUTHORIZED);
        }
        
        // Verificar a senha
        if (!$this->passwordHasher->isPasswordValid($user, $data['password'])) {
            return $this->json(['message' => 'Credenciais inválidas'], Response::HTTP_UNAUTHORIZED);
        }
        
        // Gerar um token simples (temporário até implementação de JWT)
        $token = base64_encode($user->getEmail() . ':' . md5($user->getPassword() . $user->getId()));
        
        return $this->json([
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'roles' => $user->getRoles()
            ]
        ]);
    }

    /**
     * Endpoint de registro
     */
    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        // Validação básica
        if (empty($data['email']) || empty($data['name']) || empty($data['plainPassword'])) {
            return $this->json(['message' => 'Email, nome e senha são obrigatórios'], Response::HTTP_BAD_REQUEST);
        }
        
        // Verificar se o email já existe
        if ($this->userRepository->findOneBy(['email' => $data['email']])) {
            return $this->json(['message' => 'Este email já está cadastrado'], Response::HTTP_BAD_REQUEST);
        }
        
        // Criar o usuário
        $user = new User();
        $user->setEmail($data['email']);
        $user->setName($data['name']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $data['plainPassword']));
        $user->setRoles(['ROLE_USER']);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        return $this->json([
            'message' => 'Usuário registrado com sucesso',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName()
            ]
        ], Response::HTTP_CREATED);
    }

    /**
     * Endpoint para obter informações do usuário atual
     */
    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        // Obter o usuário atual
        $user = $this->security->getUser();
        
        if (!$user instanceof User) {
            return $this->json(['message' => 'Usuário não autenticado'], Response::HTTP_UNAUTHORIZED);
        }
        
        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'roles' => $user->getRoles(),
            'languageCode' => $user->getLanguageCode(),
            'timezone' => $user->getTimezone(),
            'profilePictureUrl' => $user->getProfilePictureUrl()
        ]);
    }

    /**
     * Endpoint de logout
     */
    #[Route('/logout', name: 'logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        // Limpar token (o componente de segurança do Symfony também lida com isso)
        $this->tokenStorage->setToken(null);
        
        return $this->json(['message' => 'Logout realizado com sucesso']);
    }

    /**
     * Endpoint para alterar senha
     */
    #[Route('/change-password', name: 'change_password', methods: ['POST'])]
    public function changePassword(Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        
        if (!$user instanceof User) {
            return $this->json(['message' => 'Usuário não autenticado'], Response::HTTP_UNAUTHORIZED);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (empty($data['currentPassword']) || empty($data['newPassword'])) {
            return $this->json(['message' => 'Senha atual e nova senha são obrigatórias'], Response::HTTP_BAD_REQUEST);
        }
        
        // Verificar senha atual
        if (!$this->passwordHasher->isPasswordValid($user, $data['currentPassword'])) {
            return $this->json(['message' => 'Senha atual incorreta'], Response::HTTP_BAD_REQUEST);
        }
        
        // Validar nova senha
        if (strlen($data['newPassword']) < 8) {
            return $this->json(['message' => 'A nova senha deve ter pelo menos 8 caracteres'], Response::HTTP_BAD_REQUEST);
        }
        
        // Atualizar senha
        $user->setPassword($this->passwordHasher->hashPassword($user, $data['newPassword']));
        $this->entityManager->flush();
        
        return $this->json(['message' => 'Senha alterada com sucesso']);
    }
} 