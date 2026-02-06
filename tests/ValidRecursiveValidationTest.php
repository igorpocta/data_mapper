<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Tests;

use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Exceptions\ValidationException;
use Pocta\DataMapper\Mapper;
use Pocta\DataMapper\MapperOptions;
use Pocta\DataMapper\Validation\Email;
use Pocta\DataMapper\Validation\NotBlank;
use Pocta\DataMapper\Validation\Valid;
use Pocta\DataMapper\Validation\Validator;
use Pocta\DataMapper\Attributes\MapProperty;
use Pocta\DataMapper\Attributes\PropertyType;

class ValidRecursiveValidationTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    public function testValidNestedObjectPasses(): void
    {
        $child = new ValidChildDTO();
        $child->name = 'Alice';
        $child->email = 'alice@example.com';

        $parent = new ValidParentDTO();
        $parent->title = 'Parent';
        $parent->child = $child;

        $errors = $this->validator->validate($parent, throw: false);
        $this->assertEmpty($errors);
    }

    public function testValidNestedObjectFails(): void
    {
        $child = new ValidChildDTO();
        $child->name = '';
        $child->email = 'invalid';

        $parent = new ValidParentDTO();
        $parent->title = 'Parent';
        $parent->child = $child;

        $errors = $this->validator->validate($parent, throw: false);
        $this->assertArrayHasKey('child.name', $errors);
        $this->assertArrayHasKey('child.email', $errors);
    }

    public function testValidArrayOfObjectsPasses(): void
    {
        $child1 = new ValidChildDTO();
        $child1->name = 'Alice';
        $child1->email = 'alice@example.com';

        $child2 = new ValidChildDTO();
        $child2->name = 'Bob';
        $child2->email = 'bob@example.com';

        $parent = new ValidParentWithArrayDTO();
        $parent->title = 'Parent';
        $parent->children = [$child1, $child2];

        $errors = $this->validator->validate($parent, throw: false);
        $this->assertEmpty($errors);
    }

    public function testValidArrayOfObjectsFails(): void
    {
        $child1 = new ValidChildDTO();
        $child1->name = 'Alice';
        $child1->email = 'alice@example.com';

        $child2 = new ValidChildDTO();
        $child2->name = '';
        $child2->email = 'invalid';

        $parent = new ValidParentWithArrayDTO();
        $parent->title = 'Parent';
        $parent->children = [$child1, $child2];

        $errors = $this->validator->validate($parent, throw: false);
        $this->assertEmpty(array_filter($errors, fn($k) => str_starts_with($k, 'children[0]'), ARRAY_FILTER_USE_KEY));
        $this->assertArrayHasKey('children[1].name', $errors);
        $this->assertArrayHasKey('children[1].email', $errors);
    }

    public function testValidNullNestedObjectSkipped(): void
    {
        $parent = new ValidNullableParentDTO();
        $parent->title = 'Parent';
        $parent->child = null;

        $errors = $this->validator->validate($parent, throw: false);
        $this->assertEmpty($errors);
    }

    public function testValidDeepNesting(): void
    {
        $grandchild = new ValidChildDTO();
        $grandchild->name = '';
        $grandchild->email = 'invalid';

        $middle = new ValidMiddleDTO();
        $middle->label = 'Middle';
        $middle->child = $grandchild;

        $root = new ValidRootDTO();
        $root->name = 'Root';
        $root->middle = $middle;

        $errors = $this->validator->validate($root, throw: false);
        $this->assertArrayHasKey('middle.child.name', $errors);
        $this->assertArrayHasKey('middle.child.email', $errors);
    }

    public function testValidThrowsWithPath(): void
    {
        $child = new ValidChildDTO();
        $child->name = '';
        $child->email = 'invalid';

        $parent = new ValidParentDTO();
        $parent->title = 'Parent';
        $parent->child = $child;

        try {
            $this->validator->validate($parent, throw: true);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('child.name', $e->getErrors());
        }
    }

    public function testValidWithMapper(): void
    {
        $mapper = new Mapper(MapperOptions::strict()->withAutoValidation());

        $data = [
            'title' => 'Parent',
            'child' => [
                'name' => 'Alice',
                'email' => 'alice@example.com',
            ],
        ];

        $obj = $mapper->fromArray($data, ValidParentDTO::class);
        $this->assertSame('Alice', $obj->child->name);
    }

    public function testValidWithMapperFails(): void
    {
        $mapper = new Mapper(MapperOptions::strict()->withAutoValidation());

        $this->expectException(ValidationException::class);
        $data = [
            'title' => 'Parent',
            'child' => [
                'name' => '',
                'email' => 'invalid',
            ],
        ];

        $mapper->fromArray($data, ValidParentDTO::class);
    }

    public function testValidEmptyArraySkipped(): void
    {
        $parent = new ValidParentWithArrayDTO();
        $parent->title = 'Parent';
        $parent->children = [];

        $errors = $this->validator->validate($parent, throw: false);
        $this->assertEmpty($errors);
    }

    public function testValidParentValidationAlsoRuns(): void
    {
        $child = new ValidChildDTO();
        $child->name = 'Alice';
        $child->email = 'alice@example.com';

        $parent = new ValidParentDTO();
        $parent->title = ''; // Parent's own validation fails
        $parent->child = $child;

        $errors = $this->validator->validate($parent, throw: false);
        $this->assertArrayHasKey('title', $errors);
        $this->assertCount(1, $errors); // Only parent error, child is valid
    }
}

class ValidChildDTO
{
    #[NotBlank]
    public string $name = '';

    #[Email]
    public string $email = '';
}

class ValidParentDTO
{
    #[NotBlank]
    public string $title = '';

    #[Valid]
    #[MapProperty(classType: ValidChildDTO::class)]
    public ValidChildDTO $child;
}

class ValidParentWithArrayDTO
{
    #[NotBlank]
    public string $title = '';

    /** @var array<ValidChildDTO> */
    #[Valid]
    #[MapProperty(type: PropertyType::Array, arrayOf: ValidChildDTO::class)]
    public array $children = [];
}

class ValidNullableParentDTO
{
    #[NotBlank]
    public string $title = '';

    #[Valid]
    public ?ValidChildDTO $child = null;
}

class ValidMiddleDTO
{
    #[NotBlank]
    public string $label = '';

    #[Valid]
    #[MapProperty(classType: ValidChildDTO::class)]
    public ValidChildDTO $child;
}

class ValidRootDTO
{
    #[NotBlank]
    public string $name = '';

    #[Valid]
    #[MapProperty(classType: ValidMiddleDTO::class)]
    public ValidMiddleDTO $middle;
}
