<?php
/**
 * This file is part of the sfLucene package.
 * (c) Carl Vondrick <carl.vondrick@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require dirname(__FILE__) . '/../../bootstrap/unit.php';
require 'criteria/xfCriterionImplementer.interface.php';
require 'criteria/xfLuceneCriterionImplementer.class.php';
require 'criteria/xfCriterion.interface.php';
require 'util/xfLuceneTokenizer.class.php';
require 'util/xfTokenInterface.interface.php';
require 'util/xfToken.class.php';
require 'mock/criteria/xfMockCriterion.class.php';
require 'vendor/Zend/Search/Lucene.php';

$t = new lime_test(4, new lime_output_color);

$mock = new xfMockCriterion;
$query = new Zend_Search_Lucene_Search_Query_Term(new Zend_Search_Lucene_Index_Term('foobar'));
$i = new xfLuceneCriterionImplementer($mock, $query);

$t->is($i->getAbstractCriterion(), $mock, '->getAbstractCriterion() returns the abstract criterion');
$t->is($i->getConcreteCriterion(), $query, '->getConcreteCriterion() returns the concrete criterion');
$tokens = $i->tokenize('foo foobar baz');

$t->is(count($tokens), 1, '->tokenize() returns the correct number of tokens');
$t->is($tokens[0]->getText(), 'foobar', '->tokenize() tokenizes the correct words');
