<?php
/**
 * This file is part of the sfLucene package.
 * (c) Carl Vondrick <carl.vondrick@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Tokenizes a Zend_Search_Lucene_Search_Query into xfToken
 *
 * @todo Create patch to integrate this directly into Zend_Search_Lucene. This
 * is bad OOP design.
 *
 * @todo Implement the methods to follow token rules more closely.  Hopefully
 * Zend_Search_Lucene will come through and make this class obsolete.  
 *
 * @package sfLucene
 * @subpackage Utilities
 * @author Carl Vondrick
 */
final class xfLuceneTokenizer
{
  /**
   * The Zend_Search_Lucene query
   *
   * @var Zend_Search_Lucene_Search_Query
   */
  private $query;

  /**
   * The text to tokenize
   *
   * @var string
   */
  private $text;

  /**
   * The text encoding
   *
   * @var string
   */
  private $encoding;

  /**
   * The matched tokens
   *
   * @var array
   */
  private $tokens = array();

  /**
   * The text tokens
   *
   * @var array
   */
  private $textTokens = array();

  /**
   * The analyzer
   *
   * @var Zend_Search_Lucene_Analysis_Analyzer
   */
  private $analyzer;

  /**
   * Constructor to set query
   *
   * @param Zend_Search_Lucene_Search_Query $query
   */
  public function __construct(Zend_Search_Lucene_Search_Query $query)
  {
    $this->query = $query;
    $this->analyzer = Zend_Search_Lucene_Analysis_Analyzer::getDefault();
  }

  /**
   * Tokenizes text to create xfToken's
   *
   * @param string $text
   * @param string $encoding
   * @returns array of xfToken
   */
  public function tokenize($text, $encoding = '')
  {
    $this->text = $text;
    $this->textTokens = $this->analyzer->tokenize($text, $encoding);

    $this->handle($this->query);

    $tokens = $this->tokens;

    $this->text = null;
    $this->tokens = array();

    return $tokens;
  }

  /**
   * Handles a general Zend_Search_Lucene query
   *
   * @param Zend_Search_Lucene_Search_Query $query
   */
  private function handle(Zend_Search_Lucene_Search_Query $query)
  {
    if ($query instanceof Zend_Search_Lucene_Search_Query_Boolean)
    {
      $this->handleBoolean($query);
    }
    elseif ($query instanceof Zend_Search_Lucene_Search_Query_Term)
    {
      $this->handleTerm($query);
    }
    elseif ($query instanceof Zend_Search_Lucene_Search_Query_MultiTerm)
    {
      $this->handleMultiTerm($query);
    }
    elseif ($query instanceof Zend_Search_Lucene_Search_Query_Phrase)
    {
      $this->handlePhrase($query);
    }
    elseif ($query instanceof Zend_Search_Lucene_Search_Query_Range)
    {
      $this->handleRange($query);
    }
    elseif ($query instanceof Zend_Search_Lucene_Search_Query_Wildcard)
    {
      $this->handleWildcard($query);
    }
    elseif ($query instanceof Zend_Search_Lucene_Search_Query_Fuzzy)
    {
      $this->handleFuzzy($query);
    }
    elseif ($query instanceof Zend_Search_Lucene_Search_Query_Empty)
    {
      // nothing to do
    }
    elseif ($query instanceof Zend_Search_Lucene_Search_Query_Insignificant)
    {
      // nothing to do
    }
    else
    {
      throw new xfLuceneException('Unable to handle query "' . get_class($query) . '"');
    }
  }

  /**
   * Handles a boolean query
   *
   * @param Zend_Search_Lucene_Search_Query_Boolean $query
   */
  private function handleBoolean(Zend_Search_Lucene_Search_Query_Boolean $query)
  {
    foreach ($query->getSubqueries() as $subquery)
    {
      $this->handle($subquery);
    }
  }

  /**
   * Handles a term query
   *
   * @param Zend_Search_Lucene_Search_Query_Term $query
   */
  private function handleTerm(Zend_Search_Lucene_Search_Query_Term $query)
  {
    foreach ($this->textTokens as $token)
    {
      if ($token->getTermText() == $query->getTerm()->text)
      {
        $this->addToken($token);
      }
    }
  }

  /**
   * Handles a multi term query
   *
   * @param Zend_Search_Lucene_Search_Query_MutliTerm $query
   */
  private function handleMultiterm(Zend_Search_Lucene_Search_Query_MultiTerm $query)
  {
    foreach ($this->textTokens as $token)
    {
      foreach ($query->getQueryTerms() as $qterm)
      {
        if ($token->getTermText() == $qterm->text)
        {
          $this->addToken($token);
        }
      }
    }
  }

  /**
   * Handles a phrase query
   *
   * @todo Currently this method just tokenizes each term.  We need to change it
   * so it only tokenizes an entire phrase only.  Current behavior is to match
   * both "foo bar" and "bar foo" when it should only match "foo bar"
   *
   * @param Zend_Search_Lucene_Search_Query_Phrase $query
   */
  private function handlePhrase(Zend_Search_Lucene_Search_Query_Phrase $query)
  {
    foreach ($this->textTokens as $token)
    {
      foreach ($query->getQueryTerms() as $qterm)
      {
        if ($token->getTermText() == $qterm->text)
        {
          $this->addToken($token);
        }
      }
    }
  }

  /**
   * Handles a range query
   *
   * @todo Implementation
   *
   * @param Zend_Search_Lucene_Search_Query_Range
   */
  private function handleRange(Zend_Search_Lucene_Search_Query_Range $query)
  {
    // nothing to do
  }

  /**
   * Handles a wildcard query
   *
   * @todo Implementation
   *
   * @param Zend_Search_Lucene_Search_Query_Wildcard
   */
  private function handleWildcard(Zend_Search_Lucene_Search_Query_Wildcard $query)
  {
    // nothing to do
  }

  /**
   * Handles a fuzzy query
   *
   * @todo Implementation
   *
   * @param Zend_Search_Lucene_Search_Query_Fuzzy
   */
  private function handleFuzzy(Zend_Search_Lucene_Search_Query_Fuzzy $query)
  {
    // nothing to do
  }

  /**
   * Adds a token to the hash
   *
   * @param Zend_Search_Lucene_Analysis_Token $token
   */
  private function addToken(Zend_Search_Lucene_Analysis_Token $token)
  {
    $start = $token->getStartOffset();
    $end = $token->getEndOffset();

    $text = substr($this->text, $start, $end - $start);

    $this->tokens[] = new xfToken($text, $start, $end);
  }
}
