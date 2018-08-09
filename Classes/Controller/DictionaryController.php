<?php
/**
 * The dictionary controller to handle dictionary entries for custom hyphenations.
 *
 * This file is part of the Flow Package "Wegmeister.Hyphenator".
 *
 * PHP version 7
 *
 * @category Hyphenator
 * @package  Wegmeister\Hyphenator
 * @author   Benjamin Klix <benjamin.klix@die-wegmeister.com>
 * @license  https://github.com/die-wegmeister/Wegmeister.Hyphenator/blob/master/LICENSE GPL-3.0-or-later
 * @link     https://github.com/die-wegmeister/Wegmeister.Hyphenator
 */
namespace Wegmeister\Hyphenator\Controller;

/*
 * This file is part of the Wegmeister.Hyphenator package.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Wegmeister\Hyphenator\Domain\Model\Dictionary;
use Wegmeister\Hyphenator\Domain\Repository\DictionaryRepository;

class DictionaryController extends ActionController
{

    /**
     * The repository to load the dictionary entries from.
     *
     * @Flow\Inject
     * @var DictionaryRepository
     */
    protected $dictionaryRepository;

    /**
     * Return all entries on the overview.
     *
     * @return void
     */
    public function indexAction()
    {
        $this->view->assign('dictionaries', $this->dictionaryRepository->findAll());
    }

    /**
     * Show a single dictionary entry.
     *
     * @param Dictionary $dictionary The dictionary entry.
     *
     * @return void
     */
    public function showAction(Dictionary $dictionary)
    {
        $this->view->assign('dictionary', $dictionary);
    }

    /**
     * Show template to create a new dictionary entry.
     *
     * @return void
     */
    public function newAction()
    {
    }

    /**
     * Create a new dictionary entry.
     *
     * @param Dictionary $newDictionary The new dictionary entry.
     *
     * @return void
     */
    public function createAction(Dictionary $newDictionary)
    {
        $this->dictionaryRepository->add($newDictionary);
        $this->addFlashMessage('Created a new dictionary.');
        $this->redirect('index');
    }

    /**
     * Load dictionary entry for editing.
     *
     * @param Dictionary $dictionary The dictionary entry.
     *
     * @return void
     */
    public function editAction(Dictionary $dictionary)
    {
        $this->view->assign('dictionary', $dictionary);
    }

    /**
     * Update the given dictionary entry.
     *
     * @param Dictionary $dictionary The dictionary entry.
     *
     * @return void
     */
    public function updateAction(Dictionary $dictionary)
    {
        $this->dictionaryRepository->update($dictionary);
        $this->addFlashMessage('Updated the dictionary.');
        $this->redirect('index');
    }

    /**
     * Remove the given dictionary entry.
     *
     * @param Dictionary $dictionary The dictionary entry.
     *
     * @return void
     */
    public function deleteAction(Dictionary $dictionary)
    {
        $this->dictionaryRepository->remove($dictionary);
        $this->addFlashMessage('Deleted a dictionary.');
        $this->redirect('index');
    }
}
