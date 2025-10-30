<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;
use InvalidArgumentException;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class ArrayCastFilter implements FilterInterface
{
    /** @var 'int'|'float'|'string'|'bool' */
    public readonly string $type;

    public function __construct(string $type, public readonly bool $recursive = false)
    {
        $allowed = ['int', 'float', 'string', 'bool'];
        if (!in_array($type, $allowed, true)) {
            throw new InvalidArgumentException("ArrayCast type must be one of: " . implode(', ', $allowed));
        }
        $this->type = $type;
    }

    public function apply(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }
        return $this->castArray($value);
    }

    /**
     * @param array<mixed> $arr
     * @return array<mixed>
     */
    private function castArray(array $arr): array
    {
        $out = [];
        foreach ($arr as $k => $v) {
            if ($this->recursive && is_array($v)) {
                $out[$k] = $this->castArray($v);
                continue;
            }
            $out[$k] = match ($this->type) {
                'int' => is_numeric($v) ? (int) $v : $v,
                'float' => is_numeric($v) ? (float) $v : $v,
                'string' => is_scalar($v) ? (string) $v : $v,
                'bool' => is_bool($v) ? $v : (is_numeric($v) ? (bool) $v : (is_string($v) ? in_array(strtolower(trim($v)), ['1','true','yes','y','on'], true) : $v)),
            };
        }
        return $out;
    }
}
