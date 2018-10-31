<?php
/**
 * Module Index
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 */

namespace Skeleton\Package\Openapi\Web\Module;

use \Skeleton\Core\Web\Module;

abstract class Call extends Module {

	/**
	 * Login required ?
	 * Default = yes
	 *
	 * @access public
	 * @var bool $login_required
	 */
	public $login_required = false;

	/**
	 * Template to use
	 *
	 * @access public
	 * @var string $template
	 */
	public $template = false;

	/**
	 * Display
	 *
	 * Dispatches the call to the corresponding method
	 *
	 * @access public
	 */
	public function display() {
		if (!isset($_REQUEST['call'])) {
			$this->display_404();
		}

		if (!is_callable( [ $this, 'call_' . $_REQUEST['call'] ])) {
			$this->display_404();
		}

		if (empty($_REQUEST['api_key'])) {
			$this->display_403();
		}

		try {
			$response = call_user_func_array( [$this, 'call_' . $_REQUEST['call'] ], []);
		} catch (\Exception $e) {
			$response = 'Exception: ' . $e->getMessage();
		}

		if (!isset($_REQUEST['api_output']) OR $_REQUEST['api_output'] == '') {
			$_REQUEST['api_output'] = 'print_r';
		}

		if ($_REQUEST['api_output'] == 'print_r') {
			print_r($response);
		} elseif ($_REQUEST['api_output'] == 'json') {
			echo json_encode($response, JSON_PRETTY_PRINT);
		} elseif ($_REQUEST['api_output'] == 'serialize') {
			echo serialize($response);
		}
	}

	/**
	 * Get the calls
	 *
	 * @access public
	 * @return array $calls
	 */
	public function get_calls() {
		$class = new \ReflectionClass($this);
		$methods = $class->getMethods();
		$result = [];

		foreach ($methods as $method) {
			if (strpos($method->name, 'call_') !== 0) {
				continue;
			}
			$method_name = str_replace('call_', '', $method->name);
			$comments = $method->getDocComment();
			$factory  = \phpDocumentor\Reflection\DocBlockFactory::createInstance();
			$docblock = $factory->create($comments);

			$result[$method_name] = $docblock;
		}
		ksort($result);
		return $result;
	}

	/**
	 * Show 404
	 *
	 * @access private
	 */
	private function display_404() {
		header("HTTP/1.0 404 Not Found");
		echo '404: not found';
		exit;
	}

	/**
	 * Show 404
	 *
	 * @access private
	 */
	private function display_403() {
		header("HTTP/1.0 403 Forbidden");
		echo '403: no allowed';
		exit;
	}
}
