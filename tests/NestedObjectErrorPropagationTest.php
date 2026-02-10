<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Attributes\MapProperty;
use Pocta\DataMapper\Attributes\PropertyType;
use Pocta\DataMapper\Exceptions\ValidationException;
use Pocta\DataMapper\Mapper;
use Pocta\DataMapper\MapperOptions;
use Tests\Fixtures\Status;

// --- Inline DTOs for nested enum error propagation tests ---

class NestedEnumChild
{
    public Status $status;
    public string $value;
}

class NestedEnumParent
{
    public string $name;

    #[MapProperty(classType: NestedEnumChild::class)]
    public NestedEnumChild $child;
}

class NullableNestedEnumParent
{
    public string $name;

    #[MapProperty(classType: NestedEnumChild::class)]
    public ?NestedEnumChild $child = null;
}

class DeeplyNestedGrandchild
{
    public Status $status;
    public string $label;
}

class DeeplyNestedChild
{
    public string $name;

    #[MapProperty(classType: DeeplyNestedGrandchild::class)]
    public DeeplyNestedGrandchild $grandchild;
}

class DeeplyNestedParent
{
    public string $title;

    #[MapProperty(classType: DeeplyNestedChild::class)]
    public DeeplyNestedChild $child;
}

class NestedEnumArrayItem
{
    public Status $status;
    public string $name;
}

class NestedEnumArrayParent
{
    public string $title;

    /** @var array<NestedEnumArrayItem> */
    #[MapProperty(type: PropertyType::Array, arrayOf: NestedEnumArrayItem::class)]
    public array $items;
}

/**
 * Tests that denormalization errors in nested objects propagate correctly
 * as ValidationException instead of causing TypeError on non-nullable properties.
 *
 * Bug: When a nested object fails denormalization (e.g., invalid enum value),
 * the error was stored under a nested key like "child.status" but the guard
 * in setPropertyValue() checked for the exact key "child", causing null to be
 * assigned to a non-nullable property.
 */
