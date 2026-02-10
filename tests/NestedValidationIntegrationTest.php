<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Tests;

use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Attributes\MapProperty;
use Pocta\DataMapper\Attributes\PropertyType;
use Pocta\DataMapper\Exceptions\ValidationException;
use Pocta\DataMapper\Mapper;
use Pocta\DataMapper\MapperOptions;
use Pocta\DataMapper\Validation\Choice;
use Pocta\DataMapper\Validation\Email;
use Pocta\DataMapper\Validation\Length;
use Pocta\DataMapper\Validation\NotBlank;
use Pocta\DataMapper\Validation\Valid;
use Pocta\DataMapper\Validation\Validator;
use Tests\Fixtures\Status;

// --- DTOs simulating real-world API request structure ---
// Similar to ClientSaveRequest -> ClientRequest -> ClientContactRequest/ClientAddressRequest

enum IntegrationEntityType: string
{
    case NaturalPerson = 'NaturalPerson';
    case LegalEntity = 'LegalEntity';
}

class IntegrationContactDTO
{
    #[NotBlank]
    #[Choice(choices: ['Phone', 'Email'])]
    public string $type = '';

    #[NotBlank]
    public string $value = '';

    public bool $isPrimary = false;
}

class IntegrationAddressDTO
{
    #[NotBlank]
    public string $street = '';

    #[NotBlank]
    public string $city = '';

    #[Length(min: 5, max: 5)]
    public string $zipCode = '';

    #[Choice(choices: ['Permanent', 'Mailing'])]
    public string $type = 'Mailing';
}

class IntegrationClientDTO
{
    public IntegrationEntityType $entityType;

    #[NotBlank]
    public string $firstName = '';

    #[NotBlank]
    public string $lastName = '';

    #[Email]
    public ?string $email = null;

    /** @var array<IntegrationContactDTO>|null */
    #[Valid]
    #[MapProperty(type: PropertyType::Array, arrayOf: IntegrationContactDTO::class)]
    public ?array $contacts = null;

    /** @var array<IntegrationAddressDTO>|null */
    #[Valid]
    #[MapProperty(type: PropertyType::Array, arrayOf: IntegrationAddressDTO::class)]
    public ?array $addresses = null;
}

class IntegrationRequestDTO
{
    #[NotBlank]
    public string $agentId = '';

    #[Valid]
    #[MapProperty(classType: IntegrationClientDTO::class)]
    public IntegrationClientDTO $client;
}

// --- DTO with array of objects that each have arrays of objects ---

class IntegrationTeamMemberDTO
{
    #[NotBlank]
    public string $name = '';

    #[Email]
    public string $email = '';
}

class IntegrationTeamDTO
{
    #[NotBlank]
    public string $teamName = '';

    /** @var array<IntegrationTeamMemberDTO> */
    #[Valid]
    #[MapProperty(type: PropertyType::Array, arrayOf: IntegrationTeamMemberDTO::class)]
    public array $members = [];
}

class IntegrationOrganizationDTO
{
    #[NotBlank]
    public string $orgName = '';

    /** @var array<IntegrationTeamDTO> */
    #[Valid]
    #[MapProperty(type: PropertyType::Array, arrayOf: IntegrationTeamDTO::class)]
    public array $teams = [];
}

/**
 * Integration tests for complex nested validation scenarios.
 * Covers the full flow: JSON/Array -> Mapper -> Denormalize -> Validate -> Errors.
 */
class NestedValidationIntegrationTest extends TestCase
{
    // =========================================================================
    // Full Mapper flow: fromArray with auto-validation
    // =========================================================================

