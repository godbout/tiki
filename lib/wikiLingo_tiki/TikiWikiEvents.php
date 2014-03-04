<?php


class TikiWikiEvents {

	public $title;
	public $version;
	public $body;

	public function __construct($title, $version, $body)
	{
		$this->title = $title;
		$this->version = $version;
		$this->body = $body;
	}

	public function listen() {
		//listener start
		if (isset($_POST['protocol'])) {
			echo FLP\Service\Receiver::receive();
			exit;
		}
		//listener end
	}

	public function direct() {
		//redirect start
		if (isset($_GET['phrase'])) {
			$phrase = (!empty($_GET['phrase']) ? $_GET['phrase'] : '');
		}

		//start session if that has not already been done
		if (!isset($_SESSION)) {
			session_start();
		}

		//recover from redirect if it happened
		if (!empty($_SESSION['phrase'])) {
			$phrase = $_SESSION['phrase'];
			unset($_SESSION['phrase']);

		}

		//check if versions are same, if they are not, redirect to where they do
		else if (!empty($phrase)) {
			$revision = FLP\Data::getRevision($phrase);
			//check if this version (latest) is
			if ($this->version != $revision->version) {
				//prep for redirect
				$_SESSION['phrase'] = $phrase;
				header('Location: ' . TikiLib::tikiUrl() . 'tiki-pagehistory.php?page=' . $revision->title . '&preview=' . $revision->version . '&nohistory');
				exit();
			}
		}
	}

	public function load() {
		global $smarty;
		//standard page
		$parsed = $smarty->getTemplateVars('parsed');
		if (!empty($parsed)) {
			$ui = new FLP\UI($parsed);
			FLP\Data::GetPairsByTitleAndApplyToUI($this->title, $ui);
			$parsed = $ui->render();
			$smarty->assign('parsed', $parsed);
		} else {
			//history
			$previewd = $smarty->getTemplateVars('previewd');
			if (!empty($previewd)) {
				$ui = new FLP\UI($previewd);
				FLP\Data::GetPairsByTitleAndApplyToUI($this->title, $ui);
				$previewd = $ui->render();
				$smarty->assign('previewd', $previewd);
			}
		}
	}
} 