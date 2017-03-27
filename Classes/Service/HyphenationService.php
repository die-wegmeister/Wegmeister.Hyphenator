<?php
namespace Wegmeister\Hyphenator\Service;

/**
 * This script belongs to the TYPO3 Flow Package "Wegmeister.Hyphenator".
 * This service handles the hyphenation of texts.
 *
 * wegmeister/hyphenator 1.1.0
 * Neos-Integration of the phpHyphenator with some enhancements on
 * the pattern-converter by Benjamin Klix
 */

use Neos\Flow\Annotations as Flow;
use \Org\Heigl\Hyphenator;

/**
 * @Flow\Scope("singleton")
 */
class HyphenationService
{
    /**
     * @Flow\Inject
     * @var \Neos\Flow\I18n\Service
     */
    protected $localizationService;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @var array
     */
    protected $dictionary;

    /**
     * @var array
     */
    protected $patterns;

    /**
     * @var array
     */
    protected $hyphenators;


    /**
     * Inject the settings
     *
     * @param array $settings
     * @return void
     */
    public function injectSettings(array $settings)
    {
        $excludeTags = [];
        foreach ($settings['excludeTags'] as $tag => $exclude) {
            if ($exclude === true) {
                $excludeTags[] = $tag;
            }
        }
        $settings['excludeTags'] = $excludeTags;
        $this->settings = $settings;

        $this->dictionary = [];
        if (file_exists($this->settings['dictionary'])) {
            $entries = file($this->settings['dictionary']);
            foreach ($entries as $entry) {
                $entry = trim($entry);
                if (strlen($entry) > 0) {
                    $this->dictionary[str_replace('/', '', mb_strtolower($entry))] = str_replace('/', $this->settings['hyphen'], $entry);
                }
            }
        }

        $this->patterns = [];
    }


    /**
     * Add text hyphenation.
     *
     * @param string $text
     * @param string $locale
     * @return string
     */
    public function hyphenate($text, $locale = null)
    {
        if (!is_string($text) && !(is_object($text) && method_exists($text, '__toString'))) {
            return $text;
        }

        if ($locale === null || $locale === '') {
            $locale = $this->localizationService->getConfiguration()->getCurrentLocale()->getLanguage();
        }
        if (isset($this->settings['locales'][$locale])) {
            $locale = $this->settings['locales'][$locale];
        }

        if (!isset($this->hyphenators[$locale])) {
            $options = new Hyphenator\Options();
            $options
                ->setHyphen($this->settings['hyphen'])
                ->setDefaultLocale($locale)
                ->setLeftMin($this->settings['leftmin'])
                ->setRightMin($this->settings['rightmin'])
                ->setWordMin($this->settings['shortestPattern'])
                ->setFilters('Simple')
                ->setTokenizers('Whitespace,Punctuation');

            $this->hyphenators[$locale] = new Hyphenator\Hyphenator();
            $this->hyphenators[$locale]->setOptions($options);
        }

        $word = '';
        $tag  = '';
        $tagJump = '';
        $output = [];

        $text .= ' ';
        $inAttr1 = false;
        $inAttr2 = false;

        for ($i = 0; $i < mb_strlen($text); $i++) {
            $char = mb_substr($text, $i, 1);
            if ($tag !== '') {
                if ($char === '"' && !$inAttr2) $inAttr1 = !$inAttr1;
                if ($char === "'" && !$inAttr1) $inAttr2 = !$inAttr2;
            }
            if (mb_strpos($this->settings['wordBoundaries'], $char) === false && $tag === '') {
                $word .= $char;
            } else {
                if ($word !== '') {
                    $hyphenatedWord = $this->wordHyphenation($word, $locale);
                    if (is_array($hyphenatedWord)) {
                        $output[] = $hyphenatedWord[0];
                    } else {
                        $output[] = $hyphenatedWord;
                    }
                    $word = '';
                }
                if ($tag !== '' || $char === '<') {
                    $tag .= $char;
                }
                if ($tag !== '' && $char === '>' && !$inAttr1 && !$inAttr2) {
                    $spacePos = mb_strpos($tag, ' ');
                    $bracketPos = mb_strpos($tag, '>');
                    $tagName = ($spacePos && $spacePos < $bracketPos) ? mb_substr($tag, 1, $spacePos - 1) : mb_substr($tag, 1, $bracketPos - 1);
                    if ($tagJump === '' && in_array(mb_strtolower($tagName), $this->settings['excludeTags'])) {
                        $tagJump = mb_strtolower($tagName);
                    } else if ($tagJump === '' || mb_strtolower(mb_substr($tag, -mb_strlen($tagName) - 3)) === '</' . $tagJump . '>') {
                        $output[] = $tag;
                        $tag = '';
                        $tagJump = '';
                    }
                }
                if ($tag === '' && $char !== '<' && $char !== '>') $output[] = $char;
            }
        }

        $text = implode($output);
        return trim($text);
    }

    /**
     * Word hyphenation.
     *
     * @param string $word
     * @param string $locale
     * @return string
     */
    protected function wordHyphenation($word, $locale)
    {
        if (mb_strpos($word, $this->settings['hyphen']) !== false
          || mb_strpos($word, $this->settings['altHyphen']) !== false) {
            return $word;
        }
        if (isset($this->dictionary[mb_strtolower($word)])) {
            return $this->dictionary[mb_strtolower($word)];
        }

        $hyphenatedWord = $this->hyphenators[$locale]->hyphenate($word);

        /**
         * @TODO Add caching?
         */
        return $hyphenatedWord;
    }
}