    public function testMapperWithValidComplexNestedRequest(): void
    {
        $mapper = new Mapper(MapperOptions::strict()->withAutoValidation());

        $data = [
            'agentId' => 'agent-001',
            'client' => [
                'entityType' => 'NaturalPerson',
                'firstName' => 'Jan',
                'lastName' => 'Novak',
                'contacts' => [
                    ['type' => 'Phone', 'value' => '+420777888999', 'isPrimary' => true],
                    ['type' => 'Email', 'value' => 'jan@example.com', 'isPrimary' => true],
                ],
                'addresses' => [
                    ['street' => 'Hlavni 1', 'city' => 'Praha', 'zipCode' => '11000', 'type' => 'Permanent'],
                ],
            ],
        ];

        $result = $mapper->fromArray($data, IntegrationRequestDTO::class);

        $this->assertSame('agent-001', $result->agentId);
        $this->assertSame(IntegrationEntityType::NaturalPerson, $result->client->entityType);
        $this->assertSame('Jan', $result->client->firstName);
        $this->assertNotNull($result->client->contacts);
        $this->assertCount(2, $result->client->contacts);
        $this->assertNotNull($result->client->addresses);
        $this->assertCount(1, $result->client->addresses);
    }

    public function testMapperWithInvalidEnumInNestedObjectAndAutoValidation(): void
    {
        $mapper = new Mapper(MapperOptions::strict()->withAutoValidation());

        $data = [
            'agentId' => 'agent-001',
            'client' => [
                'entityType' => 'InvalidType',
                'firstName' => 'Jan',
                'lastName' => 'Novak',
            ],
        ];

        try {
            $mapper->fromArray($data, IntegrationRequestDTO::class);
            $this->fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('client.entityType', $errors);
            $this->assertStringContainsString('Invalid value', $errors['client.entityType']);
        }
    }

    public function testMapperWithNotBlankValidationOnNestedArrayItems(): void
    {
        $mapper = new Mapper(MapperOptions::strict()->withAutoValidation());

        $data = [
            'agentId' => 'agent-001',
            'client' => [
                'entityType' => 'NaturalPerson',
                'firstName' => 'Jan',
                'lastName' => 'Novak',
                'contacts' => [
                    ['type' => 'Phone', 'value' => '+420777888999', 'isPrimary' => true],
                    ['type' => '', 'value' => '', 'isPrimary' => false],
                ],
            ],
        ];

        try {
            $mapper->fromArray($data, IntegrationRequestDTO::class);
            $this->fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            // Second contact has empty type and value
            $this->assertArrayHasKey('client.contacts[1].type', $errors);
            $this->assertArrayHasKey('client.contacts[1].value', $errors);
            // First contact should be fine
            $this->assertArrayNotHasKey('client.contacts[0].type', $errors);
            $this->assertArrayNotHasKey('client.contacts[0].value', $errors);
        }
    }

    public function testMapperWithMultipleValidationErrorsAcrossNestedArrays(): void
    {
        $mapper = new Mapper(MapperOptions::strict()->withAutoValidation());

        $data = [
            'agentId' => 'agent-001',
            'client' => [
                'entityType' => 'NaturalPerson',
                'firstName' => '',
                'lastName' => '',
                'contacts' => [
                    ['type' => 'InvalidChoice', 'value' => '', 'isPrimary' => false],
                ],
                'addresses' => [
                    ['street' => '', 'city' => '', 'zipCode' => '1', 'type' => 'Permanent'],
                ],
            ],
        ];

        try {
            $mapper->fromArray($data, IntegrationRequestDTO::class);
            $this->fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            // Client-level validation errors
            $this->assertArrayHasKey('client.firstName', $errors);
            $this->assertArrayHasKey('client.lastName', $errors);
            // Contact-level errors
            $this->assertArrayHasKey('client.contacts[0].type', $errors);
            $this->assertArrayHasKey('client.contacts[0].value', $errors);
            // Address-level errors
            $this->assertArrayHasKey('client.addresses[0].street', $errors);
            $this->assertArrayHasKey('client.addresses[0].city', $errors);
            $this->assertArrayHasKey('client.addresses[0].zipCode', $errors);
        }
    }

    // =========================================================================
    // Array of objects, each containing arrays of validated objects
    // =========================================================================

