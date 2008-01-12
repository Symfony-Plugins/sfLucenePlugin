<?php
/*
 * This file is part of the sfLucenePlugin package
 * (c) 2007 - 2008 Carl Vondrick <carl@carlsoft.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @package sfLucenePlugin
 * @subpackage Filter
 * @author Carl Vondrick
 * @version SVN: $Id$
 */
class sfLuceneCacheFilter extends sfCacheFilter
{
  public function execute($chain)
  {
    $chain->execute();
  }
}