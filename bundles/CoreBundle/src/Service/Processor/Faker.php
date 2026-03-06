<?php

declare(strict_types=1);

namespace DbManager\CoreBundle\Service\Processor;

use Faker\Factory;
use Faker\Generator;

class Faker
{
    /**
     * @var Generator|null
     */
    protected ?Generator $faker = null;

    /**
     * @param FakerUnique $fakerUnique
     */
    public function __construct(
        private readonly FakerUnique $fakerUnique
    ) {
        $this->faker = Factory::create();
    }

    /**
     * @param string $method
     * @param array $options
     * @param int $count
     * @param bool $unique
     * @param int|null $maxLength
     * @param string|null $columnType
     * @return array
     */
    public function generateFakeCollection(
        string $method,
        array $options = [],
        int $count = 1,
        bool $unique = false,
        ?int $maxLength = null,
        ?string $columnType = null
    ): array {
        $collection = [];
        for ($i = 0; $i < $count; $i++) {
            $generatedValue = $this->generateFake($method, $options, $maxLength, $columnType);
            if ($unique && in_array($generatedValue, $collection)) {
                $generatedValue = $this->fakerUnique->makeUnique($generatedValue, $method, $collection);
            }
            $collection[] = $generatedValue;
        }

        return $collection;
    }

    /**
     * @param string $method
     * @param array $options
     * @param int|null $maxLength
     * @param string|null $columnType
     * @return string
     */
    public function generateFake(string $method, array $options, ?int $maxLength = null, ?string $columnType = null): string
    {
        // Spread positionally so associative option keys (date1/date2, int1/int2, etc.) do not
        // interfere with Faker's own parameter names.
        $result = $this->faker->{$method}(...array_values($options));

        if ($result instanceof \DateTimeInterface) {
            $format = ($columnType === 'date') ? 'Y-m-d' : 'Y-m-d H:i:s';
            return $result->format($format);
        }

        $value = (string)$result;
        if ($maxLength) {
            $value = mb_substr($value, 0, $maxLength);
        }

        return $value;
    }
}
