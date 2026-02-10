<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Tests;

use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Validation\NotBlank;
use Pocta\DataMapper\Validation\Email;
use Pocta\DataMapper\Validation\Valid;
use Pocta\DataMapper\Validation\SkipValidation;
use Pocta\DataMapper\Validation\Validator;

class UninitializedPropertyValidationTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    // ─── Uninitialized + has validation attributes → error ─────────────

    public function testUninitializedPropertyWithNotBlankReturnsRequired(): void
    {
        $dto = new UninitNotBlankDTO();
        // $dto->name is NOT initialized (no default value)

        $errors = $this->validator->validate($dto, throw: false);
        $this->assertArrayHasKey('name', $errors);
        $this->assertSame('This field is required.', $errors['name']);
    }

    public function testUninitializedPropertyWithEmailReturnsRequired(): void
    {
        $dto = new UninitEmailDTO();

        $errors = $this->validator->validate($dto, throw: false);
        $this->assertArrayHasKey('email', $errors);
        $this->assertSame('This field is required.', $errors['email']);
    }

    public function testUninitializedPropertyWithValidReturnsRequired(): void
    {
        $dto = new UninitValidDTO();

        $errors = $this->validator->validate($dto, throw: false);
        $this->assertArrayHasKey('child', $errors);
        $this->assertSame('This field is required.', $errors['child']);
    }

    public function testMultipleUninitializedPropertiesReturnErrors(): void
    {
        $dto = new UninitMultipleDTO();

        $errors = $this->validator->validate($dto, throw: false);
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('email', $errors);
        $this->assertSame('This field is required.', $errors['name']);
        $this->assertSame('This field is required.', $errors['email']);
    }

    // ─── Uninitialized + NO validation attributes → skip (existing behavior) ──

    public function testUninitializedPropertyWithoutAttributesIsSkipped(): void
    {
        $dto = new UninitNoAttributeDTO();

        $errors = $this->validator->validate($dto, throw: false);
        $this->assertEmpty($errors);
    }

    // ─── SkipValidation ────────────────────────────────────────────────

    public function testSkipValidationOnUninitializedPropertySkips(): void
    {
        $dto = new SkipValidationUninitDTO();

        $errors = $this->validator->validate($dto, throw: false);
        $this->assertEmpty($errors);
    }

    public function testSkipValidationOnInitializedPropertySkipsAllAsserts(): void
    {
        $dto = new SkipValidationInitializedDTO();
        $dto->name = ''; // Would normally fail NotBlank

        $errors = $this->validator->validate($dto, throw: false);
        $this->assertEmpty($errors);
    }

    // ─── Group filtering on uninitialized properties ───────────────────

    public function testUninitializedPropertyWithNonActiveGroupIsSkipped(): void
    {
        $dto = new UninitGroupDTO();
        // $dto->name has #[NotBlank(groups: ['Create'])], validate with Default

        $errors = $this->validator->validate($dto, throw: false);
        $this->assertEmpty($errors, 'Property in non-active group should be skipped even when uninitialized');
    }

    public function testUninitializedPropertyWithActiveGroupReturnsRequired(): void
    {
        $dto = new UninitGroupDTO();

        $errors = $this->validator->validate($dto, throw: false, groups: ['Create']);
        $this->assertArrayHasKey('name', $errors);
        $this->assertSame('This field is required.', $errors['name']);
    }

    public function testUninitializedPropertyWithMultipleGroupsMatchesAny(): void
    {
        $dto = new UninitMultiGroupDTO();

        // Group 'A' matches
        $errors = $this->validator->validate($dto, throw: false, groups: ['A']);
        $this->assertArrayHasKey('name', $errors);

        // Group 'B' matches
        $errors = $this->validator->validate($dto, throw: false, groups: ['B']);
        $this->assertArrayHasKey('name', $errors);

        // Group 'C' does not match
        $errors = $this->validator->validate($dto, throw: false, groups: ['C']);
        $this->assertEmpty($errors);
    }

    // ─── Nested path for uninitialized properties ──────────────────────

    public function testUninitializedNestedPropertyHasDotNotationPath(): void
    {
        $parent = new UninitNestedParentDTO();
        $parent->child = new UninitNestedChildDTO();

        $errors = $this->validator->validate($parent, throw: false);
        $this->assertArrayHasKey('child.name', $errors);
        $this->assertSame('This field is required.', $errors['child.name']);
    }
}

// ─── Test DTOs ──────────────────────────────────────────────────────────

class UninitNotBlankDTO
{
    #[NotBlank]
    public string $name;
}

class UninitEmailDTO
{
    #[Email]
    public string $email;
}

class UninitValidDTO
{
    #[Valid]
    public UninitNestedChildDTO $child;
}

class UninitMultipleDTO
{
    #[NotBlank]
    public string $name;

    #[Email]
    public string $email;
}

class UninitNoAttributeDTO
{
    public string $internal;
}

class SkipValidationUninitDTO
{
    #[SkipValidation]
    #[NotBlank]
    public string $name;
}

class SkipValidationInitializedDTO
{
    #[SkipValidation]
    #[NotBlank]
    public string $name = '';
}

class UninitGroupDTO
{
    #[NotBlank(groups: ['Create'])]
    public string $name;
}

class UninitMultiGroupDTO
{
    #[NotBlank(groups: ['A', 'B'])]
    public string $name;
}

class UninitNestedParentDTO
{
    #[Valid]
    public UninitNestedChildDTO $child;
}

class UninitNestedChildDTO
{
    #[NotBlank]
    public string $name;
}
