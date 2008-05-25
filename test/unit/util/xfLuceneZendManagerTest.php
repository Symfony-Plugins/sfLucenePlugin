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

$t = new lime_test(2, new lime_output_color);

function has()
{
  $files = get_included_files();

  foreach ($files as $file)
  {
    if (false !== strpos($file, '/Zend/Search/Lucene.php'))
    {
      return true;
    }
  }
  
  return false;
}

function inpath()
{
  $paths = explode(PATH_SEPARATOR, get_include_path());

  foreach ($paths as $path)
  {
    if (false !== strpos($path, '/lib/vendor'))
    {
      return true;
    }
  }
  
  return false;
}

xfLuceneZendManager::load();
$t->ok(has(), '::load() loads Zend_Search_Lucene');
$t->ok(inpath(), '::load() configures the include path');
