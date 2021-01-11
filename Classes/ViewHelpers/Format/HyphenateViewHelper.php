<?php
namespace Wegmeister\Hyphenator\ViewHelpers\Format;

/**
 * This script belongs to the TYPO3 Flow Package "Wegmeister.Hyphenator"
 */

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\ViewHelpers\Rendering\AbstractRenderingStateViewHelper;
use Wegmeister\Hyphenator\Service\HyphenationService;

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
class HyphenateViewHelper extends AbstractRenderingStateViewHelper
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
     * Initialize the arguments.
     *
     * @return void
     * @throws \Neos\FluidAdaptor\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('value', 'string', 'The incoming data to convert, or NULL if VH children should be used', false, null);
        $this->registerArgument('locale', 'string', 'The locale, that should be used.', false, null);
        $this->registerArgument('force', 'boolean', 'Force conversion.', false, false);
        $this->registerArgument('node', NodeInterface::class, 'Node');
    }

    /**
     * Adds hyphens (&shy;) to the given text.
     *
     * @param string $value  String that should be formatted
     * @param string $locale Locale for use in hyphenator
     * @param bool   $force  Force to run the hyphenator, even in backend.
     *
     * @return mixed
     *
     * @see https://github.com/heiglandreas/Org_Heigl_Hyphenator (Original PHP implementation)
     * @api
     */
    public function render()
    {
        $value = $this->arguments['value'];

        if ($value === null) {
            $value = $this->renderChildren();
        }

        $context = $this->getNodeContext($this->arguments['node']);
        $renderingMode = $context->getCurrentRenderingMode();

        // Do not use hyphenator in Neos Backend
        if (!$$this->arguments['force'] && $renderingMode->isEdit()) {
            return $value;
        }

        return $this->hyphenationService->hyphenate($value, $locale);
    }
}
