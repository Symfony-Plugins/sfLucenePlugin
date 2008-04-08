<?php
/**
 * This file is part of the sfLucene package.
 * (c) Carl Vondrick <carl.vondrick@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * The Zend_Search_Lucene backend engine.
 *
 * @package xfLucene
 * @subpackage Engine
 * @author Carl Vondrick
 */
final class xfLuceneEngine implements xfEngine
{
  /**
   * Flag for UTF8 analyzer
   */
  const ANALYZER_UTF8 = 1;

  /**
   * Flag for text analyzer
   */
  const ANALYZER_TEXT = 2;

  /**
   * Flag for number analyzer
   */
  const ANALYZER_NUMBER = 4;

  /**
   * Flag for case sensitive analyzer
   */
  const ANALYZER_CASE_SENSITIVE = 8;

  /**
   * The Lucene index.
   *
   * @var Zend_Search_Lucene_Interface
   */
  private $index;

  /**
   * The index location
   *
   * @var string
   */
  private $location;

  /**
   * The analyzer
   *
   * @var Zend_Search_Lucene_Analsysi_Analyzer
   */
  private $analyzer;

  /**
   * Constructor to set initial values.
   *
   * @param string $location The index location
   */
  public function __construct($location)
  {
    $this->location = $location;
    $this->analyzer = new Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8Num_CaseInsensitive;

    Zend_Search_Lucene_Storage_Directory_Filesystem::setDefaultFilePermissions(0777);
  }

  /**
   * Configures according to flags
   *
   * @param int $flag
   */
  public function configure($flag)
  {
    if ($flag & self::ANALYZER_UTF8 || $flag & self::ANALYZER_TEXT)
    {
      // flag modifies analyzer
      
      if ($flag & self::ANALYZER_UTF8)
      {
        $class = 'Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8';
      }
      elseif ($flag & self::ANALYZER_TEXT)
      {
        $class = 'Zend_Search_Lucene_Analysis_Analyzer_Common_Text';
      }

      if ($flag & self::ANALYZER_NUMBER)
      {
        $class .= 'Num';
      }

      if (!($flag & self::ANALYZER_CASE_SENSITIVE))
      {
        $class .= '_CaseInsensitive';
      }

      $this->setAnalyzer(new $class);
    }
  }

  /**
   * Sets the analyzer 
   *
   * @param Zend_Search_Lucene_Analysis_Analyzer $analyzer
   */
  public function setAnalyzer(Zend_Search_Lucene_Analysis_Analyzer $analyzer)
  {
    $this->analyzer = $analyzer;
  }

  /**
   * Gets the analyzer
   *
   * @returns Zend_Search_Lucene_Analysis_Analyzer
   */
  public function getAnalyzer()
  {
    return $this->analyzer;
  }

  /**
   * Configures the index for batch processing
   */
  public function enableBatchMode()
  {
    $index = $this->getIndex();

    $index->setMaxBufferedDocs(500);
    $index->setMaxMergeDocs(PHP_INT_MAX);
    $index->setMergeFactor(100);
  }

  /**
   * Configures the index for interactive processing.
   */
  public function enableInteractiveMode()
  {
    $index = $this->getIndex();

    $index->setMaxBufferedDocs(10);
    $index->setMaxMergeDocs(PHP_INT_MAX);
    $index->setMergeFactor(10);
  }

  /**
   * @see xfEngine
   */
  public function open()
  {
    if (!$this->index)
    {
      if (file_exists($this->location . '/segments.gen'))
      {
        $this->index = Zend_Search_Lucene::open($this->location);
      }
      else
      {
        $this->index = Zend_Search_Lucene::create($this->location);
      }
    }
  }

  /**
   * @see xfEngine
   */
  public function close()
  {
    unset($this->index);
    $this->index = null;
  }

  /**
   * Commits changes to the index (Zend_Search_Lucene specific)
   */
  public function commit()
  {
    $this->getIndex()->commit();
  }

  /**
   * @see xfEngine
   */
  public function erase()
  {
    if ($this->index)
    {
      $this->index = Zend_Search_Lucene::create($this->location);
    }
    else
    {
      foreach (new DirectoryIterator($this->location) as $file)
      {
        if (!$file->isDot())
        {
          unlink($file->getRealpath());
        }
      }
    }
  }

  /**
   * @see xfEngine
   */
  public function optimize()
  {
    $this->getIndex()->optimize();
  }

  /**
   * @see xfEngine
   */
  public function find(xfCriterion $criteria)
  {
    $zquery = xfLuceneCriterionRewriter::rewrite($criteria);
    $hits = $this->getIndex()->find($zquery);

    $implementer = new xfLuceneCriterionImplementer($criteria, $zquery);

    return new xfLuceneHits($this, $hits, $implementer);
  }