class NestedObjectErrorPropagationTest extends TestCase
{
    private Mapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new Mapper();
    }

    public function testInvalidEnumInNestedObjectThrowsValidationException(): void
    {
        $data = [
            'name' => 'Test',
            'child' => [
                'status' => 'InvalidValue',
                'value' => 'test',
            ],
        ];

        $this->expectException(ValidationException::class);

        $this->mapper->fromArray($data, NestedEnumParent::class);
    }

    public function testInvalidEnumInNestedObjectContainsCorrectErrorPath(): void
    {
        $data = [
            'name' => 'Test',
            'child' => [
                'status' => 'InvalidValue',
                'value' => 'test',
            ],
        ];

        try {
            $this->mapper->fromArray($data, NestedEnumParent::class);
            $this->fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('child.status', $errors);
            $this->assertStringContainsString('Invalid value', $errors['child.status']);
        }
    }

    public function testInvalidEnumInDeeplyNestedObjectThrowsValidationException(): void
    {
        $data = [
            'title' => 'Test',
            'child' => [
                'name' => 'Child',
                'grandchild' => [
                    'status' => 'BadEnum',
                    'label' => 'test',
                ],
            ],
        ];

        $this->expectException(ValidationException::class);

        $this->mapper->fromArray($data, DeeplyNestedParent::class);
    }

    public function testInvalidEnumInDeeplyNestedObjectContainsCorrectErrorPath(): void
    {
        $data = [
            'title' => 'Test',
            'child' => [
                'name' => 'Child',
                'grandchild' => [
                    'status' => 'BadEnum',
                    'label' => 'test',
                ],
            ],
        ];

        try {
            $this->mapper->fromArray($data, DeeplyNestedParent::class);
            $this->fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('child.grandchild.status', $errors);
        }
    }

    public function testInvalidEnumInArrayOfNestedObjectsThrowsValidationException(): void
    {
        $data = [
            'title' => 'Test',
            'items' => [
                ['status' => 'active', 'name' => 'OK Item'],
                ['status' => 'InvalidEnum', 'name' => 'Bad Item'],
            ],
        ];

        $this->expectException(ValidationException::class);

        $this->mapper->fromArray($data, NestedEnumArrayParent::class);
    }

    public function testInvalidEnumInArrayOfNestedObjectsContainsCorrectErrorPath(): void
    {
        $data = [
            'title' => 'Test',
            'items' => [
                ['status' => 'active', 'name' => 'OK Item'],
                ['status' => 'InvalidEnum', 'name' => 'Bad Item'],
            ],
        ];

        try {
            $this->mapper->fromArray($data, NestedEnumArrayParent::class);
            $this->fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('items[1].status', $errors);
        }
    }

    public function testNullableNestedObjectWithInvalidEnumThrowsValidationException(): void
    {
        $data = [
            'name' => 'Test',
            'child' => [
                'status' => 'InvalidValue',
                'value' => 'test',
            ],
        ];

        $this->expectException(ValidationException::class);

        $this->mapper->fromArray($data, NullableNestedEnumParent::class);
    }

    public function testValidNestedEnumStillWorks(): void
    {
        $data = [
            'name' => 'Test',
            'child' => [
                'status' => 'active',
                'value' => 'test',
            ],
        ];

        $result = $this->mapper->fromArray($data, NestedEnumParent::class);

        $this->assertInstanceOf(NestedEnumParent::class, $result);
        $this->assertSame('Test', $result->name);
        $this->assertInstanceOf(NestedEnumChild::class, $result->child);
        $this->assertSame(Status::Active, $result->child->status);
        $this->assertSame('test', $result->child->value);
    }

    public function testInvalidEnumInNestedObjectViaFromJson(): void
    {
        $json = json_encode([
            'name' => 'Test',
            'child' => [
                'status' => 'InvalidValue',
                'value' => 'test',
            ],
        ], JSON_THROW_ON_ERROR);

        try {
            $this->mapper->fromJson($json, NestedEnumParent::class);
            $this->fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('child.status', $errors);
        }
    }

    public function testMultipleErrorsInNestedObjectCollectedCorrectly(): void
    {
        $mapper = new Mapper(options: MapperOptions::strict());

        $data = [
            'title' => 'Test',
            'items' => [
                ['status' => 'InvalidOne', 'name' => 'Bad 1'],
                ['status' => 'InvalidTwo', 'name' => 'Bad 2'],
            ],
        ];

        try {
            $mapper->fromArray($data, NestedEnumArrayParent::class);
            $this->fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('items[0].status', $errors);
            $this->assertArrayHasKey('items[1].status', $errors);
        }
    }

    public function testMultipleNestedObjectsWithErrorsAtSameLevel(): void
    {
        $data = [
            'title' => 'Test',
            'child' => [
                'name' => 'Child',
                'grandchild' => [
                    'status' => 'BadEnum',
                    'label' => 'test',
                ],
            ],
        ];

        try {
            $this->mapper->fromArray($data, DeeplyNestedParent::class);
            $this->fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            // Should have the deeply nested path, not a shallow one
            $this->assertArrayHasKey('child.grandchild.status', $errors);
            // Should NOT have a generic "child" error
            $this->assertArrayNotHasKey('child', $errors);
            $this->assertArrayNotHasKey('child.grandchild', $errors);
        }
    }

    public function testValidationExceptionContainsValidEnumValues(): void
    {
        $data = [
            'name' => 'Test',
            'child' => [
                'status' => 'WrongValue',
                'value' => 'test',
            ],
        ];

        try {
            $this->mapper->fromArray($data, NestedEnumParent::class);
            $this->fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $errorMessage = $errors['child.status'];
            $this->assertStringContainsString('active', $errorMessage);
            $this->assertStringContainsString('inactive', $errorMessage);
            $this->assertStringContainsString('pending', $errorMessage);
            $this->assertStringContainsString('WrongValue', $errorMessage);
        }
    }

    public function testToApiResponseWorksWithNestedErrors(): void
    {
        $data = [
            'name' => 'Test',
            'child' => [
                'status' => 'InvalidValue',
                'value' => 'test',
            ],
        ];

        try {
            $this->mapper->fromArray($data, NestedEnumParent::class);
            $this->fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $response = $e->toApiResponse();
            $this->assertSame(422, $response['code']);
            $context = $response['context'];
            $this->assertIsArray($context);
            /** @var array<string, mixed> $context */
            $validation = $context['validation'];
            $this->assertIsArray($validation);
            /** @var array<string, mixed> $validation */
            $this->assertArrayHasKey('child.status', $validation);
        }
    }
}
