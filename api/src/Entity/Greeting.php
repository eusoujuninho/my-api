<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * This is a dummy entity. Remove it!
 */
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/api/greetings'
        ),
        new Post(
            uriTemplate: '/api/greetings'
        ),
        new Get(
            uriTemplate: '/api/greetings/{id}'
        ),
        new Put(
            uriTemplate: '/api/greetings/{id}'
        ),
        new Patch(
            uriTemplate: '/api/greetings/{id}'
        ),
        new Delete(
            uriTemplate: '/api/greetings/{id}'
        )
    ],
    mercure: true
)]
#[ORM\Entity]
class Greeting
{
    /**
     * The entity ID
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    private ?int $id = null;

    /**
     * A nice person
     */
    #[ORM\Column]
    #[Assert\NotBlank]
    public string $name = '';

    public function getId(): ?int
    {
        return $this->id;
    }
}
