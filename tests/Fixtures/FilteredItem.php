<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use Pocta\DataMapper\Attributes\Filters\PregReplaceFilter as PregReplace;
use Pocta\DataMapper\Attributes\Filters\StringToLowerFilter as StringToLower;
use Pocta\DataMapper\Attributes\Filters\StringToUpperFilter as StringToUpper;
use Pocta\DataMapper\Attributes\Filters\StringTrimFilter as StringTrim;
use Pocta\DataMapper\Attributes\Filters\StripNewlinesFilter as StripNewlines;
use Pocta\DataMapper\Attributes\Filters\StripTagsFilter as StripTags;
use Pocta\DataMapper\Attributes\Filters\ToFloatFilter as ToFloat;
use Pocta\DataMapper\Attributes\Filters\ToIntFilter as ToInt;
use Pocta\DataMapper\Attributes\Filters\ToNullFilter as ToNull;
use Pocta\DataMapper\Attributes\Filters\ToStringFilter as ToString;

class FilteredItem
{
    #[StringTrim]
    #[StringToLower]
    public string $name;

    #[StringToUpper]
    public string $code;

    #[StripNewlines]
    public string $oneLine;

    #[StripTags]
    public string $text;

    #[StripTags('<b>')]
    public string $richText;

    #[ToInt]
    public string $intish;

    #[ToFloat]
    public string $floatish;

    #[ToNull]
    public ?string $optional = null;

    #[PregReplace('/\s+/', '-')]
    public string $sluggy;

    #[ToString(trim: true)]
    public string $stringified;
}
