<?php
namespace Solarfield\StaticPager\Plugins\StaticPager;

use Exception;
use App\Environment as Env;
use Solarfield\Ok\StructUtils;

class ControllerPlugin extends \Solarfield\Pager\PagerControllerPlugin {
	private $pagesDirFilePath;

	protected function getPagesDirectoryFilePath() {
		if ($this->pagesDirFilePath === null) {
			$value = $this->getController()->getOptions()->get('pagerPlugin.pagesDirectoryFilePath');

			if (!$value) {
				throw new Exception(
					"The 'pagerPlugin.pagesDirectoryFilePath' option is required."
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

	protected function loadStubPages() {
		$pagesDirPath = $this->getPagesDirectoryFilePath();

		$indexFilePath = $pagesDirPath . '/index.php';

		if (!file_exists($indexFilePath)) {
			throw new Exception(
				"'$pagesDirPath/index.php' was not found."
			);
		}

		$index = (function () use ($indexFilePath) {return require($indexFilePath);})();

		return $index['pages'];
	}

	protected function loadFullPage($aCode) {
		$fullPage = null;

		$pagesDirPath = $this->getPagesDirectoryFilePath();

		if (preg_match('/^[a-z\-_]+$/i', $aCode) !== 1) {
			throw new Exception(
				"Invalid page code: '" . $aCode . "'."
			);
		}

		$fullPage = $this->getStubPage($aCode);

		$indexFilePath = $pagesDirPath . '/pages/' . $aCode . '/details.php';
		if (file_exists($indexFilePath)) {
			$details = (function () use ($indexFilePath) {return require($indexFilePath);})();

			$fullPage = StructUtils::merge($fullPage, $details['page']);
		}

		return $fullPage;
	}

	public function handleResolveOptions() {
		$options = $this->getController()->getOptions();

		$options->add('pagerPlugin.pagesDirectoryFilePath', Env::getVars()->get('projectPackageFilePath') . '/libs/static-pager');
	}

	public function __construct(\Solarfield\Lightship\ControllerInterface $aController, $aComponentCode) {
		parent::__construct($aController, $aComponentCode);

		$this->getController()->addEventListener('resolve-options', [$this, 'handleResolveOptions']);
	}
}
