<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Processor para operações específicas de perfil de usuário
 */
final class UserProfileProcessor implements ProcessorInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private UserService $userService
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Response
    {
        // Verificar se o ID do usuário está disponível
        if (!isset($uriVariables['id'])) {
            throw new BadRequestHttpException('ID de usuário não fornecido');
        }

        // Buscar o usuário pelo ID
        $user = $this->userRepository->find($uriVariables['id']);
        if (!$user) {
            throw new NotFoundHttpException('Usuário não encontrado');
        }

        $request = $context['request'] ?? null;
        if (!$request) {
            throw new BadRequestHttpException('Requisição inválida');
        }

        // Determinar qual operação de perfil está sendo realizada
        try {
            $path = $request->getPathInfo();
            $method = $request->getMethod();
            $content = json_decode($request->getContent(), true) ?? [];

            // Operações de atualização de foto de perfil
            if (str_ends_with($path, '/profile-picture') && $method === 'PATCH') {
                if (!isset($content['url'])) {
                    throw new BadRequestHttpException('URL da imagem não fornecida');
                }
                $user = $this->userService->updateProfilePicture($user, $content['url']);
                
                return new JsonResponse([
                    'id' => $user->getId(),
                    'profilePictureUrl' => $user->getProfilePictureUrl()
                ]);
            }

            // Operações de atualização de foto de capa
            if (str_ends_with($path, '/cover-picture') && $method === 'PATCH') {
                if (!isset($content['url'])) {
                    throw new BadRequestHttpException('URL da imagem não fornecida');
                }
                $user = $this->userService->updateCoverPicture($user, $content['url']);
                
                return new JsonResponse([
                    'id' => $user->getId(),
                    'coverPictureUrl' => $user->getCoverPictureUrl()
                ]);
            }

            // Operações de atualização de bio curta
            if (str_ends_with($path, '/short-bio') && $method === 'PATCH') {
                if (!isset($content['content'])) {
                    throw new BadRequestHttpException('Conteúdo da bio não fornecido');
                }
                $language = $content['language'] ?? null;
                $user = $this->userService->updateShortBio($user, $content['content'], $language);
                
                return new JsonResponse([
                    'id' => $user->getId(),
                    'shortBio' => $user->getShortBio()
                ]);
            }

            // Operações de atualização de bio longa
            if (str_ends_with($path, '/long-bio') && $method === 'PATCH') {
                if (!isset($content['content'])) {
                    throw new BadRequestHttpException('Conteúdo da bio não fornecido');
                }
                $language = $content['language'] ?? null;
                $user = $this->userService->updateLongBio($user, $content['content'], $language);
                
                return new JsonResponse([
                    'id' => $user->getId(),
                    'longBio' => $user->getLongBio()
                ]);
            }

            // Operações de atualização de interesses
            if (str_ends_with($path, '/interests') && $method === 'PUT') {
                if (!isset($content['interests']) || !is_array($content['interests'])) {
                    throw new BadRequestHttpException('Lista de interesses não fornecida ou inválida');
                }
                $user = $this->userService->updateInterests($user, $content['interests']);
                
                return new JsonResponse([
                    'id' => $user->getId(),
                    'interests' => $user->getInterests()
                ]);
            }

            // Operações de atualização de links para redes sociais
            if (str_ends_with($path, '/social-links') && $method === 'PUT') {
                if (!isset($content['links']) || !is_array($content['links'])) {
                    throw new BadRequestHttpException('Lista de links não fornecida ou inválida');
                }
                $user = $this->userService->updateSocialLinks($user, $content['links']);
                
                return new JsonResponse([
                    'id' => $user->getId(),
                    'socialLinks' => $user->getSocialLinks()
                ]);
            }

            // Operação de perfil completo
            if (str_ends_with($path, '/profile') && $method === 'GET') {
                return new JsonResponse($this->userService->getFullProfile($user));
            }

            // Operação de perfil público
            if (str_ends_with($path, '/public-profile') && $method === 'GET') {
                return new JsonResponse($this->userService->getPublicProfile($user));
            }

            throw new BadRequestHttpException('Operação de perfil não suportada');
        } catch (AccessDeniedException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        } catch (BadRequestHttpException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erro ao processar operação de perfil: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
} 