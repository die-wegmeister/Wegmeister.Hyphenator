<?php
namespace Wegmeister\Hyphenator\Service;

/**
 * This script belongs to the TYPO3 Flow Package "Wegmeister.Hyphenator".
 * This service handles the hyphenation of texts.
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

/**
 * @Flow\Scope("singleton")
 */
class HyphenationService
{
    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\I18n\Service
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
        if ($locale === 'en') {
            $locale .= '-gb';
        }

        if (!isset($this->patterns[$locale])) {
            $filename = $this->settings['patternPath'] . $locale;
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
            return $text;
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
                    $output[] = $this->wordHyphenation($word, $locale);
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
        if (isset($this->dictionary[mb_strtolower($word)])) {
            return $this->dictionary[mb_strtolower($word)];
        }

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
                    if (!isset($hypos[$c]) || $hypos[$c] < $pat[$i + 1]) {
                        $hypos[$c] = $pat[$i + 1];
                    }
                }
            }
        }

        $inserted = 0;
        for ($i = $this->settings['leftmin']; $i <= (mb_strlen($word) - $this->settings['rightmin']); $i++) {
            if (isset($hypos[$i]) && !!$hypos[$i] & 1) {
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
            if (preg_match('/^([0-9]+)=/', $line, $matches)) {
                $num = $matches[1];
                if ($num >= 3) {
                    $str1 = explode('"', $line);
                    for ($i = 0; $i < mb_strlen($str1[1]) / $num; $i++) {
                        $pattern = mb_substr($str1[1], $i * $num, $num, 'utf-8');
                        $patterns[] = "'" . preg_replace('/[0-9]/', '', $pattern) . "'=>'" . $pattern . "'";
                    }
                }
            }
        }

        $filename = $filename . '.php';
        if (!($handle = fopen($filename, 'w'))) {
            // TODO: Add Exception
            return false;
        }

        $pattern = 'return [' . implode(', ', $patterns) . '];';
        if (!fwrite($handle, "<?php\n" . $pattern)) {
            // TODO: Add Exception
            return false;
        }

        fclose($handle);
        return true;
    }
}
