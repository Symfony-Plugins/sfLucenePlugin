<?php
/*
 * This file is part of the sfLucenePlugin package
 * (c) 2007 Carl Vondrick <carlv@carlsoft.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
  * @package sfLucenePlugin
  * @subpackage Test
  * @author Carl Vondrick
  * @version SVN: $Id$
  */

require dirname(__FILE__) . '/../../bootstrap/unit.php';

$t = new lime_test(5, new lime_output_color());

class FooListener
{
  public $event;

  public function listen($event)
  {
    $this->event = $event;
  }
}

$source = new sfEventDispatcher();
$target = new sfEventDispatcher();

$connector = new sfLuceneEventConnector($source, 'foo', $target, 'bar');

$t->ok($source->hasListeners('foo'), '__construct() connects a listener to the source');

$subject = 'Fabien';
$params = array('a', 'b', 'c');

$listener = new FooListener;
$target->connect('bar', array($listener, 'listen'));

$source->notify(new sfEvent($subject, 'foo', $params));

$t->isa_ok($listener->event, 'sfEvent', 'calling a linked event calls target');
$t->is($listener->event->getSubject(), $subject, 'calling a linked event sends correct subject');
$t->is($listener->event->getName(), 'bar', 'calling a linked event sends correct name');
$t->is($listener->event->getParameters(), $params, 'calling a linked event sends correct parameters');