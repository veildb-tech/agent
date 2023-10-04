<?php

declare(strict_types=1);

namespace App\Service\PublicCommand;

use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Service\InputOutput;

abstract class AbstractCommand
{
    private ?InputOutput $inputOutput = null;

    /**
     * @var InputInterface
     */
    protected InputInterface $input;

    abstract public function execute(InputInterface $input, OutputInterface $output);

    /**
     * @param InputOutput $inputOutput
     * @return $this
     */
    public function setInputOutput(InputOutput $inputOutput): static
    {
        $this->inputOutput = $inputOutput;
        return $this;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return $this
     */
    protected function initInputOutput(InputInterface $input, OutputInterface $output): static
    {
        $this->inputOutput = new InputOutput($input, $output);
        $this->input = $input;
        return $this;
    }

    /**
     * @return InputOutput
     * @throws Exception
     */
    protected function getInputOutput(): InputOutput
    {
        if ($this->inputOutput === null) {
            throw new Exception("Input&Output hasn't initialized");
        }

        return $this->inputOutput;
    }
}
