<?php
declare(strict_types=1);

namespace TRAW\NewsArchiver\Utility;

use BackedEnum;

class ValidationUtility
{
    public function isPositiveInteger(mixed $value): bool {
        return (
            (is_int($value) || is_string($value))
            && ctype_digit((string)$value)
            && (int)$value > 0
        );
    }

    public function isNonNegativeInteger(mixed $value): bool
    {
        return (
            (is_int($value) || is_string($value))
            && ctype_digit((string)$value)
        );
    }

    public function isIntegerList(mixed $value): bool
    {
        return $value === '' || (is_string($value) && preg_match('/^\d+(?:,\d+)*$/', $value) === 1);
    }

    /**
     *  value is an instance of enum class
     *  or can be converted to a valid backed enum case.
     *
     * @template NewsArchiverEnum of BackedEnum
     *
     * @param mixed  $value
     * @param class-string<NewsArchiverEnum> $enumClass
     *
     * @return bool
     */
    public function isEnum(mixed $value, string $enumClass): bool {
        return is_a($value, $enumClass)
            || $enumClass::tryFrom((string)$value) !== null;
    }
}
