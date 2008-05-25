<?php
/**
 * This file is part of the sfLucene package.
 * (c) Carl Vondrick <carl.vondrick@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


require dirname(__FILE__) . '/../../bootstrap/unit.php';
require 'util/xfLuceneZendManager.class.php';
require 'criteria/xfLuceneCriterionRewriter.class.php';
require 'criteria/xfCriterion.interface.php';
require 'criteria/xfCriteria.class.php';
require 'criteria/xfCriteriaOperator.class.php';
require 'criteria/xfCriterionString.class.php';
require 'criteria/xfCriterionField.class.php';
require 'util/xfLuceneException.class.php';
xfLuceneZendManager::load();

$t = new lime_test(13, new lime_output_color);

$t->diag('::doString()');
$r = xfLuceneCriterionRewriter::doString(new xfCriterionString('foobar', xfCriterionString::FATAL, 'utf8'));
$t->ok($r instanceof Zend_Search_Lucene_Search_Query, '::doString() returns a Zend_Search_Lucene_Search_Query');

try {
  $msg = '::doString() fails if query contains syntax errors and mode is fatal';
  $r = xfLuceneCriterionRewriter::doString(new xfCriterionString('(foobar', xfCriterionString::FATAL, 'utf8'));
  $t->fail($msg);
} catch (Exception $e) {
  $t->pass($msg);
}

try {
  $msg = '::doString() succeeds if query contains syntax errors and mode is failsafe';
  $r = xfLuceneCriterionRewriter::doString(new xfCriterionString('(foobar', xfCriterionString::FAILSAFE, 'utf8'));
  $t->pass($msg);
} catch (Exception $e) {
  $t->fail($msg);
}
  
$t->diag('::doBoolean()');
$r = xfLuceneCriterionRewriter::doBoolean(new xfCriteria);
$t->isa_ok($r, 'Zend_Search_Lucene_Search_Query_Boolean', '::doBoolean() returns a Zend_Search_Lucene_Search_Query_Boolean');

$c = new xfCriteria;
$c->add(new xfCriteriaOperator(new xfCriterionString('foobar'), xfCriteriaOperator::MUST));
$c->add(new xfCriteriaOperator(new xfCriterionString('foobar'), xfCriteriaOperator::SHOULD));
$c->add(new xfCriteriaOperator(new xfCriterionString('foobar'), xfCriteriaOperator::CANNOT));
$r = xfLuceneCriterionRewriter::doBoolean($c);

$t->is(count($r->getSubqueries()), 3, '::doBoolean() creates the correct number of subqueries');
$t->is($r->getSigns(), array(true, null, false), '::doBoolean() assigns the correct signs');

$t->diag('::doField()');
$r = xfLuceneCriterionRewriter::doField(new xfCriterionField('field', 'value', 'utf8'));
$t->isa_ok($r, 'Zend_Search_Lucene_Search_Query_Term', '::doField() returns a Zend_Search_Lucene_Search_Query_Term');
$t->is($r->getTerm()->field, 'field', '::doField() returns a field with correct name');
$t->is($r->getTerm()->text, 'value', '::doField() returns a field with correct value');

$t->diag('::rewrite()');
$t->isa_ok(xfLuceneCriterionRewriter::rewrite(new xfCriteria), 'Zend_Search_Lucene_Search_Query_Boolean', '::rewrite() with xfCriteria returns a Zend_Search_Lucene_Search_Query_Boolean');
$t->isa_ok(xfLuceneCriterionRewriter::rewrite(new xfCriterionString('query')), 'Zend_Search_Lucene_Search_Query_Boolean', '::rewrite() with xfCriterionString returns a Zend_Search_Lucene_Search_Query_Boolean');
$t->isa_ok(xfLuceneCriterionRewriter::rewrite(new xfCriterionField('field', 'value')), 'Zend_Search_Lucene_Search_Query_Term', '::rewrite() with xfCriterionField returns a Zend_Search_Lucene_Search_Query_Term');

class FooCriterion implements xfCriterion
{
  public function breakdown()
  {
  }
}

try {
  $msg = '::rewrite() fails if criterion is unknown';
  xfLuceneCriterionRewriter::rewrite(new FooCriterion);
  $t->fail($msg);
} catch (Exception $e) {
  $t->pass($msg);
}
