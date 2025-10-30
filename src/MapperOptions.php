<?php

declare(strict_types=1);

namespace Pocta\DataMapper;

class MapperOptions
{
    /**
     * @param bool $autoValidate Automatically validate objects after denormalization
     * @param bool $strictMode Throw validation error if unknown keys are present in input data
     * @param bool $throwOnMissingData Throw exception when required data is missing (default: true)
     * @param bool $skipNullValues Skip null values during normalization (don't include them in output)
     * @param bool $preserveNumericStrings Keep numeric strings as strings instead of converting to numbers
     */
    public function __construct(
        public readonly bool $autoValidate = false,
        public readonly bool $strictMode = false,
        public readonly bool $throwOnMissingData = true,
        public readonly bool $skipNullValues = false,
        public readonly bool $preserveNumericStrings = false,
    ) {
    }

    /**
     * Create options with auto-validation enabled
     */
    public static function withAutoValidation(): self
    {
        return new self(autoValidate: true);
    }

    /**
     * Create options with strict mode enabled
     */
    public static function withStrictMode(): self
    {
        return new self(strictMode: true);
    }

    /**
     * Create options with both auto-validation and strict mode enabled
     */
    public static function strict(): self
    {
        return new self(autoValidate: true, strictMode: true);
    }

    /**
     * Create options for development (strict + auto-validation + throw on missing)
     */
    public static function development(): self
    {
        return new self(
            autoValidate: true,
            strictMode: true,
            throwOnMissingData: true
        );
    }

    /**
     * Create options for production (lenient, no auto-validation)
     */
    public static function production(): self
    {
        return new self(
            autoValidate: false,
            strictMode: false,
            throwOnMissingData: true
        );
    }

    /**
     * Create a modified copy with updated values
     */
    public function with(
        ?bool $autoValidate = null,
        ?bool $strictMode = null,
        ?bool $throwOnMissingData = null,
        ?bool $skipNullValues = null,
        ?bool $preserveNumericStrings = null,
    ): self {
        return new self(
            autoValidate: $autoValidate ?? $this->autoValidate,
            strictMode: $strictMode ?? $this->strictMode,
            throwOnMissingData: $throwOnMissingData ?? $this->throwOnMissingData,
            skipNullValues: $skipNullValues ?? $this->skipNullValues,
            preserveNumericStrings: $preserveNumericStrings ?? $this->preserveNumericStrings,
        );
    }
}
