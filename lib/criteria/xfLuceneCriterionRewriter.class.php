<?php
/**
 * This file is part of the sfLucene package.
 * (c) Carl Vondrick <carl.vondrick@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Rewrites a criterion into Zend_Search_Lucene_Search_Query's
 *
 * @package sfLucene
 * @subpackage Criteria
 * @author Carl Vondrick
 */
final class xfLuceneCriterionRewriter
{
  /**
   * Rewrites the criterion
   *
   * @param xfCriterion $criterion The criterion to rewrite
   * @returns Zend_Search_Lucene_Search_Query
   */
  static public function rewrite(xfCriterion $criterion)
  {
    $criterion = $criterion->breakdown();
    $class = get_class($criterion);

    switch ($class)
    {
      case 'xfCriteria':
        return self::doBoolean($criterion);
      case 'xfCriterionString':
        return self::doString($criterion);
      case 'xfCriterionField':
        return self::doField($criterion);
      default:
        throw new xfLuceneException('Unknown criterion "' . $class . '"');
    }
  }

  /**
   * Rewrites a boolean query
   *
   * @param xfCriteria $crit
   * @returns Zend_Search_Lucene_Search_Query_Boolean
   */
  static public function doBoolean(xfCriteria $crit)
  {
    $bool = new Zend_Search_Lucene_Search_Query_Boolean;

    foreach ($crit->getOperators() as $operator)
    {
      $mode = $operator->getMode();

      if ($mode & xfCriteriaOperator::SHOULD)
      {
        $mode = null;
      }
      elseif ($mode & xfCriteriaOperator::CANNOT)
      {
        $mode = false;
      }
      else
      {
        $mode = true;
      }

      $bool->addSubquery(self::rewrite($operator->getCriterion()), $mode);
    }

    return $bool;
  }

  /**
   * Rewrites a field
   *
   * @param xfCriterionField $crit
   * @returns Zend_Search_Lucene_Search_Query 
   */
  static public function doField(xfCriterionField $crit)
  {
    $term = new Zend_Search_Lucene_Index_Term($crit->getValue(), $crit->getName());
    $query = new Zend_Search_Lucene_Search_Query_Term($term);

    return $query;
  }

  /**
   * Rewrites a string
   *
   * @param xfCriterionString $crit
   * @throws Zend_Search_Lucene_Exception if parse fails
   * @returns Zend_Search_Lucene_Search_Query
   */
  static public function doString(xfCriterionString $crit)
  {   
    if ($crit->getMode() & xfCriterionString::FATAL)
    {
      Zend_Search_Lucene_Search_QueryParser::dontSuppressQueryParsingExceptions();
    }
    else
    {
      Zend_Search_Lucene_Search_QueryParser::suppressQueryParsingExceptions();
    }

    return Zend_Search_Lucene_Search_QueryParser::parse($crit->getQuery(), $crit->getEncoding());
  }
}
