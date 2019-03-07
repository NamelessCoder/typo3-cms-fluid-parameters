<?php
declare(strict_types=1);

namespace NamelessCoder\FluidParameters;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\ArgumentDefinition;

class ValidationProxy extends AbstractViewHelper
{
    /**
     * @param ArgumentDefinition[] $argumentDefinitions
     */
    public function setArgumentDefinitions(array $argumentDefinitions): void
    {
        $this->argumentDefinitions = $argumentDefinitions;
    }

    public function prepareArguments()
    {
        return $this->argumentDefinitions;
    }
}