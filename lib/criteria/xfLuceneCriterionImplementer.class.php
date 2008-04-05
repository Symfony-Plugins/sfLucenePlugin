<?php
/**
 * This file is part of the sfLucene package.
 * (c) Carl Vondrick <carl.vondrick@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Binds the criterion to a specific implementation.
 *
 * @package sfLucene
 * @subpackage Criteria
 * @author Carl Vondrick
 */
final class xfLuceneCriterionImplementer implements xfCriterionImplementer
{
  /**
   * The abstract criterion
   *
   * @var xfCriterion
   */
  private $abstract;

  /**
   * The concrete criterion
   *
   * @var Zend_Search_Lucene_Search_Query
   */
  private $concrete;

  /**
   * Constructor to set initial criterion.
   *
   * @param xfCriterion $abstract The abstract criterion
   * @param Zend_Search_Lucene_Search_Query $concrete The concrete (Zend)
   * query
   */
  public function __construct(xfCriterion $abstract, Zend_Search_Lucene_Search_Query $concrete)
  {
    $this->abstract = $abstract;
    $this->concrete = $concrete;
  }

  /**
   * @see xfCriterionImplementer
   */
  public function getAbstractCriterion()
  {
    return $this->abstract;
  }

  /**
   * @see xfCriterionImplemente
   * @returns Zend_Search_Lucene_Search_Query
   */
  public function getConcreteCriterion()
  {
    return $this->concrete;
  }

  /**
   * @todo
   * @see xfCriterionImplementer
   */
  public function tokenize($input)
  {
    return array();
  }
}
