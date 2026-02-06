<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Tests;

use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Exceptions\ValidationException;
use Pocta\DataMapper\Validation\GroupSequenceProviderInterface;
use Pocta\DataMapper\Validation\NotBlank;
use Pocta\DataMapper\Validation\Valid;
use Pocta\DataMapper\Validation\Validator;

class GroupValidationTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    public function testDefaultGroupApplied(): void
    {
        $obj = new GroupDefaultTestDTO();
        $obj->name = '';

        $errors = $this->validator->validate($obj, throw: false);
        $this->assertArrayHasKey('name', $errors);
    }

    public function testNonDefaultGroupSkippedByDefault(): void
    {
        $obj = new GroupNonDefaultTestDTO();
        $obj->name = '';

        // NotBlank is in group 'Special', not 'Default' — should pass
        $errors = $this->validator->validate($obj, throw: false);
        $this->assertEmpty($errors);
    }

    public function testExplicitGroupFiltering(): void
    {
        $obj = new GroupNonDefaultTestDTO();
        $obj->name = '';

        // Explicitly validate with 'Special' group
        $errors = $this->validator->validate($obj, throw: false, groups: ['Special']);
        $this->assertArrayHasKey('name', $errors);
    }

    public function testMultipleGroupsOnConstraint(): void
    {
        $obj = new GroupMultipleTestDTO();
        $obj->name = '';

        // Constraint belongs to both 'A' and 'B'
        $errors = $this->validator->validate($obj, throw: false, groups: ['A']);
        $this->assertArrayHasKey('name', $errors);

        $errors = $this->validator->validate($obj, throw: false, groups: ['B']);
        $this->assertArrayHasKey('name', $errors);

        $errors = $this->validator->validate($obj, throw: false, groups: ['C']);
        $this->assertEmpty($errors);
    }

    public function testGroupSequenceProvider(): void
    {
        // NaturalPerson — firstName required, companyName not
        $obj = new GroupSequenceTestDTO();
        $obj->entityType = 'natural_person';
        $obj->firstName = '';
        $obj->companyName = '';

        $errors = $this->validator->validate($obj, throw: false);
        $this->assertArrayHasKey('firstName', $errors);
        $this->assertArrayNotHasKey('companyName', $errors);
    }

    public function testGroupSequenceProviderLegalEntity(): void
    {
        // LegalEntity — companyName required, firstName not
        $obj = new GroupSequenceTestDTO();
        $obj->entityType = 'legal_entity';
        $obj->firstName = '';
        $obj->companyName = '';

        $errors = $this->validator->validate($obj, throw: false);
        $this->assertArrayNotHasKey('firstName', $errors);
        $this->assertArrayHasKey('companyName', $errors);
    }

    public function testGroupSequenceProviderWithDefaultGroup(): void
    {
        // entityType has Default group — always validated
        $obj = new GroupSequenceTestDTO();
        $obj->entityType = '';
        $obj->firstName = 'John';
        $obj->companyName = 'ACME';

        $errors = $this->validator->validate($obj, throw: false);
        $this->assertArrayHasKey('entityType', $errors);
    }

    public function testGroupSequenceProviderAllValid(): void
    {
        $obj = new GroupSequenceTestDTO();
        $obj->entityType = 'natural_person';
        $obj->firstName = 'John';
        $obj->companyName = '';

        $errors = $this->validator->validate($obj, throw: false);
        $this->assertEmpty($errors);
    }

    public function testExplicitGroupsOverrideProvider(): void
    {
        // Even though object implements GroupSequenceProviderInterface,
        // explicit groups parameter takes precedence
        $obj = new GroupSequenceTestDTO();
        $obj->entityType = 'natural_person';
        $obj->firstName = '';
        $obj->companyName = '';

        $errors = $this->validator->validate($obj, throw: false, groups: ['LegalEntity']);
        $this->assertArrayNotHasKey('firstName', $errors);
        $this->assertArrayHasKey('companyName', $errors);
    }

    public function testGroupsWithValidRecursion(): void
    {
        $child = new GroupChildTestDTO();
        $child->value = '';

        $parent = new GroupParentTestDTO();
        $parent->child = $child;

        // Default group — child's 'Special' group constraint should NOT trigger
        $errors = $this->validator->validate($parent, throw: false);
        $this->assertEmpty($errors);

        // Special group — child's constraint should trigger
        $errors = $this->validator->validate($parent, throw: false, groups: ['Special']);
        $this->assertArrayHasKey('child.value', $errors);
    }
}

class GroupDefaultTestDTO
{
    #[NotBlank]  // Default group: ['Default']
    public string $name = '';
}

class GroupNonDefaultTestDTO
{
    #[NotBlank(groups: ['Special'])]
    public string $name = '';
}

class GroupMultipleTestDTO
{
    #[NotBlank(groups: ['A', 'B'])]
    public string $name = '';
}

class GroupSequenceTestDTO implements GroupSequenceProviderInterface
{
    #[NotBlank]  // Default group
    public string $entityType = '';

    #[NotBlank(groups: ['NaturalPerson'])]
    public ?string $firstName = null;

    #[NotBlank(groups: ['LegalEntity'])]
    public ?string $companyName = null;

    public function getGroupSequence(): array
    {
        $groups = ['Default'];

        if ($this->entityType === 'legal_entity') {
            $groups[] = 'LegalEntity';
        } else {
            $groups[] = 'NaturalPerson';
        }

        return $groups;
    }
}

class GroupChildTestDTO
{
    #[NotBlank(groups: ['Special'])]
    public string $value = '';
}

class GroupParentTestDTO
{
    #[Valid(groups: ['Default', 'Special'])]
    public GroupChildTestDTO $child;
}
