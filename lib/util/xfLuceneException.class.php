<?php
/**
 * This file is part of the sfLucene package.
 * (c) Carl Vondrick <carl.vondrick@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'sfSearchPlugin' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'util' . DIRECTORY_SEPARATOR . 'xfException.class.php';

/**
 * The base xfLuceneException
 *
 * @package sfLucene
 * @subpackage Utilities
 * @author Carl Vondrick
 */
class xfLuceneException extends xfException
{
}
