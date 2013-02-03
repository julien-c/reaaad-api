<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/config.php';

$app = new Silex\Application();
$app['debug'] = true;

/***
 *
 * Accepting JSON in request body.
 * @note: the method described in http://silex.sensiolabs.org/doc/cookbook/json_request_body.html doesn't allow us to get the whole parameter array.
 *
 */

$app->before(function (Request $request) use ($app) {
	if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
		$app['data'] = json_decode($request->getContent(), true);
	}
});


/***
 *
 * Endpoints.
 * @see https://github.com/okfn/annotator/wiki/Storage
 *
 */


$app->get('/', function () use ($app) {
	$out = array(
		'name'    => "Annotator Store API (PHP)",
		'version' => '1.0.0',
		'author'  => 'julien-c'
	);
	return $app->json($out);
});


$app->get('/sentence', function (Request $request) use ($app) {
	$component = $request->get('component');
	$sentence = $request->get('sentence');
	
	$out = array();
	
	$m = new Mongo();
	$c = $m->reaaad->comments->find(array(
		'component' => $component,
		'sentence' => $sentence
	));
	
	foreach($c as $post) {
		$post['id'] = (string) $post['_id'];
		unset($post['_id']);
		
		$post['user']['md5'] = md5(trim($post['user']['email']));
		
		$out[] = $post;
	}
	
	return $app->json($out);
});


$app->post('/comments', function () use ($app) {
	$post = $app['data'];
	$post['time'] = new MongoDate();
	
	$m = new Mongo();
	$m->reaaad->comments->insert($post, array('safe' => true));
	
	$post['id'] = (string) $post['_id'];
	unset($post['_id']);
	
	return $app->json($post);
});


$app->get('/component', function (Request $request) use ($app) {
	$component = $request->get('component');
	
	$sentences = array();
	
	$m = new Mongo();
	$c = $m->reaaad->comments->find(array(
		'component' => $component
	));
	
	foreach($c as $post) {
		if (!isset($sentences[$post['sentence']])) {
			$sentences[$post['sentence']] = 1;
		}
		else {
			$sentences[$post['sentence']]++;
		}
	}
	
	return $app->json($sentences);
});





/***
 *
 * Run, App, Run!
 *
 */


$app->run();