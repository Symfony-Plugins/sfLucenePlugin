<?php
/**
 * This file is part of the sfLucene package.
 * (c) Carl Vondrick <carl.vondrick@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

set_include_path(realpath(dirname(__FILE__) . '/../vendor/') . PATH_SEPARATOR . get_include_path());

// this is an ugly hack, but there's a bug somewhere with sfSimpleAutoload,
// Zend_Search_Lucene, and PHP.
require_once 'Zend/Search/Lucene.php';

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
   * The sfLucene version
   */
  const VERSION = '0.5-DEV';

  /**
   * The Zend_Search_Lucene version
   */
  const LUCENE_VERSION = '1.5';

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
   * @var Zend_Search_Lucene_Analysis_Analyzer
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

    Zend_Search_Lucene_Storage_Directory_Filesystem::setDefaultFilePermissions(0666);
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
      $fs = new xfLuceneEnhancedFilesystem($this->location);

      if (file_exists($this->location . '/segments.gen'))
      {
        $this->index = Zend_Search_Lucene::open($fs);
      }
      else
      {
        $this->index = Zend_Search_Lucene::create($fs);
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
    elseif (is_dir($this->location))
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
    $this->bind();

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

    $term = new Zend_Search_Lucene_Index_Term($guid, '__guid');
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
    $doc = new xfDocument($zdoc->getFieldValue('__guid'));

    $boosts = unserialize($zdoc->getFieldValue('__boosts'));

    foreach ($zdoc->getFieldNames() as $name)
    {
      // ignore internal fields
      if (substr($name, 0, 2) != '__')
      {
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
    }

    foreach (unserialize($zdoc->getFieldValue('__sub_documents')) as $guid)
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
    $zdoc->addField(Zend_Search_Lucene_Field::Keyword('__guid', $doc->getGuid()));
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
    $zdoc->addField(Zend_Search_Lucene_Field::UnIndexed('__sub_documents', serialize($childrenGuids)));
    $zdoc->addField(Zend_Search_Lucene_Field::UnIndexed('__boosts', serialize($boosts)));

    return $zdoc;
  }
  
  /**
   * @see xfEngine
   */
  public function delete($guid)
  {
    $index = $this->getIndex();

    $term = new Zend_Search_Lucene_Index_Term($guid, '__guid');

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
   * @see xfEngine
   */
  public function describe()
  {
    $this->open();

    $aclass = get_class($this->analyzer);
    if (substr($aclass, 0, 44) == 'Zend_Search_Lucene_Analysis_Analyzer_Common_')
    {
      $aclass = substr($aclass, 44);
    }

    return array(
      'Engine'            => 'sfLucene ' . self::VERSION,
      'Implementation'    => 'Zend_Search_Lucene ' . self::LUCENE_VERSION,
      'Location'          => $this->location,
      'Total Documents'   => $this->count(),
      'Total Segments'    => $this->getSegmentCount(),
      'Total Size'        => round($this->getByteSize() / 1024 / 1024, 3) . ' MB',
      'Analyzer'          => $aclass
    );
  }

  /**
   * Gets the byte size of the index.
   *
   * @returns int The size in bytes
   */
  public function getByteSize()
  {
    $size = 0;
    foreach (new DirectoryIterator($this->location) as $node)
    {
      if (!in_array($node->getFilename(), array('CVS', '.svn', '_svn')))
      {
        $size += $node->getSize();
      }
    }

    return $size;
  }

  /**
   * Gets the number of segments
   *
   * @returns int
   */
  public function getSegmentCount()
  {
    return count(glob($this->location . DIRECTORY_SEPARATOR . '_*.cfs'));
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
