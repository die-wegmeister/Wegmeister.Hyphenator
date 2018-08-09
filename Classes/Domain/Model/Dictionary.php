<?php
/**
 * DictionaryEntry for custom hyphenation.
 *
 * This file is part of the Wegmeister.Hyphenator package.
 *
 * PHP version 7
 *
 * @category Hyphenator
 * @package  Wegmeister\Hyphenator
 * @author   Benjamin Klix <benjamin.klix@die-wegmeister.com>
 * @license  https://github.com/die-wegmeister/Wegmeister.Hyphenator/blob/master/LICENSE GPL-3.0-or-later
 * @link     https://github.com/die-wegmeister/Wegmeister.Hyphenator
 */
namespace Wegmeister\Hyphenator\Domain\Model;

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Entity
 */
class Dictionary
{
    /**
     * Locale this dictionary entry is used for.
     *
     * @var string
     */
    protected $locale;

    /**
     * The hyphenated word, devided by slashes (/).
     *
     * @var string
     */
    protected $word;


    /**
     * Get the locale set for this dictionary entry.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set the locale for this dictionary entry.
     *
     * @param string $locale The locale to set.
     *
     * @return void
     */
    public function setLocale(string $locale)
    {
        $this->locale = $locale;
    }
    /**
     * Get the hyphenated word of this dictionary entry.
     *
     * @return string
     */
    public function getWord()
    {
        return $this->word;
    }

    /**
     * Set the hyphenated word for this dictionary entry.
     *
     * @param string $word The new hyphenated word.
     *
     * @return void
     */
    public function setWord(string $word)
    {
        $this->word = $word;
    }
}
