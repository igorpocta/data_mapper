<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Tests;

use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Exceptions\ValidationException;
use Pocta\DataMapper\Validation\Callback;
use Pocta\DataMapper\Validation\NotBlank;
use Pocta\DataMapper\Validation\Valid;
use Pocta\DataMapper\Validation\Validator;

class CallbackMethodValidationTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    public function testCallbackMethodReturnsErrors(): void
    {
        $obj = new CallbackMethodTestDTO();
        $obj->hasBlue = false;
        $obj->hasGold = false;

        $errors = $this->validator->validate($obj, throw: false);
        $this->assertArrayHasKey('hasBlue', $errors);
        $this->assertStringContainsString('at least one', $errors['hasBlue']);
    }

    public function testCallbackMethodPasses(): void
    {
        $obj = new CallbackMethodTestDTO();
        $obj->hasBlue = true;
        $obj->hasGold = false;

        $errors = $this->validator->validate($obj, throw: false);
        $this->assertEmpty($errors);
    }

    public function testCallbackMethodReturnsNull(): void
    {
        $obj = new CallbackMethodNullReturnDTO();
        $errors = $this->validator->validate($obj, throw: false);
        $this->assertEmpty($errors);
    }

    public function testCallbackMethodReturnsEmptyArray(): void
    {
        $obj = new CallbackMethodEmptyReturnDTO();
        $errors = $this->validator->validate($obj, throw: false);
        $this->assertEmpty($errors);
    }

    public function testMultipleCallbackMethods(): void
    {
        $obj = new MultipleCallbackMethodsDTO();
        $obj->name = '';
        $obj->age = -1;

        $errors = $this->validator->validate($obj, throw: false);
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('age', $errors);
    }

    public function testCallbackMethodWithGroups(): void
    {
        $obj = new CallbackMethodGroupsDTO();
        $obj->value = '';

        // Default group — should not trigger 'Special' group callback
        $errors = $this->validator->validate($obj, throw: false);
        $this->assertEmpty($errors);

        // Special group — should trigger
        $errors = $this->validator->validate($obj, throw: false, groups: ['Special']);
        $this->assertArrayHasKey('value', $errors);
    }

    public function testCallbackMethodThrows(): void
    {
        $obj = new CallbackMethodTestDTO();
        $obj->hasBlue = false;
        $obj->hasGold = false;

        $this->expectException(ValidationException::class);
        $this->validator->validate($obj);
    }

    public function testCallbackMethodCombinedWithPropertyValidation(): void
    {
        $obj = new CallbackMethodCombinedDTO();
        $obj->name = ''; // NotBlank fails
        $obj->items = []; // Method callback fails

        $errors = $this->validator->validate($obj, throw: false);
        $this->assertArrayHasKey('name', $errors); // From NotBlank
        $this->assertArrayHasKey('items', $errors); // From method callback
    }

    public function testCallbackMethodWithRecursiveValid(): void
    {
        $child = new CallbackMethodTestDTO();
        $child->hasBlue = false;
        $child->hasGold = false;

        $parent = new CallbackMethodParentDTO();
        $parent->child = $child;

        $errors = $this->validator->validate($parent, throw: false);
        $this->assertArrayHasKey('child.hasBlue', $errors);
    }

    public function testPropertyCallbackStillWorks(): void
    {
        $obj = new PropertyCallbackDTO();
        $obj->username = 'admin';

        $errors = $this->validator->validate($obj, throw: false);
        $this->assertArrayHasKey('username', $errors);
    }

    public function testPropertyCallbackWithoutArgumentStillWorks(): void
    {
        // Callback with null callback used on property should just pass
        $obj = new PropertyCallbackNullDTO();
        $obj->value = 'test';

        $errors = $this->validator->validate($obj, throw: false);
        $this->assertEmpty($errors);
    }
}

class CallbackMethodTestDTO
{
    public bool $hasBlue = false;
    public bool $hasGold = false;

    /**
     * @return array<string, string>
     */
    #[Callback]
    public function validateAgreements(): array
    {
        if ($this->hasBlue === false && $this->hasGold === false) {
            return ['hasBlue' => 'You must select at least one option.'];
        }
        return [];
    }
}

class CallbackMethodNullReturnDTO
{
    /**
     * @return array<string, string>|null
     */
    #[Callback]
    public function validate(): ?array
    {
        return null;
    }
}

class CallbackMethodEmptyReturnDTO
{
    /**
     * @return array<string, string>
     */
    #[Callback]
    public function validate(): array
    {
        return [];
    }
}

class MultipleCallbackMethodsDTO
{
    public string $name = '';
    public int $age = 0;

    /**
     * @return array<string, string>
     */
    #[Callback]
    public function validateName(): array
    {
        if ($this->name === '') {
            return ['name' => 'Name is required.'];
        }
        return [];
    }

    /**
     * @return array<string, string>
     */
    #[Callback]
    public function validateAge(): array
    {
        if ($this->age < 0) {
            return ['age' => 'Age must be non-negative.'];
        }
        return [];
    }
}

class CallbackMethodGroupsDTO
{
    public string $value = '';

    /**
     * @return array<string, string>
     */
    #[Callback(groups: ['Special'])]
    public function validateValue(): array
    {
        if ($this->value === '') {
            return ['value' => 'Value is required.'];
        }
        return [];
    }
}

class CallbackMethodCombinedDTO
{
    #[NotBlank]
    public string $name = '';

    /** @var array<string> */
    public array $items = [];

    /**
     * @return array<string, string>
     */
    #[Callback]
    public function validateItems(): array
    {
        if (count($this->items) === 0) {
            return ['items' => 'At least one item required.'];
        }
        return [];
    }
}

class CallbackMethodParentDTO
{
    #[Valid]
    public CallbackMethodTestDTO $child;
}

class PropertyCallbackDTO
{
    #[Callback(callback: [self::class, 'checkUsername'])]
    public string $username = '';

    public static function checkUsername(mixed $value): ?string
    {
        return $value === 'admin' ? 'Username cannot be admin' : null;
    }
}

class PropertyCallbackNullDTO
{
    #[Callback]
    public string $value = '';
}
