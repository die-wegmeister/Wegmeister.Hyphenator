<?php
namespace Wegmeister\Hyphenator\ViewHelpers\Format;

/**
 * This script belongs to the TYPO3 Flow Package "Wegmeister.Hyphenator"
 *
 * wegmeister/hyphenator 1.0.0
 * Neos-Integration of the phpHyphenator with some enhancements on
 * the pattern-converter by Benjamin Klix
 *
 * phpHyphenator 1.6.1
 * Enhanced by Erik Krause
 *
 * based on
 *
 * phpHyphenator 1.6
 * Enhanced by Liedtke.IT Jens Liedtke
 * PHP version of the JavaScript Hyphenator version 3.1.0 by
 * Mathias Nater, <a href = "mailto:mathias@mnn.ch">mathias@mnn.ch</a>
 *
 * based on
 *
 * phpHyphenator 1.5
 * Developed by yellowgreen designbüro
 * PHP version of the JavaScript Hyphenator 1.0 (Beta) by Matthias Nater
 *
 * Licensed under Creative Commons Attribution-Share Alike 2.5 Switzerland
 * http://creativecommons.org/licenses/by-sa/2.5/ch/deed.en
 *
 * Associated pages:
 * http://www.dokuwiki.org/plugin:hyphenation
 *
 * Special thanks to:
 * Dave Gööck (webvariants.de)
 * Markus Birth (birth-online.de)
 * Nico Wenig (yellowgreen.de)
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\I18n\Service;
use TYPO3\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\Fluid\Core\ViewHelper\Facets\CompilableInterface;

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
class HyphenateViewHelper extends AbstractViewHelper implements CompilableInterface
{

    /**
     * @var \TYPO3\Flow\I18n\Service
     */
    protected $localizationService;

    /**
     * @see AbstractViewHelper::isOutputEscapingEnabled()
     * @var boolean
     */
    protected $escapeOutput = FALSE;

    /**
     * @var boolean
     */
    protected $escapeChildren = FALSE;

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
     * @param Service $localizationService
     * @return void
     */
    public function injectLocalizationService(Service $localizationService)
    {
        $this->localizationService = $localizationService;
    }

    /**
     * Inject the settings
     *
     * @param array $settings
     * @return void
     */
    public function injectSettings(array $settings)
    {
        mb_internal_encoding("utf-8");
        $this->settings = $settings;

        $this->dictionary = [];
        if (file_exists($this->settings['dictionary'])) {
            $entries = file($this->settings['dictionary']);
            foreach ($entries as $entry) {
                $entry = trim($entry);
                $this->dictionary[str_replace('/', '', mb_strtolower($entry))] = str_replace('/', $this->settings['hyphen'], $entry);
            }
        }

        $this->patterns = [];
    }


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
    public function render($value = NULL, $locale = null)
    {
        $renderChildrenClosure = $this->buildRenderChildrenClosure();
        $renderingContext = $this->renderingContext;


        $value = $arguments['value'];
        if ($value === NULL) {
            $value = $renderChildrenClosure();
        }
        if (is_string($value) || (is_object($value) && method_exists($value, '__toString'))) {
            if ($locale === null) {
                $locale = $this->localizationService->getConfiguration()->getCurrentLocale();
            }

            if (!isset($this->patterns[$locale])) {
                $filename = $this->settings['patternPath'] . $locale;
                if ($locale === 'en') $filename .= '-gb';
                if (file_exists($filename . '.php')) {
                    $this->patterns[$locale] = include($filename . '.php');
                } elseif (file_exists($filename . '.js')) {
                    if ($this->makePHPPattern($filename)) {
                        $this->patterns[$locale] = include($filename . '.php');
                    } else {
                        $this->patterns[$locale] = [];
                    }
                } else {
                    $this->patterns[$locale] = [];
                }
            }

            if ($this->patterns[$locale] === []) {
                return $value;
            }

            return $this->hyphenation($value, $locale);
        }

        return $value;
    }

    /**
     * Add text hyphenation.
     *
     * @param string $text
     * @return string
     */
    protected function hyphenation($text, $locale)
    {
        $word = '';
        $tag  = '';
        $tagJump = 0;
        $output = [];

        $text .= ' ';
        $inAttr1 = false;
        $inAttr2 = false;

        for ($i = 0; $i < mb_strlen($text); $i++) {
            $char = mb_substr($text, $i, 1);
            if ($tag !== '') {
                if ($char === '"') $inAttr1 = !$inAttr1;
                if ($char === "'") $inAttr2 = !$inAttr2;
            }
            if (mb_strpos($this->settings['wordBoundaries'], $char) === false && $tag === '') {
                $word .= $char;
            } else {
                if ($word !== '') {
                    $output[] = $this->wordHyphenation($word);
                    $word = '';
                }
                if ($tag !== '' || $char === '<') {
                    $tag .= $char;
                }
                if ($tag !== '' && $char === '>' && !$inAttr1 && !$inAttr2) {
                    $tagName = (mb_strpos($tag, ' ')) ? mb_substr($tag, 1, mb_strpos($tag, ' ') - 1) : mb_substr($tag, 1, mb_strpos($tag, '>') - 1);
                    if ($tagJump === 0 && in_array(mb_strtolower($tagName), $this->settings['excludeTags'])) {
                        $tagJump = 1;
                    } else if ($tagJump === 0 || mb_strtolower(mb_substr($tag, -mb_strlen($tagName) - 3)) === '</' . mb_strtolower($tagName) . '>') {
                        $output[] = $tag;
                        $tag = '';
                        $tagJump = 0;
                    }
                }
                if ($tag === '' && $char !== '<' && $char !== '>') $output[] = $char;
            }
        }

        $text = join($output);
        return mb_substr($text, 0, mb_strlen($text) - 1);
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
        if (mb_strlen($word) < $this->settings['shortestPattern']
          || mb_strpos($word, $this->settings['hyphen']) !== false
          || mb_strpos($word, $this->settings['altHyphen']) !== false) {
            return $word;
        }
        if (isset($this->dictionary[mb_strtolower($word)])) return $this->dictionary[mb_strtolower($word)];

        $w = '_' . $word . '_';
        $wLength = mb_strlen($w);
        $chars = $this->mb_split_chars($w);
        $w = mb_strtolower($w);
        $hyphenatedWord = [];
        $hypos = [];

        $n = $wLength - $this->settings['shortestPattern'];
        for ($p = 0; $p <= $n; $p++) {
            $maxWins = min(($wLength - $p), $this->settings['longestPattern']);
            for ($win = $this->settings['shortestPattern']; $win <= $maxWins; $win++) {
                if (isset($this->patterns[$locale][mb_substr($w, $p, $win)])) {
                    $pat = $this->patterns[$locale][mb_substr($w, $p, $win)];
                    $patLength = mb_strlen($pat);

                    $t = 0;
                    $val = [];
                    for ($i = 0; $i < $patLength; $i++) {
                        $c = mb_substr($pat, $i, 1);
                        if (is_numeric($c)) {
                            $val[] = $i - $t;
                            $val[] = (int)$c;
                            $t++;
                        }
                    }
                    $pat = $val;
                } else {
                    continue;
                }

                for ($i = 0; $i < count($pat); $i += 2) {
                    $c = $p - 1 + $pat[$i];
                    if (!$hypos[$c] || $hypos[$c] < $pat[$i + 1]) {
                        $hypos[$c] = $pat[$i + 1];
                    }
                }
            }
        }

        $inserted = 0;
        for ($i = $this->settings['leftmin']; $i <= (mb_strlen($word) - $this->settings['rightmin']); $i++) {
            if (!!($hypos[$i] & 1)) {
                array_splice($chars, $i + $inserted + 1, 0, $this->settings['hyphen']);
                $inserted++;
            }
        }
        $hyphenatedWord = implode(array_splice($chars, 1, -1));

        /**
         * @TODO Add caching?
         */
        return $hyphenatedWord;
    }


    /**
     * Internal helper function to split chars.
     *
     * @param string $string
     * @return array
     */
    protected function mb_split_chars($string)
    {
        $i = 0;
        $strlen = mb_strlen($string);
        $array = [];
        while ($strlen - $i > 0) {
            $array[] = mb_substr($string, $i++, 1, 'utf-8');
        }
        return $array;
    }

    /**
     * Internal helper function to convert js-Files from hyphenator.js
     * to a php-Pattern file.
     *
     * @param string $filename
     * @return void
     */
    protected function makePHPPattern($filename)
    {
        $lines = file($filename . '.js');
        $lines = str_replace(':', '=', $lines);
        $lines = str_replace(',', ';', $lines);
        $lines = str_replace('leftmin', '$leftmin', $lines);
        $lines = str_replace('rightmin', '$rightmin', $lines);
        $lines = str_replace('shortestPattern', '$shortestPattern', $lines);
        $lines = str_replace('longestPattern', '$longestPattern', $lines);
        $lines = str_replace(' ', '', $lines);
        $lines = str_replace("\t", '', $lines);

        $patterns = [];
        foreach ($lines as $lineNum => $line) {
            $num = number_format($line);
            if ($num >= 3) {
                $str1 = explode('"', $line);
                for ($i = 0; $i < mb_strlen($str1[1]) / $num; $i++) {
                    $patterns[] = mb_substr($str1[1], $i * $num, $num, 'utf-8');
                }
            }
        }

        $filename = $filename . '.php';
        if (!($handle = fopen($filename, 'w'))) {
            // TODO: Add Exception
            return false;
        }

        $pattern = "return ['" . implode("', '", $patterns) . "']";
        if (!fwrite($handle, "<?php\n" . $pattern)) {
            // TODO: Add Exception
            return false;
        }

        fclose($handle);
        return true;
    }
}