  /**
   * @see xfEngine
   */
  public function findGuid($guid)
  {
    $index = $this->getIndex();

    $term = new Zend_Search_Lucene_Index_Term($guid, '_guid');
    $docs = $index->termDocs($term);

    if (count($docs))
    {
      $doc = $index->getDocument($docs[0]);

      return $this->unwriteDocument($doc);
    }
    else
    {
      throw new xfEngineException('GUID "' . $guid . '" could not be found in Zend_Search_Lucene index');
    }
  }

  /**
   * @see xfEngine
   */
  public function add(xfDocument $doc)
  {
    $this->getIndex()->addDocument($this->rewriteDocument($doc));

    foreach ($doc->getChildren() as $child)
    {
      $this->add($child);
    }
  }

  /**
   * Unrewrites a Zend_Search_Lucene document into a xfDocument
   *
   * @param Zend_Search_Lucene_Document $zdoc
   * @returns xfDocument
   */
  public function unwriteDocument(Zend_Search_Lucene_Document $zdoc)
  {
    $doc = new xfDocument($zdoc->getFieldValue('_guid'));

    $boosts = unserialize($zdoc->getFieldValue('_boosts'));

    foreach ($zdoc->getFieldNames() as $name)
    {
      if (substr($name, 0, 1) == '_')
      {
        // internal field, deal with later
        continue;
      }

      $zfield = $zdoc->getField($name);

      $type = 0;

      if ($zfield->isStored)
      {
        $type |= xfField::STORED;
      }
      if ($zfield->isIndexed)
      {
        $type |= xfField::INDEXED;
      }
      if ($zfield->isTokenized)
      {
        $type |= xfField::TOKENIZED;
      }
      if ($zfield->isBinary)
      {
        $type |= xfField::BINARY;
      }

      $field = new xfField($name, $type);
      $field->setBoost($boosts[$name]);

      $value = new xfFieldValue($field, $zfield->value);
      $doc->addField($value);
    }

    foreach (unserialize($zdoc->getFieldValue('_sub_documents')) as $guid)
    {
      $doc->addChild($this->findGuid($guid));
    }

    return $doc;
  }

  /**
   * Rewrites a xfDocument into a Zend_Search_Lucene document
   *
   * @param xfDocument $doc The document
   * @returns Zend_Search_Lucene_Document
   */
  public function rewriteDocument(xfDocument $doc)
  {
    $zdoc = new Zend_Search_Lucene_Document;
    $zdoc->addField(Zend_Search_Lucene_Field::Keyword('_guid', $doc->getGuid()));
    $zdoc->boost = $doc->getBoost();

    $boosts = array();

    foreach ($doc->getFields() as $field)
    {
      $type = $field->getField()->getType();

      $zfield = new Zend_Search_Lucene_Field(
        $field->getField()->getName(),
        $field->getValue(),
        $field->getEncoding(),
        ($type & xfField::STORED) > 0,
        ($type & xfField::INDEXED) > 0,
        ($type & xfField::TOKENIZED) > 0,
        ($type & xfField::BINARY) > 0
        );
      $zfield->boost = $field->getField()->getBoost();

      $zdoc->addField($zfield);

      $boosts[$field->getField()->getName()] = $field->getField()->getBoost();
    }

    $childrenGuids = array();
    foreach ($doc->getChildren() as $child)
    {
      $childrenGuids[] = $child->getGuid();
    }
    $zdoc->addField(Zend_Search_Lucene_Field::UnIndexed('_sub_documents', serialize($childrenGuids)));
    $zdoc->addField(Zend_Search_Lucene_Field::UnIndexed('_boosts', serialize($boosts)));

    return $zdoc;
  }
  
  /**
   * @see xfEngine
   */
  public function delete($guid)
  {
    $index = $this->getIndex();

    $term = new Zend_Search_Lucene_Index_Term($guid, '_guid');

    foreach ($index->termDocs($term) as $id)
    {
      $index->delete($id);
    }
  }

  /**
   * @see xfEngine
   */
  public function count()
  {
    // we use ->numDocs() because ->count() counts deleted documents
    return $this->getIndex()->numDocs();
  }

  /**
   * Gets index instance.
   *
   * @returns Zend_Search_Lucene_Interface The raw index
   */
  public function getIndex()
  {
    $this->check();
    $this->bind();

    return $this->index;
  }

  /**
   * Checks to see if index is open and throws exception if its closed
   * 
   * @throws xfLuceneException if index is closed
   */
  private function check()
  {
    if (!$this->index)
    {
      throw new xfLuceneException('Index is closed');
    }
  }

  /**
   * Binds the current configuration
   */
  private function bind()
  {
    Zend_Search_Lucene_Analysis_Analyzer::setDefault($this->analyzer);
  }
}
