<?php
namespace Solarfield\StaticPager\Plugins\StaticPager;

use Exception;
use App\Environment as Env;
use Solarfield\Ok\StructUtils;

class ControllerPlugin extends \Solarfield\Pager\PagerControllerPlugin {
	private $pagesDirFilePath;
	private $pagesList;
	private $fullPage;
	private $fullPageCacheKey;

	protected function getPagesDirectoryFilePath() {
		if ($this->pagesDirFilePath === null) {
			$value = $this->getOptions()->get('pagesDirectoryFilePath');

			if (!$value) {
				throw new Exception(
					"The '" . $this->getInstallationCode() . ".pagesDirectoryFilePath' option is required."
				);
			}

			$this->pagesDirFilePath = realpath($value);

			if (!$this->pagesDirFilePath) {
				throw new Exception(
					"'$value' does not exist."
				);
			}

			if (!is_dir($this->pagesDirFilePath)) {
				throw new Exception(
					"'{$this->pagesDirFilePath}' is not a directory."
				);
			}
		}

		return $this->pagesDirFilePath;
	}

	public function getPagesList() {
		if ($this->pagesList === null) {
			$pagesDirPath = $this->getPagesDirectoryFilePath();

			$indexFilePath = $pagesDirPath . '/index.php';
			if (!file_exists($indexFilePath)) {
				throw new Exception(
					"'$pagesDirPath/index.php' was not found."
				);
			}

			/** @noinspection PhpIncludeInspection */
			$index = include($indexFilePath);

			$this->pagesList = $index['pages'];
		}

		return $this->pagesList;
	}

	public function getFullPage($aCode) {
		$currentKey = $aCode;

		if ($currentKey !== $this->fullPageCacheKey) {
			$this->fullPage = null;
			$this->fullPageCacheKey = $currentKey;

			$pagesDirPath = $this->getPagesDirectoryFilePath();

			if (preg_match('/^[a-z\-_]+$/i', $aCode) !== 1) {
				throw new Exception(
					"Invalid page code: '" . $aCode . "'."
				);
			}

			$this->fullPage = $this->getPagesMap()['lookup'][$aCode];

			$indexFilePath = $pagesDirPath . '/pages/' . $aCode . '/details.php';
			if (file_exists($indexFilePath)) {
				/** @noinspection PhpIncludeInspection */
				$details = include($indexFilePath);

				$this->fullPage = StructUtils::merge($this->fullPage, $details['page']);
			}
		}

		return $this->fullPage;
	}

	public function handleResolveOptions() {
		$options = $this->getOptions();

		$options->add('pagesDirectoryFilePath', Env::getVars()->get('projectPackageFilePath') . '/libs/static-pager');
	}

	public function __construct(\Solarfield\Batten\ControllerInterface $aController, $aComponentCode, $aInstallationCode) {
		parent::__construct($aController, $aComponentCode, $aInstallationCode);

		$this->getController()->addEventListener('resolve-options', [$this, 'handleResolveOptions']);
	}
}
