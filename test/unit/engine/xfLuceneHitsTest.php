<?php

require dirname(__FILE__) . '/../../bootstrap/unit.php';
require 'engine/xfLuceneHits.class.php';
require 'engine/xfEngine.interface.php';
require 'engine/xfLuceneEngine.class.php';
require 'criteria/xfCriterion.interface.php';
require 'criteria/xfCriterionString.class.php';
require 'criteria/xfCriterionImplementer.interface.php';
require 'criteria/xfLuceneCriterionImplementer.class.php';
require 'result/xfDocumentHit.class.php';
require 'document/xfDocument.class.php';
require 'document/xfField.class.php';
require 'document/xfFieldValue.class.php';
require 'vendor/Zend/Search/Lucene.php';

$t = new lime_test(13, new lime_output_color);

$doc = new xfDocument('foobar');
$doc->addField(new xfFieldValue(new xfField('title', xfField::TEXT), 'foobar'));
$engine = new xfLuceneEngine(dirname(__FILE__) . '/../../sandbox/index');
$engine->erase();
$engine->open();
$engine->add($doc);
$engine->commit();

$zhits = $engine->getIndex()->find('foobar');

$implementer = new xfLuceneCriterionImplementer(new xfCriterionString('foobar'), Zend_Search_Lucene_Search_QueryParser::parse('foobar'));

$hits = new xfLuceneHits($engine, $zhits, $implementer);

$t->diag('->current()');
$r = $hits->current();
$t->isa_ok($r, 'xfDocumentHit', '->current() returns an xfDocumentHit');
$t->is($r->getOption('score'), $zhits[0]->score, '->current() returns an xfDocumentHit with correct score');
$t->is($r->getOption('id'), $zhits[0]->id, '->current() returns an xfDocumentHit with correct id');
$t->is($r->getCriterionImplementer(), $implementer, '->current() returns an xfDocument bound to the implementer');
$t->is($r->getDocument()->getField('title')->getValue(), 'foobar', '->current() communicates with the unwriter correctly');
$t->ok($r === $hits->current(), '->current() caches the response');

$t->diag('->key(), ->next(), ->valid(), ->rewind(), ->seek()');
$t->is($hits->key(), 0, '->key() returns the current key');
$t->ok($hits->valid(), '->valid() returns true if key exists');
$hits->next();
$t->is($hits->key(), 1, '->next() advances the key by one');
$t->ok(!$hits->valid(), '->valid() returns false if key does not exist');
$hits->rewind();
$t->is($hits->key(), 0, '->rewind() resets the pointer');
$hits->seek(5);
$t->is($hits->key(), 5, '->seek() advances the pointer');

$t->diag('->count()');
$t->is($hits->count(), 1,' ->count() counts the number of hits');
