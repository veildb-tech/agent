<?php

declare(strict_types=1);

namespace App\Service\Platform;

use \Exception;

readonly class PlatformProcessor
{
    /**
     * @param iterable $platforms
     */
    public function __construct(
        private Iterable $platforms = []
    ) {
    }

    /**
     * @param string $engine
     * @return array
     */
    public function getPlatforms(string $engine): array
    {
        $platforms = [];
        /** @var PlatformInterface $platform */
        foreach ($this->platforms as $platform) {
            if ($platform->supports($engine)) {
                $platforms[$platform->getCode()] = $platform;
            }
        }
        return $platforms;
    }

    /**
     * @param string $code
     * @return PlatformInterface
     * @throws Exception
     */
    public function getPlatformByCode(string $code): PlatformInterface
    {
        foreach ($this->platforms as $platform) {
            if ($platform->getCode() === $code) {
                return $platform;
            }
        }

        throw new Exception(sprintf("No such engine %s", $code));
    }
}
