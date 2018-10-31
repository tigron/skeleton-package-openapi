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

				if (is_a($module, '\Skeleton\Package\Api\Web\Module\Call')) {
					$modules[] = $module;
				}
			}
		}

		$template = \Skeleton\Core\Web\Template::get();
		$template->assign('modules', $modules);
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
