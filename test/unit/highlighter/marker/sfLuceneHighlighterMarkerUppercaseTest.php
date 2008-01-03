<?php
/*
 * This file is part of the sfLucenePlugin package
 * (c) 2007 Carl Vondrick <carlv@carlsoft.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
  * @package sfLucenePlugin
  * @subpackage Test
  * @author Carl Vondrick
  * @version SVN: $Id$
  */

require dirname(__FILE__) . '/../../../bootstrap/unit.php';

$t = new lime_test(3, new lime_output_color());

$marker = new sfLuceneHighlighterMarkerUppercase;

$t->is($marker->highlight('foobar'), 'FOOBAR', '->highlight() converts the string to uppercase');

$t->isa_ok(sfLuceneHighlighterMarkerUppercase::generate(), 'sfLuceneHighlighterMarkerHarness', '::generate() returns a highlighter marker harness');

$t->is(sfLuceneHighlighterMarkerUppercase::generate()->getHighlighter()->highlight('foobar'), 'FOOBAR', '::generate() builds correct highlighters');