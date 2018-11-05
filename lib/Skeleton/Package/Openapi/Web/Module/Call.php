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
			if ($method->isStatic()) {
				continue;
			}
			$http_methods = [ 'get', 'post', 'put', 'delete' ];

			foreach ($http_methods as $http_method) {

				if (strpos(strtolower($method->name), $http_method) !== 0) {
					continue;
				}

				if ($method->getDeclaringClass()->getName() != get_class($this)) {
					continue;
				}

				$call = [];
				$parts = explode('_', $method->name);
				$call['http_method'] = $parts[0];
				if (isset($parts[1])) {
					$call['action'] = $parts[1];
				}
				$path = $this->get_module_path();

				// We will try to fake a url in order to match a route via revere rewrite
				$params = [];
				if (isset($call['action'])) {
					$params['action'] = $call['action'];
				}

				$comments = $method->getDocComment();
				$factory  = \phpDocumentor\Reflection\DocBlockFactory::createInstance();
				$docblock = $factory->create($comments);
				$docblock_params = $docblock->getTagsByName('param');

				foreach ($docblock_params as $docblock_param) {
					$params[$docblock_param->getVariableName()] = $docblock_param->getVariableName();
				}

				if (count($params) > 0) {
					$call['path'] = \Skeleton\Core\Util::rewrite_reverse($path . '?' . http_build_query($params));
				} else {
					$call['path'] = \Skeleton\Core\Util::rewrite_reverse($path);
				}

				$call['summary'] = (string)$docblock->getSummary();
				$call['description'] = (string)$docblock->getDescription();
				$call['tags'] = $this->get_module_path();
				$call['operationId'] = $this->get_module_path() . '/' . $method->name;
				$call['parameters'] = [];
				foreach ($docblock_params as $docblock_param) {
					$param = [];
					$param['name'] = $docblock_param->getVariableName();
				}


				$result[] = $call;
			}
		}
		ksort($result);
		return $result;
	}



	private static function recursive_scan($path) {
		$files = scandir($directory);
		$result = [];
		foreach ($files as $key => $value) {
			if ($value[0] == '.') {
				unset($files[$key]);
				continue;
			}

			if (is_dir($directory . '/' . $value)) {
				$result = array_merge($result, $this->recursive_scan($directory . '/' . $value));
				continue;
			}

			$result[] = $directory . '/' . $value;
		}
		return $result;
	}


}
