<?php

declare(strict_types=1);

namespace App\Service\Engine;

use Exception;

readonly class EngineProcessor
{
    /**
     * @param iterable $engines
     */
    public function __construct(
        private Iterable $engines = []
    ) {
    }

    /**
     * @return array
     */
    public function getEngines(): array
    {
        $engines = [];
        /** @var EngineInterface $engine */
        foreach ($this->engines as $engine) {
            $engines[$engine->getCode()] = $engine;
        }
        return $engines;
    }

    /**
     * @param string $code
     * @return EngineInterface
     * @throws Exception
     */
    public function getEngineByCode(string $code): EngineInterface
    {
        foreach ($this->engines as $engine) {
            if ($engine->getCode() === $code) {
                return $engine;
            }
        }

        throw new Exception(sprintf("No such engine %s", $code));
    }
}
