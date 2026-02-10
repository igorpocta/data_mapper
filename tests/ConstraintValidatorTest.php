<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Tests;

use Attribute;
use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Mapper;
use Pocta\DataMapper\MapperOptions;
use Pocta\DataMapper\Validation\ConstraintInterface;
use Pocta\DataMapper\Validation\ConstraintValidatorInterface;
use Pocta\DataMapper\Validation\Length;
use Pocta\DataMapper\Validation\NotBlank;
use Pocta\DataMapper\Validation\Validator;
use Pocta\DataMapper\Validation\ValidatorResolverInterface;

class ConstraintValidatorTest extends TestCase
{
    public function testConstraintValidatorIsCalledAndFails(): void
    {
        $validator = new Validator();

        $dto = new ConstraintTestDTO();
        $dto->code = 'INVALID';

        $errors = $validator->validate($dto, throw: false);
        $this->assertArrayHasKey('code', $errors);
        $this->assertSame('Code "INVALID" is not allowed.', $errors['code']);
    }

    public function testConstraintValidatorPasses(): void
    {
        $validator = new Validator();

        $dto = new ConstraintTestDTO();
        $dto->code = 'ALLOWED';

        $errors = $validator->validate($dto, throw: false);
        $this->assertEmpty($errors);
    }

    public function testConstraintValidatorSkipsNull(): void
    {
        $validator = new Validator();

        $dto = new ConstraintTestNullableDTO();
        $dto->code = null;

        $errors = $validator->validate($dto, throw: false);
        $this->assertEmpty($errors);
    }

    public function testConstraintValidatorRespectsGroups(): void
    {
        $validator = new Validator();

        $dto = new ConstraintWithGroupsDTO();
        $dto->code = 'INVALID';

        // Default group — constraint not applied
        $errors = $validator->validate($dto, throw: false, groups: ['Default']);
        $this->assertEmpty($errors);

        // Matching group — constraint applied
        $errors = $validator->validate($dto, throw: false, groups: ['Strict']);
        $this->assertArrayHasKey('code', $errors);
    }

    public function testConstraintValidatorWithResolver(): void
    {
        $mockValidator = new AllowedCodeValidator();
        $resolver = new TestValidatorResolver(['AllowedCodeValidator' => $mockValidator]);

        $validator = new Validator(validatorResolver: $resolver);

        $dto = new ConstraintTestDTO();
        $dto->code = 'INVALID';

        $errors = $validator->validate($dto, throw: false);
        $this->assertArrayHasKey('code', $errors);
    }

    public function testConstraintValidatorReceivesObjectForCrossFieldValidation(): void
    {
        $validator = new Validator();

        $dto = new CrossFieldConstraintDTO();
        $dto->password = 'secret';
        $dto->passwordConfirm = 'different';

        $errors = $validator->validate($dto, throw: false);
        $this->assertArrayHasKey('passwordConfirm', $errors);
        $this->assertSame('Passwords do not match.', $errors['passwordConfirm']);
    }

    public function testCrossFieldConstraintPasses(): void
    {
        $validator = new Validator();

        $dto = new CrossFieldConstraintDTO();
        $dto->password = 'secret';
        $dto->passwordConfirm = 'secret';

        $errors = $validator->validate($dto, throw: false);
        $this->assertEmpty($errors);
    }

    public function testConstraintCombinedWithBuiltInValidators(): void
    {
        $validator = new Validator();

        $dto = new CombinedConstraintDTO();
        $dto->code = '';

        // NotBlank fires first
        $errors = $validator->validate($dto, throw: false);
        $this->assertArrayHasKey('code', $errors);
        $this->assertStringContainsString('must not be blank', $errors['code']);
    }

    public function testConstraintFiresAfterBuiltInPasses(): void
    {
        $validator = new Validator();

        $dto = new CombinedConstraintDTO();
        $dto->code = 'INVALID';

        $errors = $validator->validate($dto, throw: false);
        $this->assertArrayHasKey('code', $errors);
        $this->assertSame('Code "INVALID" is not allowed.', $errors['code']);
    }

