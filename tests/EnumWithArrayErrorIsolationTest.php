<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Attributes\MapProperty;
use Pocta\DataMapper\Attributes\PropertyType;
use Pocta\DataMapper\Exceptions\ValidationException;
use Pocta\DataMapper\Mapper;
use Tests\Fixtures\Status;

// --- Inline DTOs ---

class EwaChildDto
{
    public ?string $name = null;
    public bool $primary = false;
}

class EwaParentDto
{
    public Status $status;

    /** @var array<EwaChildDto> */
    #[MapProperty(type: PropertyType::Array, arrayOf: EwaChildDto::class)]
    public array $children = [];
}

class EwaParentWithConstructorDto
{
    /**
     * @param array<EwaChildDto> $children
     */
    public function __construct(
        public Status $status,
        #[MapProperty(type: PropertyType::Array, arrayOf: EwaChildDto::class)]
        public array $children = []
    ) {
    }
}

class EwaGrandParentDto
{
    public Status $status;

    /** @var array<EwaParentDto> */
    #[MapProperty(type: PropertyType::Array, arrayOf: EwaParentDto::class)]
    public array $parents = [];
}

/**
 * Exact production-bug reproduction layout:
 * outer wraps inner; inner has an invalid enum + a valid non-nullable typed array.
 * The error is raised on the INNER level, which goes through the SHARED
 * denormalizer. That is the instance where cross-contamination happens.
 */
class EwaOuterDto
{
    #[MapProperty(classType: EwaParentDto::class)]
    public EwaParentDto $inner;
}

/**
 * Regression tests for production bug: invalid enum on a parent DTO combined with
 * a valid nested typed array would cause cross-contamination between recursive
 * denormalize() calls, ultimately assigning null to a non-nullable array property
 * and triggering a PHP TypeError (500) instead of a ValidationException (422).
 *
 * See production trace: App\Client\DTO\Request\ClientSimpleRequest::$addresses of type array.
 */
