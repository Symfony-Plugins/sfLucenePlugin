<?php
/*
 * This file is part of the sfLucenePlugin package
 * (c) 2007 Carl Vondrick <carlv@carlsoft.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


require_once(SF_ROOT_DIR . '/plugins/sfLucenePlugin/modules/sfLucene/lib/BasesfLuceneComponents.class.php');

/**
 * @package    sfLucenePlugin
 * @subpackage Module
 * @author     Carl Vondrick <carlv@carlsoft.net>
 * @version SVN: $Id: components.class.php 6247 2007-12-01 03:25:13Z Carl.Vondrick $
 */
class ##MODULE_NAME##Components extends BasesfLuceneComponents
{
  /**
   * Returns an instance of sfLucene configured for this environment.
   */
  protected function getLuceneInstance()
  {
    return ##CALLABLE##;
  }
}