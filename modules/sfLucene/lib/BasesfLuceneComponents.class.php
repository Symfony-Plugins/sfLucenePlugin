<?php
/*
 * This file is part of the sfLucenePlugin package
 * (c) 2007 Carl Vondrick <carlv@carlsoft.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @package    sfLucenePlugin
 * @subpackage Module
 * @author     Carl Vondrick <carlv@carlsoft.net>
 * @version SVN: $Id$
 */
abstract class BasesfLuceneComponents extends sfComponents
{
  public function executePublicControls()
  {
    $this->query = $this->getRequestParameter('query');
  }

  public function executeCategories()
  {
    $installed = array_keys($this->getLuceneInstance()->getCategories()->getAllCategories());

    sfLoader::loadHelpers('I18N');

    $categories = array(null => __('All'));

    if (count($installed))
    {
      sort($installed);
      $categories += array_combine($installed, $installed);
    }

    $this->categories = $categories;

    $this->show = count($categories) > 1 ? true : false;

    $this->selected = $this->getRequestParameter('category', 0);
  }

  protected function getLuceneInstance()
  {
    return sfLuceneToolkit::getApplicationInstance();
  }
}