    public function testConstraintReceivesConstraintParameters(): void
    {
        $validator = new Validator();

        $dto = new ParameterizedConstraintDTO();
        $dto->value = 15;

        $errors = $validator->validate($dto, throw: false);
        $this->assertArrayHasKey('value', $errors);
        $this->assertSame('Value must be at most 10.', $errors['value']);
    }

    public function testParameterizedConstraintPasses(): void
    {
        $validator = new Validator();

        $dto = new ParameterizedConstraintDTO();
        $dto->value = 5;

        $errors = $validator->validate($dto, throw: false);
        $this->assertEmpty($errors);
    }

    public function testMapperWithValidatorResolverParameter(): void
    {
        $resolver = new TestValidatorResolver([
            'DependencyRequiringValidator' => new DependencyRequiringValidator('injected-service'),
        ]);

        $mapper = new Mapper(
            options: MapperOptions::withAutoValidation(),
            validatorResolver: $resolver,
        );

        $dto = $mapper->fromArray(['code' => 'BAD'], DependencyConstraintDTO::class);
        // Should not throw - auto-validation passes because 'BAD' is not 'FAIL'
        $this->assertSame('BAD', $dto->code);
    }

    public function testMapperWithValidatorResolverReportsErrors(): void
    {
        $resolver = new TestValidatorResolver([
            'DependencyRequiringValidator' => new DependencyRequiringValidator('injected-service'),
        ]);

        $mapper = new Mapper(
            options: MapperOptions::withAutoValidation(),
            validatorResolver: $resolver,
        );

        $this->expectException(\Pocta\DataMapper\Exceptions\ValidationException::class);
        $mapper->fromArray(['code' => 'FAIL'], DependencyConstraintDTO::class);
    }

    public function testMapperCustomValidatorTakesPrecedenceOverResolver(): void
    {
        $customValidator = new Validator(); // no resolver
        $resolver = new TestValidatorResolver([
            'DependencyRequiringValidator' => new DependencyRequiringValidator('injected-service'),
        ]);

        // Custom validator should be used, resolver ignored
        $mapper = new Mapper(
            validator: $customValidator,
            validatorResolver: $resolver,
        );

        $this->assertSame($customValidator, $mapper->getValidator());
    }

    public function testConstraintValidatorWithoutResolverAndDependenciesThrows(): void
    {
        $validator = new Validator(); // no resolver

        $dto = new DependencyConstraintDTO();
        $dto->code = 'test';

        $this->expectException(\Error::class);
        $validator->validate($dto, throw: false);
    }
}

// --- Test DTOs and validators ---

#[Attribute(Attribute::TARGET_PROPERTY)]
class AllowedCode implements ConstraintInterface
{
    /** @param array<string> $groups */
    public function __construct(
        public readonly array $groups = ['Default'],
    ) {
    }

    /** @return class-string<ConstraintValidatorInterface> */
    public function validatedBy(): string
    {
        return AllowedCodeValidator::class;
    }

    public function validate(mixed $value, string $propertyName): ?string
    {
        return null;
    }
}

class AllowedCodeValidator implements ConstraintValidatorInterface
{
    public function validate(mixed $value, object $constraint, object $object): ?string
    {
        if ($value === null || !is_string($value)) {
            return null;
        }

        if ($value === 'ALLOWED') {
            return null;
        }

        return 'Code "' . $value . '" is not allowed.';
    }
}

class ConstraintTestDTO
{
    #[AllowedCode]
    public string $code = '';
}