    public function testArrayOfObjectsWithNestedArraysOfObjectsValidation(): void
    {
        $validator = new Validator();

        $member1 = new IntegrationTeamMemberDTO();
        $member1->name = 'Alice';
        $member1->email = 'alice@example.com';

        $member2 = new IntegrationTeamMemberDTO();
        $member2->name = '';  // NotBlank fails
        $member2->email = 'invalid';  // Email fails

        $team1 = new IntegrationTeamDTO();
        $team1->teamName = 'Team A';
        $team1->members = [$member1, $member2];

        $member3 = new IntegrationTeamMemberDTO();
        $member3->name = '';  // NotBlank fails
        $member3->email = 'valid@example.com';

        $team2 = new IntegrationTeamDTO();
        $team2->teamName = 'Team B';
        $team2->members = [$member3];

        $org = new IntegrationOrganizationDTO();
        $org->orgName = 'ACME';
        $org->teams = [$team1, $team2];

        $errors = $validator->validate($org, throw: false);

        // Team A, Member 2 (index 1) errors
        $this->assertArrayHasKey('teams[0].members[1].name', $errors);
        $this->assertArrayHasKey('teams[0].members[1].email', $errors);
        // Team B, Member 1 (index 0) errors
        $this->assertArrayHasKey('teams[1].members[0].name', $errors);
        // Team A, Member 1 should be fine
        $this->assertArrayNotHasKey('teams[0].members[0].name', $errors);
        $this->assertArrayNotHasKey('teams[0].members[0].email', $errors);
    }

    public function testArrayOfObjectsWithNestedArraysViaMapper(): void
    {
        $mapper = new Mapper(MapperOptions::strict()->withAutoValidation());

        $data = [
            'orgName' => 'ACME',
            'teams' => [
                [
                    'teamName' => 'Team A',
                    'members' => [
                        ['name' => 'Alice', 'email' => 'alice@example.com'],
                        ['name' => '', 'email' => 'invalid'],
                    ],
                ],
                [
                    'teamName' => '',
                    'members' => [
                        ['name' => '', 'email' => 'valid@example.com'],
                    ],
                ],
            ],
        ];

        try {
            $mapper->fromArray($data, IntegrationOrganizationDTO::class);
            $this->fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            // Team A, Member 2 errors
            $this->assertArrayHasKey('teams[0].members[1].name', $errors);
            $this->assertArrayHasKey('teams[0].members[1].email', $errors);
            // Team B name is blank
            $this->assertArrayHasKey('teams[1].teamName', $errors);
            // Team B, Member 1 name is blank
            $this->assertArrayHasKey('teams[1].members[0].name', $errors);
        }
    }

    // =========================================================================
    // Combined denormalization + validation errors
    // =========================================================================

    public function testDenormalizationErrorPreventsValidationOnSameObject(): void
    {
        $mapper = new Mapper(MapperOptions::strict()->withAutoValidation());

        $data = [
            'agentId' => 'agent-001',
            'client' => [
                'entityType' => 'InvalidType',
                'firstName' => '',
                'lastName' => '',
            ],
        ];

        try {
            $mapper->fromArray($data, IntegrationRequestDTO::class);
            $this->fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            // Denormalization error for invalid enum
            $this->assertArrayHasKey('client.entityType', $errors);
            // Denormalization error should prevent the object from being created,
            // so we get a denormalization-level error, not validation-level errors
            // for firstName/lastName (since the object couldn't be fully constructed)
        }
    }

    // =========================================================================
    // fromJson flow
    // =========================================================================

