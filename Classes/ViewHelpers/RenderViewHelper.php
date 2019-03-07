<?php
declare(strict_types=1);

namespace NamelessCoder\FluidParameters\ViewHelpers;

use NamelessCoder\FluidParameters\ValidationProxy;
use TYPO3Fluid\Fluid\Core\Parser\Exception;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

class RenderViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\RenderViewHelper
{
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $templateIdentifier = null;
        if (!empty($arguments['partial'])) {
            // A partial, with or without section as well, is being rendered. Extract the parameters required by this
            // partial template (if any exist) and validate them + pad with default values before passing the result
            // to the parent rendering method.
            $templateIdentifier = $renderingContext->getTemplatePaths()->getPartialIdentifier($arguments['partial']);
            $arguments['arguments'] = static::validateAndPadArguments($templateIdentifier, $arguments['section'], $arguments['arguments']);
        } elseif (!empty($arguments['section'])) {
            // A section in the current template is being rendered. Extract the parameters required by that section in
            // the current parsed template and validate them + pad with defaults before continuing.
            $view = $renderingContext->getViewHelperVariableContainer()->getView();
            $getCurrentParsedTemplateMethod = new \ReflectionMethod($view, 'getCurrentParsedTemplate');
            $getCurrentParsedTemplateMethod->setAccessible(true);
            $templateIdentifier = $getCurrentParsedTemplateMethod->invoke($view)->getIdentifier();
            if (!empty($templateIdentifier)) {

                $arguments['arguments'] = static::validateAndPadArguments($templateIdentifier, $arguments['section'], $arguments['arguments']);
            }
        }

        $renderingContext->getViewHelperVariableContainer()->addOrUpdate(
            RenderViewHelper::class,
            'renderingInTemplate',
            $templateIdentifier
        );

        return parent::renderStatic($arguments, $renderChildrenClosure, $renderingContext);
    }

    protected static function validateAndPadArguments(string $templateIdentifier, ?string $section, array $arguments): array
    {
        $parameters = ParameterViewHelper::getParametersForTemplateAndPossiblySection($templateIdentifier, $section);
        if (!empty($parameters)) {

            // Validate presence
            foreach ($parameters as $parameterName => $parameterDefinition) {
                if (!isset($arguments[$parameterName]) && $parameterDefinition->isRequired()) {
                    throw new Exception('Required argument "' . $parameterName . '" was not supplied.', 1237823699);
                }
            }

            try {
                // Validate type
                $validationProxy = new ValidationProxy();
                $validationProxy->setArgumentDefinitions($parameters);
                $validationProxy->setArguments($arguments);
                $validationProxy->validateArguments();
            } catch (\InvalidArgumentException $error) {
                throw new Exception('Validation error while rendering parameterised section/partial in ' . $templateIdentifier . ': ' . $error->getMessage(), $error->getCode(), $error);
            }
        }

        return $arguments;
    }
}