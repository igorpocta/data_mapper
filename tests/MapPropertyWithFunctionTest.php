<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Mapper;
use Pocta\DataMapper\Attributes\MapPropertyWithFunction as HydrateWith;
use Pocta\DataMapper\Attributes\HydrationMode;

// Helper functions for hydrator
function uppercase_value(mixed $payload): mixed {
    return is_string($payload) ? strtoupper($payload) : $payload;
}

function full_name_from_parent(mixed $payload): string {
    if (is_array($payload)) {
        $first = is_string($payload['first'] ?? null) ? $payload['first'] : '';
        $last = is_string($payload['last'] ?? null) ? $payload['last'] : '';
        return trim($first . ' ' . $last);
    }
    return '';
}

function extract_source_from_full(mixed $payload): string {
    if (is_array($payload) && isset($payload['meta']) && is_array($payload['meta']) && isset($payload['meta']['source'])) {
        $source = $payload['meta']['source'];
        return is_string($source) || is_numeric($source) ? (string) $source : '';
    }
    return '';
}

class MapPropertyWithFunctionTest extends TestCase
{
    private Mapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new Mapper();
    }

    public function testHydrateWithValueMode(): void
    {
        $class = new class {
            #[HydrateWith(function: __NAMESPACE__ . '\\uppercase_value', mode: HydrationMode::VALUE)]
            public string $email;
        };

        $obj = $this->mapper->fromArray(['email' => 'foo@bar.com'], get_class($class));
        $this->assertSame('FOO@BAR.COM', $obj->email);
    }

    public function testHydrateWithParentMode(): void
    {
        $class = new class {
            public string $first;
            public string $last;
            #[HydrateWith(function: __NAMESPACE__ . '\\full_name_from_parent', mode: HydrationMode::PARENT)]
            public string $fullName;
        };

        // 'fullName' není ve zdrojovém JSONu – hydratace se i tak provede
        $obj = $this->mapper->fromArray([
            'first' => 'John',
            'last' => 'Doe'
        ], get_class($class));
        $this->assertSame('John Doe', $obj->fullName);
    }

    public function testHydrateWithArrayCallable(): void
    {
        $class = new class {
            #[HydrateWith(function: [self::class, 'hydrateTestData'], mode: HydrationMode::VALUE)]
            public string $email;

            public static function hydrateTestData(mixed $payload): mixed
            {
                return is_string($payload) ? strtoupper($payload) : $payload;
            }
        };

        $obj = $this->mapper->fromArray(['email' => 'foo@bar.com'], get_class($class));
        $this->assertSame('FOO@BAR.COM', $obj->email);
    }

    public function testHydrateWithFullMode(): void
    {
        // Define concrete classes for nested mapping
        $outerClassName = __NAMESPACE__ . '\\HydrateOuter';
        $userClassName = __NAMESPACE__ . '\\HydrateUser';

        if (!class_exists($userClassName)) {
            eval('namespace ' . __NAMESPACE__ . ';
                /** @property string $name */
                /** @property string $metaSource */
                class HydrateUser {
                    public string $name;
                    #[\\Pocta\\DataMapper\\Attributes\\MapPropertyWithFunction(function: __NAMESPACE__ . "\\\\extract_source_from_full", mode: \\Pocta\\DataMapper\\Attributes\\HydrationMode::FULL)]
                    public string $metaSource;
                }');
        }
        if (!class_exists($outerClassName)) {
            eval('namespace ' . __NAMESPACE__ . ';
                /** @property array<string, mixed> $meta */
                /** @property HydrateUser $user */
                class HydrateOuter {
                    /** @var array<string, mixed> */
                    public array $meta;
                    public HydrateUser $user;
                }');
        }

        $input = [
            'meta' => ['source' => 'api'],
            // 'metaSource' není ve zdroji, hydratace z FULL payloadu ho doplní
            'user' => ['name' => 'Alice']
        ];
        /** @var object{user: object{metaSource: string}} $mapped */
        $mapped = $this->mapper->fromArray($input, $outerClassName); // @phpstan-ignore-line argument.type
        $this->assertSame('api', $mapped->user->metaSource);
    }
}
