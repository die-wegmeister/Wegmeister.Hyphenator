<?php
/**
 * Hyphenation service to handle the hyphenation of texts.
 *
 * This file is part of the Flow Package "Wegmeister.Hyphenator".
 * This service is a Neos-Integration of the Org_Heigl_Hyphenator,
 * which is Word-Hyphenation for PHP based on the TeX-Hyphenation algorithm.
 *
 * PHP version 7
 *
 * @category Hyphenator
 * @package  Wegmeister\Hyphenator
 * @author   Benjamin Klix <benjamin.klix@die-wegmeister.com>
 * @license  https://github.com/die-wegmeister/Wegmeister.Hyphenator/blob/master/LICENSE GPL-3.0-or-later
 * @link     https://github.com/die-wegmeister/Wegmeister.Hyphenator
 */
namespace Wegmeister\Hyphenator\Service;

/**
 * This script belongs to the Flow Package "Wegmeister.Hyphenator".
 * This service handles the hyphenation of texts.
 *
 * wegmeister/hyphenator 1.1.0
 * Neos-Integration of the phpHyphenator with some enhancements on
 * the pattern-converter by Benjamin Klix
 */

use Neos\Flow\Annotations as Flow;
use Wegmeister\Hyphenator\Domain\Repository\DictionaryRepository;
use Org\Heigl\Hyphenator;

/**
 * @Flow\Scope("singleton")
 */
class HyphenationService
{
    /**
     * Localization Service.
     *
     * @Flow\Inject
     * @var \Neos\Flow\I18n\Service
     */
    protected $localizationService;

    /**
     * The repository for the dictionary.
     *
     * @Flow\Inject
     * @var DictionaryRepository
     */
    protected $dictionaryRepository;

    /**
     * Settings injected from the yaml config.
     *
     * @var array
     */
    protected $settings;

    /**
     * Dictionary for special words not hyphenated correctly.
     *
     * @var array
     */
    protected $dictionary;

    /**
     * Array of (cached) hyphenators.
     *
     * @var array
     */
    protected $hyphenators;


    /**
     * Inject the settings
     *
     * @param array $settings Settings to inject from yaml config.
     *
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

        $this->dictionary = ['mul' => []];
        if (file_exists($this->settings['dictionary'])) {
            $entries = file($this->settings['dictionary']);
            foreach ($entries as $entry) {
                $entry = trim($entry);
                if (strlen($entry) > 0) {
                    $this->dictionary['mul'][str_replace('/', '', mb_strtolower($entry))] = str_replace('/', $this->settings['hyphen'], $entry);
                }
            }
        }
    }


    /**
     * Add text hyphenation.
     *
     * @param string $text   The text to hyphenate.
     * @param string $locale The current locale.
     *
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

        $this->initHyphenator($locale);
        $this->initLocaleDictionary($locale);

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
                if ($char === '"' && !$inAttr2) {
                    $inAttr1 = !$inAttr1;
                }
                if ($char === "'" && !$inAttr1) {
                    $inAttr2 = !$inAttr2;
                }
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
                    } elseif ($tagJump === '' || mb_strtolower(mb_substr($tag, -mb_strlen($tagName) - 3)) === '</' . $tagJump . '>') {
                        $output[] = $tag;
                        $tag = '';
                        $tagJump = '';
                    }
                }
                if ($tag === '' && $char !== '<' && $char !== '>') {
                    $output[] = $char;
                }
            }
        }

        $text = implode($output);
        return trim($text);
    }

    /**
     * Word hyphenation.
     *
     * @param string $words  The word string that should be hyphenated.
     * @param string $locale The current locale.
     *
     * @return string
     */
    protected function wordHyphenation($words, $locale)
    {
        $words = explode(' ', $words);
        foreach ($words as &$word) {
            $firstLetterIsUppercase = $this->firstLetterIsUppercase($word);
            $lowercaseWord = mb_strtolower($word);
            if (isset($this->dictionary[$locale][$lowercaseWord])) {
                $word = $this->dictionary[$locale][$lowercaseWord];
            } elseif (isset($this->dictionary['mul'][$lowercaseWord])) {
                $word = $this->dictionary['mul'][$lowercaseWord];
            }
            if ($firstLetterIsUppercase) {
                $word = $this->firstLetterToUppercase($word);
            }
        }
        $word = implode(' ', $words);

        $hyphenatedWord = $this->hyphenators[$locale]->hyphenate($word);

        /**
         * TODO: Add caching?
         */
        return $hyphenatedWord;
    }

    /**
     * Check, if the first letter of the word is an uppercase letter.
     *
     * @param string $word The word to check
     *
     * @return bool
     */
    protected function firstLetterIsUppercase(string $word)
    {
        $chr = mb_substr($str, 0, 1, 'UTF-8');
        return mb_strtolower($chr, 'UTF-8') != $chr;
    }

    /**
     * Transform first letter of the word to an uppercase letter.
     *
     * @param string $word The word to transform
     *
     * @return bool
     */
    protected function firstLetterToUppercase(string $word)
    {
        $firstChar = mb_substr($string, 0, 1, 'UTF-8');
        $nextChars = mb_substr($string, 1, null, 'UTF-8');
        return mb_strtoupper($firstChar, 'UTF-8') . $nextChars;
    }


    /**
     * Initialize the hyphenator for a given locale.
     *
     * @param string $locale The current locale to use.
     *
     * @return void
     */
    protected function initHyphenator(string $locale)
    {
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
    }

    /**
     * Initialize the dictionary for the given locale.
     *
     * @param string $locale The current locale to use.
     *
     * @return void
     */
    protected function initLocaleDictionary(string $locale)
    {
        if (!isset($this->dictionary[$locale])) {
            $this->dictionary[$locale] = [];
            $entries = $this->dictionaryRepository->findByLocale($locale);
            foreach ($entries as $entry) {
                $this->dictionary[$locale][mb_strtolower($entry->getRealWord())] = str_replace('/', $this->settings['hyphen'], $entry->getWord());
            }
        }
    }
}
