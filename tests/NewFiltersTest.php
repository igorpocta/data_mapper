<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Mapper;
use Pocta\DataMapper\Attributes\Filters\NormalizeEmailFilter;
use Pocta\DataMapper\Attributes\Filters\NormalizePhoneFilter;
use Pocta\DataMapper\Attributes\Filters\DefaultValueFilter;
use Pocta\DataMapper\Attributes\Filters\SanitizeHtmlFilter;
use Pocta\DataMapper\Attributes\Filters\ReplaceFilter;
use Pocta\DataMapper\Attributes\Filters\CoalesceFilter;

class NewFiltersTest extends TestCase
{
    private Mapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new Mapper();
    }

    public function testNormalizeEmailFilter(): void
    {
        $data = ['email' => '  John.Doe@EXAMPLE.COM  '];
        $obj = $this->mapper->fromArray($data, EmailDTO::class);
        $this->assertSame('john.doe@example.com', $obj->email);
    }

    public function testNormalizePhoneFilter(): void
    {
        $data = ['phone' => '+1 (555) 123-4567'];
        $obj = $this->mapper->fromArray($data, PhoneDTO::class);
        $this->assertSame('15551234567', $obj->phone);
    }

    public function testNormalizePhoneFilterKeepPlus(): void
    {
        $data = ['phone' => '+1 (555) 123-4567'];
        $obj = $this->mapper->fromArray($data, PhoneWithPlusDTO::class);
        $this->assertSame('+15551234567', $obj->phone);
    }

    public function testDefaultValueFilter(): void
    {
        $data = ['status' => null];
        $obj = $this->mapper->fromArray($data, StatusDTO::class);
        $this->assertSame('Unknown', $obj->status);
    }

    public function testDefaultValueFilterWithValue(): void
    {
        $data = ['status' => 'Active'];
        $obj = $this->mapper->fromArray($data, StatusDTO::class);
        $this->assertSame('Active', $obj->status);
    }

    public function testDefaultValueFilterReplaceEmpty(): void
    {
        $data = ['status' => ''];
        $obj = $this->mapper->fromArray($data, StatusWithEmptyDTO::class);
        $this->assertSame('N/A', $obj->status);
    }

    public function testSanitizeHtmlFilter(): void
    {
        $data = ['description' => '<script>alert("XSS")</script>Hello <b>World</b>'];
        $obj = $this->mapper->fromArray($data, DescriptionDTO::class);
        $this->assertSame('alert("XSS")Hello World', $obj->description);
    }

    public function testSanitizeHtmlFilterWithAllowedTags(): void
    {
        $data = ['content' => '<p>Hello <b>World</b> <script>alert("XSS")</script></p>'];
        $obj = $this->mapper->fromArray($data, ContentDTO::class);
        $this->assertSame('<p>Hello <b>World</b> alert("XSS")</p>', $obj->content);
    }

    public function testReplaceFilter(): void
    {
        $data = ['slug' => 'hello_world_test'];
        $obj = $this->mapper->fromArray($data, SlugDTO::class);
        $this->assertSame('hello-world-test', $obj->slug);
    }

    public function testReplaceFilterRegex(): void
    {
        $data = ['code' => 'ABC-123-XYZ'];
        $obj = $this->mapper->fromArray($data, CodeDTO::class);
        $this->assertSame('ABC123XYZ', $obj->code);
    }

    public function testCoalesceFilter(): void
    {
        $data = ['optional' => null];
        $obj = $this->mapper->fromArray($data, OptionalDTO::class);
        $this->assertSame('Default', $obj->optional);
    }

    public function testCoalesceFilterWithValue(): void
    {
        $data = ['optional' => 'Value'];
        $obj = $this->mapper->fromArray($data, OptionalDTO::class);
        $this->assertSame('Value', $obj->optional);
    }
}

class EmailDTO
{
    public function __construct(
        #[NormalizeEmailFilter]
        public string $email
    ) {
    }
}

class PhoneDTO
{
    public function __construct(
        #[NormalizePhoneFilter]
        public string $phone
    ) {
    }
}

class PhoneWithPlusDTO
{
    public function __construct(
        #[NormalizePhoneFilter(keepPlus: true)]
        public string $phone
    ) {
    }
}

class StatusDTO
{
    public function __construct(
        #[DefaultValueFilter('Unknown')]
        public ?string $status
    ) {
    }
}

class StatusWithEmptyDTO
{
    public function __construct(
        #[DefaultValueFilter('N/A', replaceEmpty: true)]
        public string $status
    ) {
    }
}

class DescriptionDTO
{
    public function __construct(
        #[SanitizeHtmlFilter]
        public string $description
    ) {
    }
}

class ContentDTO
{
    public function __construct(
        #[SanitizeHtmlFilter('<p><b><i>')]
        public string $content
    ) {
    }
}

class SlugDTO
{
    public function __construct(
        #[ReplaceFilter('_', '-')]
        public string $slug
    ) {
    }
}

class CodeDTO
{
    public function __construct(
        #[ReplaceFilter('/[^A-Z0-9]/', '', useRegex: true)]
        public string $code
    ) {
    }
}

class OptionalDTO
{
    public function __construct(
        #[CoalesceFilter('Default')]
        public ?string $optional
    ) {
    }
}
