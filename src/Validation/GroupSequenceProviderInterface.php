<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Validation;

/**
 * Interface for classes that provide dynamic validation group sequences.
 *
 * When a class implements this interface, the Validator will call
 * getGroupSequence() to determine which validation groups to apply.
 *
 * Example:
 * class ClientRequest implements GroupSequenceProviderInterface
 * {
 *     public EntityType $entityType;
 *
 *     #[NotBlank(groups: ['NaturalPerson'])]
 *     public ?string $firstName = null;
 *
 *     #[NotBlank(groups: ['LegalEntity'])]
 *     public ?string $companyName = null;
 *
 *     public function getGroupSequence(): array
 *     {
 *         return $this->entityType === EntityType::LegalEntity
 *             ? ['Default', 'LegalEntity']
 *             : ['Default', 'NaturalPerson'];
 *     }
 * }
 */
interface GroupSequenceProviderInterface
{
    /**
     * Returns the validation group sequence for this object.
     *
     * @return array<string> Array of group names (e.g. ['Default', 'NaturalPerson'])
     */
    public function getGroupSequence(): array;
}