class ConstraintTestNullableDTO
{
    #[AllowedCode]
    public ?string $code = null;
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class AllowedCodeStrict implements ConstraintInterface
{
    /** @param array<string> $groups */
    public function __construct(
        public readonly array $groups = ['Strict'],
    ) {
    }

    /** @return class-string<ConstraintValidatorInterface> */
    public function validatedBy(): string
    {
        return AllowedCodeValidator::class;
    }

    public function validate(mixed $value, string $propertyName): ?string
    {
        return null;
    }
}

class ConstraintWithGroupsDTO
{
    #[AllowedCodeStrict]
    public string $code = '';
}

// Cross-field validation

#[Attribute(Attribute::TARGET_PROPERTY)]
class PasswordMatch implements ConstraintInterface
{
    /** @param array<string> $groups */
    public function __construct(
        public readonly array $groups = ['Default'],
    ) {
    }

    /** @return class-string<ConstraintValidatorInterface> */
    public function validatedBy(): string
    {
        return PasswordMatchValidator::class;
    }

    public function validate(mixed $value, string $propertyName): ?string
    {
        return null;
    }
}

class PasswordMatchValidator implements ConstraintValidatorInterface
{
    public function validate(mixed $value, object $constraint, object $object): ?string
    {
        if (!property_exists($object, 'password')) {
            return null;
        }

        /** @var string $password */
        $password = $object->password;

        if ($value === $password) {
            return null;
        }

        return 'Passwords do not match.';
    }
}

class CrossFieldConstraintDTO
{
    public string $password = '';

    #[PasswordMatch]
    public string $passwordConfirm = '';
}

// Combined with built-in validators

class CombinedConstraintDTO
{
    #[NotBlank]
    #[AllowedCode]
    public string $code = '';
}

// Parameterized constraint

#[Attribute(Attribute::TARGET_PROPERTY)]
class MaxValue implements ConstraintInterface
{
    /** @param array<string> $groups */
    public function __construct(
        public readonly int $max = 100,
        public readonly array $groups = ['Default'],
    ) {
    }

    /** @return class-string<ConstraintValidatorInterface> */
    public function validatedBy(): string
    {
        return MaxValueValidator::class;
    }

    public function validate(mixed $value, string $propertyName): ?string
    {
        return null;
    }
}

class MaxValueValidator implements ConstraintValidatorInterface
{
    public function validate(mixed $value, object $constraint, object $object): ?string
    {
        if (!is_int($value) || !$constraint instanceof MaxValue) {
            return null;
        }

        if ($value > $constraint->max) {
            return "Value must be at most {$constraint->max}.";
        }

        return null;
    }
}

class ParameterizedConstraintDTO
{
    #[MaxValue(max: 10)]
    public int $value = 0;
}

// Validator requiring constructor dependency (simulates DI-injected validator)

class DependencyRequiringValidator implements ConstraintValidatorInterface
{
    public function __construct(
        private readonly string $requiredService,
    ) {
    }

    public function validate(mixed $value, object $constraint, object $object): ?string
    {
        if ($value === 'FAIL') {
            return "Validation failed (service: {$this->requiredService}).";
        }

        return null;
    }
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class DependencyConstraint implements ConstraintInterface
{
    /** @param array<string> $groups */
    public function __construct(
        public readonly array $groups = ['Default'],
    ) {
    }

    /** @return class-string<ConstraintValidatorInterface> */
    public function validatedBy(): string
    {
        return DependencyRequiringValidator::class;
    }

    public function validate(mixed $value, string $propertyName): ?string
    {
        return null;
    }
}

class DependencyConstraintDTO
{
    #[DependencyConstraint]
    public string $code = '';
}

// Test resolver

class TestValidatorResolver implements ValidatorResolverInterface
{
    /** @param array<string, ConstraintValidatorInterface> $validators */
    public function __construct(
        private readonly array $validators = [],
    ) {
    }

    public function resolve(string $validatorClass): ConstraintValidatorInterface
    {
        $shortName = substr($validatorClass, (int) strrpos($validatorClass, '\\') + 1);
        if (isset($this->validators[$shortName])) {
            return $this->validators[$shortName];
        }

        return new $validatorClass();
    }
}
