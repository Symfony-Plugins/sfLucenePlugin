<?php
/*
 * This file is part of the sfLucenePlugin package
 * (c) 2007 - 2008 Carl Vondrick <carl@carlsoft.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Highlighter for XHTML that is not a full document.
 *
 * @package    sfLucenePlugin
 * @subpackage Highlighter
 * @author     Carl Vondrick <carl@carlsoft.net>
 * @version SVN: $Id$
 */
class sfLuceneHighlighterXHTMLPart extends sfLuceneHighlighterXHTML
{
  protected function prepare()
  {
    // convert the data to a full document and we'll remove this part later
    $this->data = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/2000/REC-xhtml1-200000126/DTD/xhtml1-transitional.dtd"><html><body>' . $this->data . '</body></html>';

    parent::prepare();
  }

  protected function cleanup()
  {
    // select just
    $node = $this->xpath->query($this->xpathQuery)->item(0);

    $this->data = $this->document->saveXML($node);
    $this->data = substr($this->data, 6, -7);
  }
}