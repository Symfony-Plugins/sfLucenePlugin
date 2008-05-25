<?php
/**
 * This file is part of the sfLucene package.
 * (c) Carl Vondrick <carl.vondrick@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require dirname(__FILE__) . '/../../bootstrap/unit.php';
require 'util/xfLuceneTokenizer.class.php';
require 'util/xfLuceneException.class.php';
require 'util/xfTokenInterface.interface.php';
require 'util/xfToken.class.php';
require 'Zend/Search/Lucene.php';

class TokenizerTester
{
  public $count = 0, $tests = array();

  public function run()
  {
    $t = new lime_test($this->count, new lime_output_color);

    foreach ($this->tests as $test)
    {
      $t->diag(get_class($test['query']));

      try
      {
        $msg = '->tokenize() rejects ' . get_class($test['query']);
        $tokenizer = new xfLuceneTokenizer($test['query']);
        $tokens = $tokenizer->tokenize($test['text']);

        if ($test['exception'])
        {
          $t->fail($msg);
        }
      }
      catch (Exception $e)
      {
        if ($test['exception'])
        {
          $t->pass($msg);
        }
        else
        {
          throw $e;
        }
      }

      if ($test['exception'])
      {
        continue;
      }

      $t->is(count($tokens), count($test['expected']), '->tokenize() returns correct count');

      $x = 0;
      foreach ($test['expected'] as $pos)
      {
        if (!isset($tokens[$x]))
        {
          for ($y = 0; $y < 3; $y++)
          {
            $t->skip('Token ' . $x  . ' does not exist');
          }
        }
        else
        {
          $t->is($tokens[$x]->getText(), $pos[0], '->tokenize() returns tokens with correct text');
          $t->is($tokens[$x]->getStart(), $pos[1], '->tokenize() returns tokens with correct start position');
          $t->is($tokens[$x]->getEnd(), $pos[2], '->tokenize() returns tokens with correct end position');
        }
        $x++;
      }
    }
  }
  
  public function add($query, $text, $expected, $exception = false)
  {
    if ($exception)
    {
      $this->count++;
    }
    else
    {
      $this->count += 1 + 3 * count($expected);
    }

    $this->tests[] = array(
      'query' => $query,
      'text' => $text,
      'expected' => $expected,
      'exception' => $exception
    );
  }
}

$t = new TokenizerTester;
$t->add(
  new Zend_Search_Lucene_Search_Query_Term(new Zend_Search_Lucene_Index_Term('foobar')),
  'foobar bar foo bar baz fobar bar foobar barfoo',
  array(
    array('foobar', 0, 6),
    array('foobar', 33, 39)
  ));

$t->add(
  new Zend_Search_Lucene_Search_Query_MultiTerm(array(new Zend_Search_Lucene_Index_Term('bar'), new Zend_Search_Lucene_Index_Term('foo'))),
  'bar foo baz foobar foo bar baz',
  array(
    array('bar', 0, 3),
    array('foo', 4, 7),
    array('foo', 19, 22),
    array('bar', 23, 26),
  ));

$t->add(
  new Zend_Search_Lucene_Search_Query_Boolean(array(new Zend_Search_Lucene_Search_Query_Term(new Zend_Search_Lucene_Index_Term('fabien')), new Zend_Search_Lucene_Search_Query_Term(new Zend_Search_Lucene_Index_Term('symfony')) )),
  'fabien potoencier wrote symfony',
  array(
    array('fabien', 0, 6),
    array('symfony', 24, 31)
  ));

$t->add(
  new Zend_Search_Lucene_Search_Query_Phrase(array('foo', 'bar')),
  'foo bar is awesome',
  array(
    array('foo', 0, 3),
    array('bar', 4, 7)
  ));

$t->add(
  new Zend_Search_Lucene_Search_Query_Range('a', 'z', true),
  'a b c',
  array(
  ));

$t->add(
  new Zend_Search_Lucene_Search_Query_Wildcard(new Zend_Search_Lucene_Index_Term('*man')),
  'woman man batman nothing',
  array(
  ));

$t->add(
  new Zend_Search_Lucene_Search_Query_Fuzzy(new Zend_Search_Lucene_Index_Term('')),
  'fuzzy tokens',
  array(
  ));

$t->add(
  new Zend_Search_Lucene_Search_Query_Empty,
  'i am nobody important',
  array(
  ));

$t->add(
  new Zend_Search_Lucene_Search_Query_Insignificant,
  'i am nobody significant',
  array(
  ));

class FakeQuery extends Zend_Search_Lucene_Search_Query
{
    public function rewrite(Zend_Search_Lucene_Interface $index)
    {
    }
    public function optimize(Zend_Search_Lucene_Interface $index)
    {
    }
    public function createWeight(Zend_Search_Lucene_Interface $reader)
    {
    }
    public function execute(Zend_Search_Lucene_Interface $reader)
    {
    }
    public function matchedDocs()
    {
    }
    public function score($docId, Zend_Search_Lucene_Interface $reader)
    {
    }
    public function getQueryTerms()
    {
    }
    public function highlightMatchesDOM(Zend_Search_Lucene_Document_Html $doc, &$colorIndex)
    {
    }
    public function __toString()
    {
    }
}

$t->add(
  new FakeQuery,
  'this will fail',
  array(),
  true);

$t->run();

