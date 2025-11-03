<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Enum\PhpVersion;

/**
 * Benchmark entity storing test code and metadata in database
 */
#[ORM\Entity]
#[ORM\Table(name: 'benchmarks')]
#[ORM\Index(columns: ['category'], name: 'idx_category')]
#[ORM\Index(columns: ['slug'], name: 'idx_slug')]
#[ORM\HasLifecycleCallbacks]
class Benchmark
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    private string $slug = '';

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $name = '';

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $category = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $description = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $code = '';

    #[ORM\Column(type: Types::JSON)]
    private array $tags = [];

    #[ORM\Column(type: Types::STRING, length: 10, nullable: true)]
    private ?string $icon = null;

    /**
     * @var string[] Array of PhpVersion enum values
     */
    #[ORM\Column(type: Types::JSON)]
    private array $phpVersions = [];

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        string $slug = '',
        string $name = '',
        string $category = '',
        string $description = '',
        string $code = '',
        array $phpVersions = [],
        array $tags = [],
        ?string $icon = null
    ) {
        $this->slug = $slug;
        $this->name = $name;
        $this->category = $category;
        $this->description = $description;
        $this->code = $code;
        $this->phpVersions = $phpVersions;
        $this->tags = $tags;
        $this->icon = $icon;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return string[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    /**
     * @return string[] Array of PhpVersion enum values
     */
    public function getPhpVersions(): array
    {
        return $this->phpVersions;
    }

    /**
     * @return PhpVersion[]
     */
    public function getPhpVersionEnums(): array
    {
        return array_map(
            fn (string $version): PhpVersion => PhpVersion::from($version),
            $this->phpVersions
        );
    }

    public function supportsPhpVersion(PhpVersion $version): bool
    {
        return in_array($version->value, $this->phpVersions, true);
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setCategory(string $category): void
    {
        $this->category = $category;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function setTags(array $tags): void
    {
        $this->tags = $tags;
    }

    public function setIcon(?string $icon): void
    {
        $this->icon = $icon;
    }

    public function setPhpVersions(array $phpVersions): void
    {
        $this->phpVersions = $phpVersions;
    }
}
