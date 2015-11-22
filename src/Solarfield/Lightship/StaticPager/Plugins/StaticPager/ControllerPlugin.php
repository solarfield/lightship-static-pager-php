<?php
namespace Solarfield\Lightship\StaticPager\Plugins\StaticPager;

use Exception;
use App\Environment as Env;
use Ok\StructUtils;

class ControllerPlugin extends \Solarfield\Lightship\Pager\PagerControllerPlugin {
	protected function getPagesDirectoryFilePath() {
		static $path;

		if ($path === null) {
			$value = $this->getOptions()->get('pagesDirectoryFilePath');

			if (!$value) {
				throw new Exception(
					"The '" . $this->getInstallationCode() . ".pagesDirectoryFilePath' option is required."
				);
			}

			$path = realpath($value);

			if (!$path) {
				throw new Exception(
					"'$value' does not exist."
				);
			}

			if (!is_dir($path)) {
				throw new Exception(
					"'$path' is not a directory."
				);
			}
		}

		return $path;
	}

	public function getPagesList() {
		static $pages;

		if ($pages === null) {
			$pagesDirPath = $this->getPagesDirectoryFilePath();

			$indexFilePath = $pagesDirPath . '/index.php';
			if (!file_exists($indexFilePath)) {
				throw new Exception(
					"Page index.json was not found at: '" . $pagesDirPath . "/index.json'."
				);
			}

			/** @noinspection PhpIncludeInspection */
			$index = include($indexFilePath);

			$pages = $index['pages'];
		}

		return $pages;
	}

	public function getFullPage($aCode) {
		static $page;
		static $cacheCode = false;

		if ($aCode !== $cacheCode) {
			$pagesDirPath = $this->getPagesDirectoryFilePath();

			if (preg_match('/^[a-z\-]+$/', $aCode) !== 1) {
				throw new Exception(
					"Invalid page code: '" . $aCode . "'."
				);
			}

			$page = $this->getPagesMap()['lookup'][$aCode];

			$indexFilePath = $pagesDirPath . '/pages/' . $aCode . '/details.php';
			if (file_exists($indexFilePath)) {
				/** @noinspection PhpIncludeInspection */
				$details = include($indexFilePath);

				$page = StructUtils::merge($page, $details['page']);
			}
		}

		return $page;
	}

	public function handleResolveOptions() {
		$options = $this->getController()->getOptions();

		$options->add($this->getInstallationCode() . '.pagesDirectoryFilePath', Env::getVars()->get('projectPackageFilePath') . '/files/static-pager');
	}

	public function __construct(\Batten\ControllerInterface $aController, $aComponentCode, $aInstallationCode) {
		parent::__construct($aController, $aComponentCode, $aInstallationCode);

		$this->getController()->addEventListener('app-resolve-options', [$this, 'handleResolveOptions']);
	}
}
