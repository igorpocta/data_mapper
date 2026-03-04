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

// --- DTOs: nested object BEFORE array of objects (sibling properties) ---

class PathPrefixAdditionalInfo
{
    public string $maritalStatus = '';
    public string $job = '';
}

class PathPrefixAddressItem
{
    public Status $type;
    public string $street = '';
}

class PathPrefixClientDTO
{
    public string $firstName = '';

    #[MapProperty(classType: PathPrefixAdditionalInfo::class)]
    public PathPrefixAdditionalInfo $additionalInfo;

    /** @var array<PathPrefixAddressItem> */
    #[MapProperty(type: PropertyType::Array, arrayOf: PathPrefixAddressItem::class)]
    public array $addresses = [];
}

class PathPrefixRequestDTO
{
    public string $agentId = '';

    #[MapProperty(classType: PathPrefixClientDTO::class)]
    public PathPrefixClientDTO $client;
}

// --- DTOs: multiple sibling nested objects ---

class PathPrefixSiblingA
{
    public string $value = '';
}

class PathPrefixSiblingB
{
    public Status $status;
}

class PathPrefixMultipleSiblingsDTO
{
    public string $name = '';

    #[MapProperty(classType: PathPrefixSiblingA::class)]
    public PathPrefixSiblingA $siblingA;

    #[MapProperty(classType: PathPrefixSiblingB::class)]
    public PathPrefixSiblingB $siblingB;
}

class PathPrefixWrapperDTO
{
    public string $id = '';

    #[MapProperty(classType: PathPrefixMultipleSiblingsDTO::class)]
    public PathPrefixMultipleSiblingsDTO $nested;
}

// --- DTOs: object sibling before array, 3 levels deep ---

class PathPrefixLevel3Item
{
    public Status $status;
    public string $label = '';
}

class PathPrefixLevel2Info
{
    public string $note = '';
}

class PathPrefixLevel2
{
    #[MapProperty(classType: PathPrefixLevel2Info::class)]
    public PathPrefixLevel2Info $info;

    /** @var array<PathPrefixLevel3Item> */
    #[MapProperty(type: PropertyType::Array, arrayOf: PathPrefixLevel3Item::class)]
    public array $items = [];
}

class PathPrefixLevel1
{
    public string $title = '';

    #[MapProperty(classType: PathPrefixLevel2::class)]
    public PathPrefixLevel2 $level2;
}

/**
 * Tests that pathPrefix is properly saved/restored when ObjectType and ArrayType
 * process sibling properties. The bug: ObjectType/ArrayType reset pathPrefix to ''
 * instead of restoring the previous value, corrupting paths for subsequent siblings.
 *
 * Scenario: Parent has a nested object property processed BEFORE an array property.
 * After ObjectType processes the nested object, it resets pathPrefix to '',
 * so the array property loses its parent prefix in error paths.
 */
