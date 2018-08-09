<?php
/**
 * DictionaryRepository for custom hyphenation.
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
namespace Wegmeister\Hyphenator\Domain\Repository;

/*
 * This file is part of the Wegmeister.Hyphenator package.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Repository;
use Neos\Flow\Persistence\QueryInterface;

/**
 * @Flow\Scope("singleton")
 */
class DictionaryRepository extends Repository
{
    /**
     * Adjust order of entries.
     *
     * @var array
     */
    protected $defaultOrderings = [
        'word' => QueryInterface::ORDER_ASCENDING,
        'locale' => QueryInterface::ORDER_ASCENDING,
    ];
}
