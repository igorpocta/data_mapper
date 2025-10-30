<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Mapper;
use Pocta\DataMapper\Exceptions\ValidationException;
use Pocta\DataMapper\Validation\Uuid;
use Pocta\DataMapper\Validation\Iban;
use Pocta\DataMapper\Validation\CreditCard;
use Pocta\DataMapper\Validation\Regex;
use Pocta\DataMapper\Validation\MacAddress;
use Pocta\DataMapper\MapperOptions;

class AdditionalValidatorsTest extends TestCase
{
    private Mapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new Mapper(options: MapperOptions::strict()->withAutoValidation());
    }

    public function testUuidValidator(): void
    {
        $data = ['id' => '550e8400-e29b-41d4-a716-446655440000'];
        $obj = $this->mapper->fromArray($data, UuidDTOTest::class);
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $obj->id);
    }

    public function testUuidValidatorInvalid(): void
    {
        $this->expectException(ValidationException::class);
        $data = ['id' => 'invalid-uuid'];
        $this->mapper->fromArray($data, UuidDTOTest::class);
    }

    public function testUuidVersion4Validator(): void
    {
        $data = ['id' => '550e8400-e29b-41d4-a716-446655440000'];
        $obj = $this->mapper->fromArray($data, UuidV4DTOTest::class);
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $obj->id);
    }

    public function testIbanValidator(): void
    {
        $data = ['account' => 'DE89370400440532013000'];
        $obj = $this->mapper->fromArray($data, IbanDTOTest::class);
        $this->assertSame('DE89370400440532013000', $obj->account);
    }

    public function testIbanValidatorWithSpaces(): void
    {
        $data = ['account' => 'DE89 3704 0044 0532 0130 00'];
        $obj = $this->mapper->fromArray($data, IbanDTOTest::class);
        $this->assertSame('DE89 3704 0044 0532 0130 00', $obj->account);
    }

    public function testIbanValidatorInvalid(): void
    {
        $this->expectException(ValidationException::class);
        $data = ['account' => 'DE89370400440532013001']; // Invalid checksum
        $this->mapper->fromArray($data, IbanDTOTest::class);
    }

    public function testCreditCardValidatorInvalid(): void
    {
        $this->expectException(ValidationException::class);
        $data = ['card' => 'invalid']; // Invalid format
        $this->mapper->fromArray($data, CreditCardDTOTest::class);
    }

    public function testRegexValidator(): void
    {
        $data = ['code' => 'ABC'];
        $obj = $this->mapper->fromArray($data, RegexDTOTest::class);
        $this->assertSame('ABC', $obj->code);
    }

    public function testRegexValidatorInvalid(): void
    {
        $this->expectException(ValidationException::class);
        $data = ['code' => 'abc'];
        $this->mapper->fromArray($data, RegexDTOTest::class);
    }

    public function testMacAddressValidator(): void
    {
        $data = ['mac' => '00:1A:2B:3C:4D:5E'];
        $obj = $this->mapper->fromArray($data, MacAddressDTOTest::class);
        $this->assertSame('00:1A:2B:3C:4D:5E', $obj->mac);
    }

    public function testMacAddressValidatorDashFormat(): void
    {
        $data = ['mac' => '00-1A-2B-3C-4D-5E'];
        $obj = $this->mapper->fromArray($data, MacAddressDTOTest::class);
        $this->assertSame('00-1A-2B-3C-4D-5E', $obj->mac);
    }

    public function testMacAddressValidatorDotFormat(): void
    {
        $data = ['mac' => '001A.2B3C.4D5E'];
        $obj = $this->mapper->fromArray($data, MacAddressDTOTest::class);
        $this->assertSame('001A.2B3C.4D5E', $obj->mac);
    }

    public function testMacAddressValidatorInvalid(): void
    {
        $this->expectException(ValidationException::class);
        $data = ['mac' => 'invalid-mac'];
        $this->mapper->fromArray($data, MacAddressDTOTest::class);
    }
}

class UuidDTOTest
{
    public function __construct(
        #[Uuid]
        public string $id
    ) {
    }
}

class UuidV4DTOTest
{
    public function __construct(
        #[Uuid(version: 4)]
        public string $id
    ) {
    }
}

class IbanDTOTest
{
    public function __construct(
        #[Iban]
        public string $account
    ) {
    }
}

class CreditCardDTOTest
{
    public function __construct(
        #[CreditCard]
        public string $card
    ) {
    }
}

class RegexDTOTest
{
    public function __construct(
        #[Regex('/^[A-Z]{3}$/')]
        public string $code
    ) {
    }
}

class MacAddressDTOTest
{
    public function __construct(
        #[MacAddress]
        public string $mac
    ) {
    }
}