class PathPrefixRestoreTest extends TestCase
{
    private Mapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new Mapper();
    }

    // =========================================================================
    // Core bug: nested object sibling resets pathPrefix before array sibling
    // =========================================================================

    /**
     * The exact bug scenario: client has additionalInfo (object) before addresses (array).
     * Invalid enum in addresses[0].type should show as client.addresses[0].type,
     * not addresses[0].type.
     */
    public function testObjectSiblingBeforeArrayPreservesFullPath(): void
    {
        $data = [
            'agentId' => 'agent-001',
            'client' => [
                'firstName' => 'Jan',
                'additionalInfo' => [
                    'maritalStatus' => 'Single',
                    'job' => 'Developer',
                ],
                'addresses' => [
                    ['type' => 'InvalidType', 'street' => 'Main St'],
                ],
            ],
        ];

        try {
            $this->mapper->fromArray($data, PathPrefixRequestDTO::class);
            $this->fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            // Must include full path with client. prefix
            $this->assertArrayHasKey('client.addresses[0].type', $errors, 
                'Error path must include parent prefix "client." — got keys: ' . implode(', ', array_keys($errors)));
            // Must NOT have the broken short path
            $this->assertArrayNotHasKey('addresses[0].type', $errors,
                'Error path must not lose parent prefix');
        }
    }

    /**
     * Same structure but with valid data — should succeed without errors.
     */
    public function testObjectSiblingBeforeArrayWorksWithValidData(): void
    {
        $data = [
            'agentId' => 'agent-001',
            'client' => [
                'firstName' => 'Jan',
                'additionalInfo' => [
                    'maritalStatus' => 'Single',
                    'job' => 'Developer',
                ],
                'addresses' => [
                    ['type' => 'active', 'street' => 'Main St'],
                ],
            ],
        ];

        $result = $this->mapper->fromArray($data, PathPrefixRequestDTO::class);
        $this->assertSame('Jan', $result->client->firstName);
        $this->assertSame('Developer', $result->client->additionalInfo->job);
        $this->assertCount(1, $result->client->addresses);
        $this->assertSame(Status::Active, $result->client->addresses[0]->type);
    }

    /**
     * Multiple elements in the array — error in second element should
     * have correct index AND parent prefix.
     */
    public function testObjectSiblingBeforeArrayWithMultipleElements(): void
    {
        $data = [
            'agentId' => 'agent-001',
            'client' => [
                'firstName' => 'Jan',
                'additionalInfo' => [
                    'maritalStatus' => 'Single',
                    'job' => 'Developer',
                ],
                'addresses' => [
                    ['type' => 'active', 'street' => 'Main St'],
                    ['type' => 'BadValue', 'street' => 'Side St'],
                ],
            ],
        ];

        try {
            $this->mapper->fromArray($data, PathPrefixRequestDTO::class);
            $this->fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('client.addresses[1].type', $errors,
                'Error in second array element must include full path — got keys: ' . implode(', ', array_keys($errors)));
            $this->assertArrayNotHasKey('addresses[1].type', $errors);
        }
    }

    // =========================================================================
    // Multiple sibling nested objects
    // =========================================================================

    /**
     * Two sibling nested objects: siblingA (valid) processed before siblingB (invalid).
     * Error in siblingB must include the parent prefix.
     */
    public function testMultipleSiblingObjectsPreserveFullPath(): void
    {
        $data = [
            'id' => 'wrap-001',
            'nested' => [
                'name' => 'Test',
                'siblingA' => ['value' => 'OK'],
                'siblingB' => ['status' => 'InvalidEnum'],
            ],
        ];

        try {
            $this->mapper->fromArray($data, PathPrefixWrapperDTO::class);
            $this->fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('nested.siblingB.status', $errors,
                'Sibling object error must include parent prefix — got keys: ' . implode(', ', array_keys($errors)));
            $this->assertArrayNotHasKey('siblingB.status', $errors,
                'Error path must not lose parent prefix');
        }
    }

    // =========================================================================
    // 3-level deep: object sibling before array at each level
    // =========================================================================

    /**
     * Level1 → Level2 has info (object) before items[] (array).
     * Invalid enum in items[0].status must include full path from root.
     */
    public function testThreeLevelDeepObjectBeforeArrayPreservesFullPath(): void
    {
        $data = [
            'title' => 'Root',
            'level2' => [
                'info' => ['note' => 'Some note'],
                'items' => [
                    ['status' => 'InvalidStatus', 'label' => 'Item 1'],
                ],
            ],
        ];

        try {
            $this->mapper->fromArray($data, PathPrefixLevel1::class);
            $this->fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('level2.items[0].status', $errors,
                'Deeply nested path must be preserved — got keys: ' . implode(', ', array_keys($errors)));
            $this->assertArrayNotHasKey('items[0].status', $errors);
        }
    }

    // =========================================================================
    // Error message content verification
    // =========================================================================

    /**
     * The error message itself should contain the full path, not just the leaf.
     */
    public function testErrorMessageContainsFullPath(): void
    {
        $data = [
            'agentId' => 'agent-001',
            'client' => [
                'firstName' => 'Jan',
                'additionalInfo' => [
                    'maritalStatus' => 'Single',
                    'job' => 'Developer',
                ],
                'addresses' => [
                    ['type' => 'Permanent2', 'street' => 'Main St'],
                ],
            ],
        ];

        try {
            $this->mapper->fromArray($data, PathPrefixRequestDTO::class);
            $this->fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $errorKey = 'client.addresses[0].type';
            $this->assertArrayHasKey($errorKey, $errors);
            // The error message from BackedEnumType includes the fieldName
            $this->assertStringContainsString('client.addresses[0].type', $errors[$errorKey],
                'Error message should reference the full path');
        }
    }

    /**
     * toApiResponse should surface the correct full paths in the validation context.
     */
    public function testToApiResponseContainsFullPaths(): void
    {
        $data = [
            'agentId' => 'agent-001',
            'client' => [
                'firstName' => 'Jan',
                'additionalInfo' => [
                    'maritalStatus' => 'Single',
                    'job' => 'Developer',
                ],
                'addresses' => [
                    ['type' => 'BadType', 'street' => 'Main St'],
                ],
            ],
        ];

        try {
            $this->mapper->fromArray($data, PathPrefixRequestDTO::class);
            $this->fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $response = $e->toApiResponse();
            $this->assertSame(422, $response['code']);
            /** @var array<string, mixed> $context */
            $context = $response['context'];
            /** @var array<string, mixed> $validation */
            $validation = $context['validation'];
            $this->assertArrayHasKey('client.addresses[0].type', $validation,
                'API response must contain full path — got keys: ' . implode(', ', array_keys($validation)));
        }
    }
}
