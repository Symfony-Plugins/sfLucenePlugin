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
require 'mock/criteria/xfMockCriterion.class.php';
require 'vendor/Zend/Search/Lucene.php';

$t = new lime_test(null, new lime_output_color);

$mock = new xfMockCriterion;
$query = new Zend_Search_Lucene_Search_Query_Boolean;
$i = new xfLuceneCriterionImplementer($mock, $query);

$t->is($i->getAbstractCriterion(), $mock, '->getAbstractCriterion() returns the abstract criterion');
$t->is($i->getConcreteCriterion(), $query, '->getConcreteCriterion() returns the concrete criterion');
$t->todo('->tokenize() tokenizes strings');