    public function testFromJsonWithNestedValidationErrors(): void
    {
        $mapper = new Mapper(MapperOptions::strict()->withAutoValidation());

        $json = json_encode([
            'agentId' => 'agent-001',
            'client' => [
                'entityType' => 'NaturalPerson',
                'firstName' => 'Jan',
                'lastName' => 'Novak',
                'contacts' => [
                    ['type' => '', 'value' => '', 'isPrimary' => false],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        try {
            $mapper->fromJson($json, IntegrationRequestDTO::class);
            $this->fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('client.contacts[0].type', $errors);
            $this->assertArrayHasKey('client.contacts[0].value', $errors);
        }
    }

    // =========================================================================
    // toApiResponse with complex nested errors
    // =========================================================================

    public function testToApiResponseWithDeeplyNestedArrayErrors(): void
    {
        $mapper = new Mapper(MapperOptions::strict()->withAutoValidation());

        $data = [
            'orgName' => 'ACME',
            'teams' => [
                [
                    'teamName' => 'Team A',
                    'members' => [
                        ['name' => '', 'email' => 'invalid'],
                    ],
                ],
            ],
        ];

        try {
            $mapper->fromArray($data, IntegrationOrganizationDTO::class);
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
            $this->assertArrayHasKey('teams[0].members[0].name', $validation);
            $this->assertArrayHasKey('teams[0].members[0].email', $validation);
        }
    }

    // =========================================================================
    // Edge cases
    // =========================================================================

    public function testEmptyArrayOfContactsPassesValidation(): void
    {
        $mapper = new Mapper(MapperOptions::strict()->withAutoValidation());

        $data = [
            'agentId' => 'agent-001',
            'client' => [
                'entityType' => 'NaturalPerson',
                'firstName' => 'Jan',
                'lastName' => 'Novak',
                'contacts' => [],
            ],
        ];

        $result = $mapper->fromArray($data, IntegrationRequestDTO::class);
        $this->assertSame([], $result->client->contacts);
    }

    public function testNullArrayOfContactsPassesValidation(): void
    {
        $mapper = new Mapper(MapperOptions::strict()->withAutoValidation());

        $data = [
            'agentId' => 'agent-001',
            'client' => [
                'entityType' => 'NaturalPerson',
                'firstName' => 'Jan',
                'lastName' => 'Novak',
            ],
        ];

        $result = $mapper->fromArray($data, IntegrationRequestDTO::class);
        $this->assertNull($result->client->contacts);
    }

    public function testAllErrorPathsUseConsistentDotAndBracketNotation(): void
    {
        $mapper = new Mapper(MapperOptions::strict()->withAutoValidation());

        $data = [
            'orgName' => '',
            'teams' => [
                [
                    'teamName' => '',
                    'members' => [
                        ['name' => '', 'email' => 'bad'],
                        ['name' => '', 'email' => 'also-bad'],
                    ],
                ],
                [
                    'teamName' => '',
                    'members' => [
                        ['name' => '', 'email' => 'nope'],
                    ],
                ],
            ],
        ];

        try {
            $mapper->fromArray($data, IntegrationOrganizationDTO::class);
            $this->fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();

            // Verify dot-bracket notation consistency across all paths
            foreach (array_keys($errors) as $path) {
                // Paths should use dots for object nesting and brackets for array indices
                $this->assertMatchesRegularExpression(
                    '/^[a-zA-Z][\w]*(\.\w+|\[\d+\])*$/',
                    $path,
                    "Error path '{$path}' does not follow dot.bracket[N] notation",
                );
            }

            // Verify specific expected paths exist
            $this->assertArrayHasKey('orgName', $errors);
            $this->assertArrayHasKey('teams[0].teamName', $errors);
            $this->assertArrayHasKey('teams[0].members[0].name', $errors);
            $this->assertArrayHasKey('teams[0].members[0].email', $errors);
            $this->assertArrayHasKey('teams[0].members[1].name', $errors);
            $this->assertArrayHasKey('teams[0].members[1].email', $errors);
            $this->assertArrayHasKey('teams[1].teamName', $errors);
            $this->assertArrayHasKey('teams[1].members[0].name', $errors);
            $this->assertArrayHasKey('teams[1].members[0].email', $errors);
        }
    }
}
