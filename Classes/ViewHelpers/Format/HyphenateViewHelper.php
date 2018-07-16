<?php
namespace Wegmeister\Hyphenator\ViewHelpers\Format;

/**
 * This script belongs to the TYPO3 Flow Package "Wegmeister.Hyphenator"
 */

use Neos\Flow\Annotations as Flow;
use Wegmeister\Hyphenator\Service\HyphenationService;
use Neos\Neos\ViewHelpers\Rendering\AbstractRenderingStateViewHelper;
use Neos\FluidAdaptor\Core\ViewHelper\Facets\CompilableInterface;

/**
 * Adds hyphens (&shy;) to the given text.
 *
 * = Examples =
 *
 * <code>
 * <wh:format.hyphenate>Silbentrennung</wh:format.hyphenate>
 * </code>
 * <output>
 * Sil&shy;ben&shy;tren&shy;nung
 * </output>
 *
 * <code>
 * {text -> wh:format.hyphenate(locale: 'de')}
 * </code>
 * <output>
 * Text with hyphens
 * </output>
 */
class HyphenateViewHelper extends AbstractRenderingStateViewHelper implements CompilableInterface
{
    /**
     * @Flow\Inject
     * @var HyphenationService
     */
    protected $hyphenationService;

    /**
     * @see AbstractViewHelper::isOutputEscapingEnabled()
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * @var boolean
     */
    protected $escapeChildren = false;


    /**
    * Adds hyphens (&shy;) to the given text.
    *
    * @param string $value String that should be formatted
    * @param string $locale Locale for use in hyphenator
    * @return mixed
    * @see https://www.liedtke.it/op1a.htm
    * @see https://github.com/mnater/hyphenator (original js-Implementation)
    * @api
    */
    public function render($value = null, $locale = null)
    {
        $context = $this->getNodeContext();
        $renderingMode = $context->getCurrentRenderingMode();

        // Do not use hyphenator in Neos Backend
        if ($renderingMode->isEdit()) {
            return $value;
        }

        if ($value === null) {
            $closure = $this->buildRenderChildrenClosure();
            $value = $closure();
        }

        return $this->hyphenationService->hyphenate($value, $locale);
    }
}
