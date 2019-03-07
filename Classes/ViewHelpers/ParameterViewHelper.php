<?php
declare(strict_types=1);

namespace NamelessCoder\FluidParameters\ViewHelpers;

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\Variables\VariableProviderInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\ArgumentDefinition;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\ParserRuntimeOnly;

class ParameterViewHelper extends AbstractViewHelper
{
    use ParserRuntimeOnly;

    public function initializeArguments()
    {
        $this->registerArgument('name', 'string', 'Name of parameter', true);
        $this->registerArgument('type', 'string', 'Type of parameter (string, int, bool, class name, ...)', true);
        $this->registerArgument('description', 'string', 'Description of parameter', true);
        $this->registerArgument('required', 'bool', 'Required parameter?', false, false);
        $this->registerArgument('default', 'mixed', 'Default value of parameter if not passed (value NULL)', false);
        $this->registerArgument('forSection', 'string', 'If parameter is for a section, put the name of the section here (even if nested inside section)');
    }

    public static function getParametersForTemplateAndPossiblySection(string $templateIdentifier, ?string $section): array
    {
        $cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('fluid_parameters');
        $cacheIdentifier = static::createCacheIdentifier($templateIdentifier, $section);
        return $cache->get($cacheIdentifier) ?: [];
    }

    protected static function createCacheIdentifier(string $templateIdentifier, ?string $section): string
    {
        if ($section) {
            return sha1($templateIdentifier) . '_parameters_section__' . $section;
        }
        return sha1($templateIdentifier) . '_parameters';
    }

    public static function postParseEvent(ViewHelperNode $node, array $arguments, VariableProviderInterface $variableContainer)
    {
        /** @var RenderingContextInterface $context */
        $context = ObjectAccess::getProperty($node->getUninitializedViewHelper(), 'renderingContext', true);

        $templateIdentifier = (string)$context->getViewHelperVariableContainer()->get(RenderViewHelper::class, 'renderingInTemplate');

        if (empty($templateIdentifier)) {
            throw new Exception('Usage of f:parameter outside of Partial templates is not supported with EXT:fluid_parameters', 1551978322);
        }

        $parameterName = $arguments['name']->evaluate($context);
        $definition = new ArgumentDefinition(
            $parameterName,
            $arguments['type']->evaluate($context),
            $arguments['description']->evaluate($context),
            ($arguments['required'] ? (bool)($arguments['required']->evaluate($context) ?? false) : false),
            ($arguments['default'] ? $arguments['default']->evaluate($context) : null)
        );

        static::addParameterForTemplateAndPossiblySection(
            $templateIdentifier,
            $arguments['forSection'] ? $arguments['forSection']->evaluate($context) : null,
            $definition
        );
    }

    protected static function addParameterForTemplateAndPossiblySection(string $templateIdentifier, ?string $section, ArgumentDefinition $parameter)
    {
        $cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('fluid_parameters');
        $cacheIdentifier = static::createCacheIdentifier($templateIdentifier, $section);
        $cachedParameterList = $cache->get($cacheIdentifier) ?: [];
        $cachedParameterList[$parameter->getName()] = $parameter;
        $cache->set($cacheIdentifier, $cachedParameterList);
    }
}