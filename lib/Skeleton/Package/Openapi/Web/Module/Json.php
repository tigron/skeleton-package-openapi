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

class Json extends Module {

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
	 * Display method
	 *
	 * @access public
	 */
	public function display() {
		$application = \Skeleton\Core\Application::get();

		\Skeleton\Package\Openapi\Path::get_by_application($application);
		die();

		$config = $application->config;

		$swagger = [];
		$swagger['swagger'] = '2.0';
		$swagger['paths'] = $this->generate_paths();

		echo json_encode($swagger, JSON_PRETTY_PRINT);
	}

	private function generate_info() {

	}

	private function generate_paths() {
		$application = \Skeleton\Core\Application::get();
		$module_path = $application->module_path;
		$files = $this->recursive_scan($module_path);

		$modules = [];
		foreach ($files as $file) {
			require_once $file;
			$module_name = str_replace($module_path, '', $file);
			$module_name = str_replace('.php', '', $module_name);
			if ($module_name[0] == '/') {
				$module_name = substr($module_name, 1);
			}
			$module_name = str_replace('/', '_', $module_name);

			$classname = '\Web_Module_' . $module_name;

			if (class_exists($classname) ) {
				$module = new $classname;

				if (is_a($module, '\Skeleton\Package\Openapi\Web\Module\Call')) {
					$modules[] = $module;
				}
			}
		}

		$config = $application->config;
		$routes = $config->routes;

		$paths = [];

		foreach ($modules as $module) {
			$path = $module->get_module_path();
			if ($path != '/supplier') {
				continue;
			}

			$calls = $module->get_calls();
print_R($calls);

			foreach ($calls as $call) {

			}
		}


	}

	private function generate_definitions() {

	}

	/**
	 * Recursive scan a directory
	 *
	 * @access private
	 * @param string $directory
	 * @return array $files
	 */
	private function recursive_scan($directory) {
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