class EnumWithArrayErrorIsolationTest extends TestCase
{
    private Mapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new Mapper();
    }

    /**
     * Test 1: invalid enum + valid nested array -> ValidationException, no TypeError.
     */
    public function testInvalidEnumWithValidArrayThrowsValidationException(): void
    {
        $data = [
            'status' => 'Not A Real Status',
            'children' => [
                ['name' => 'foo', 'primary' => true],
            ],
        ];

        try {
            $this->mapper->fromArray($data, EwaParentDto::class);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('status', $errors);
            $this->assertStringContainsString('Not A Real Status', $errors['status']);
            $this->assertArrayNotHasKey('children', $errors);
            $this->assertArrayNotHasKey('children[0]', $errors);
            $this->assertArrayNotHasKey('children[0].name', $errors);
        }
    }

    /**
     * Test 2: valid enum + invalid nested array element -> only element errors.
     */
    public function testValidEnumWithInvalidNestedElement(): void
    {
        $data = [
            'status' => 'active',
            'children' => [
                ['name' => 'foo', 'primary' => true],
                ['name' => 'bar', 'primary' => 'not-a-bool'],
            ],
        ];

        try {
            $this->mapper->fromArray($data, EwaParentDto::class);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('children[1].primary', $errors);
            $this->assertArrayNotHasKey('status', $errors);
            $this->assertArrayNotHasKey('children[0].name', $errors);
            $this->assertArrayNotHasKey('children[0].primary', $errors);
        }
    }

    /**
     * Test 3: invalid enum + invalid nested element -> both errors collected.
     */
    public function testInvalidEnumWithInvalidNestedElement(): void
    {
        $data = [
            'status' => 'Invalid',
            'children' => [
                ['name' => 'foo', 'primary' => 'not-a-bool'],
            ],
        ];

        try {
            $this->mapper->fromArray($data, EwaParentDto::class);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('status', $errors);
            $this->assertArrayHasKey('children[0].primary', $errors);
        }
    }

    /**
     * Test 4: invalid enum + empty array -> only enum error.
     */
    public function testInvalidEnumWithEmptyArray(): void
    {
        $data = [
            'status' => 'Invalid',
            'children' => [],
        ];

        try {
            $this->mapper->fromArray($data, EwaParentDto::class);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('status', $errors);
            $this->assertCount(1, $errors);
        }
    }

    /**
     * Test 5: invalid enum + missing array (has default []) -> only enum error, no TypeError.
     */
    public function testInvalidEnumWithMissingArray(): void
    {
        $data = [
            'status' => 'Invalid',
        ];

        try {
            $this->mapper->fromArray($data, EwaParentDto::class);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('status', $errors);
            $this->assertCount(1, $errors);
        }
    }

    /**
     * Test 6: non-nullable array property + null in JSON -> validation error, not TypeError.
     */
    public function testNonNullableArrayWithNullInJson(): void
    {
        $data = [
            'status' => 'active',
            'children' => null,
        ];

        try {
            $this->mapper->fromArray($data, EwaParentDto::class);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('children', $errors);
            $this->assertStringContainsString('null', strtolower($errors['children']));
        }
    }

    /**
     * Test 7: deep recursion - error at root level should not contaminate grandchildren.
     */
    public function testDeepRecursionDoesNotContaminate(): void
    {
        $data = [
            'status' => 'Invalid',
            'parents' => [
                [
                    'status' => 'active',
                    'children' => [
                        ['name' => 'foo', 'primary' => true],
                    ],
                ],
            ],
        ];

        try {
            $this->mapper->fromArray($data, EwaGrandParentDto::class);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('status', $errors);
            $this->assertArrayNotHasKey('parents[0].status', $errors);
            $this->assertArrayNotHasKey('parents[0].children[0].name', $errors);
        }
    }

    /**
     * Same reproduction but through constructor-based DTO (common real-world case).
     */
    public function testInvalidEnumWithValidArrayViaConstructor(): void
    {
        $data = [
            'status' => 'Not A Real Status',
            'children' => [
                ['name' => 'foo', 'primary' => true],
            ],
        ];

        try {
            $this->mapper->fromArray($data, EwaParentWithConstructorDto::class);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('status', $errors);
        }
    }

    /**
     * Positive case: valid input should still work unchanged.
     */
    public function testValidInputStillWorks(): void
    {
        $data = [
            'status' => 'active',
            'children' => [
                ['name' => 'foo', 'primary' => true],
                ['name' => 'bar', 'primary' => false],
            ],
        ];

        $result = $this->mapper->fromArray($data, EwaParentDto::class);

        $this->assertSame(Status::Active, $result->status);
        $this->assertCount(2, $result->children);
        $this->assertSame('foo', $result->children[0]->name);
        $this->assertTrue($result->children[0]->primary);
        $this->assertSame('bar', $result->children[1]->name);
        $this->assertFalse($result->children[1]->primary);
    }

    /**
     * PRODUCTION BUG REPRODUCTION:
     * invalid enum on a nested DTO + valid typed array on same nested DTO.
     * The shared denormalizer (singleton in TypeResolver) is re-entered for the
     * array elements and sees the still-uncleared error from the parent enum,
     * raising a ValidationException that is caught and produces null -> TypeError.
     */
    public function testInvalidEnumInNestedDtoWithValidArrayDoesNotTypeError(): void
    {
        $data = [
            'inner' => [
                'status' => 'Not A Real Status',
                'children' => [
                    ['name' => 'foo', 'primary' => true],
                ],
            ],
        ];

        try {
            $this->mapper->fromArray($data, EwaOuterDto::class);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('inner.status', $errors);
            $this->assertStringContainsString('Not A Real Status', $errors['inner.status']);
            $this->assertArrayNotHasKey('inner.children', $errors);
            $this->assertArrayNotHasKey('inner.children[0]', $errors);
            $this->assertArrayNotHasKey('inner.children[0].name', $errors);
        }
    }

    /**
     * Reproduces the exact production symptom via JSON entry point.
     */
    public function testReproductionViaFromJson(): void
    {
        $json = json_encode([
            'status' => 'Not A Real Status',
            'children' => [
                ['name' => 'foo', 'primary' => true],
            ],
        ], JSON_THROW_ON_ERROR);

        try {
            $this->mapper->fromJson($json, EwaParentDto::class);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('status', $errors);
        }
    }
}
