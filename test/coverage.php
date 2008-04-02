<?php
/**
 * This file is part of the sfSearch package.
 * (c) Carl Vondrick <carl.vondrick@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once dirname(__FILE__) . '/bootstrap/unit.php';
require_once 'util/sfFinder.class.php';

$h = new lime_harness(new lime_output_color);
$h->base_dir = realpath(dirname(__FILE__) . '/unit');
$h->register(sfFinder::type('file')->name('*Test.php')->in($h->base_dir));

$c = new lime_coverage($h);
$c->extension = '.class.php';
$c->verbose = true;
$c->base_dir = realpath(dirname(__FILE__) . '/../lib');

$c->register(sfFinder::type('file')->name('*.class.php')->prune('vendor')->in($c->base_dir));
$c->run();
