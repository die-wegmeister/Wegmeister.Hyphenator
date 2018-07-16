<?php
namespace Wegmeister\Hyphenator\Fusion;

/**
 * This script belongs to the TYPO3 Flow Package "Wegmeister.Hyphenator"
 */

use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Exception;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Fusion\FusionObjects\AbstractFusionObject;

/**
 * A Fusion Object that adds hyphens to texts
 *
 * Usage::
 *
 *   someTextProperty.@process.1 = Wegmeister.Hyphenator:Hyphenate
 */
class HyphenateImplementation extends AbstractFusionObject
{
    /**
     * @Flow\Inject
     * @var \Wegmeister\Hyphenator\Service\HyphenationService
     */
    protected $hyphenationService;


    /**
     * Add hyphens to the given text
     *
     * If the workspace of the current node context is not live,
     * no replacement will be done unless forceConversion is set.
     *
     * @return string
     * @throws Exception
     */
    public function evaluate()
    {
        $text = $this->fusionValue('value');
        $locale = $this->fusionValue('locale');

        if ($text === '' || $text === null) {
            return '';
        }

        $node = $this->fusionValue('node');

        if (!$node instanceof NodeInterface) {
            throw new Exception(sprintf('The current node must be an instance of NodeInterface, given: "%s".', gettype($text)), 1382624087);
        }

        if ($node->getContext()->getWorkspace()->getName() !== 'live' && !($this->fusionValue('forceConversion'))) {
            return $text;
        }

        $processedContent = $this->hyphenationService->hyphenate($text, $locale);

        return $processedContent;
    }
}
