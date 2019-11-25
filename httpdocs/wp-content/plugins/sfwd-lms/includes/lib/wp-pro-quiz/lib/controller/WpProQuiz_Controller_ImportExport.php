<?php
class WpProQuiz_Controller_ImportExport extends WpProQuiz_Controller_Controller {
	
	public function route() {
		
		@set_time_limit(0);
		@ini_set('memory_limit', '128M');
		
		if(!isset($_GET['action']) || $_GET['action'] != 'import' && $_GET['action'] != 'export') {
			wp_die("Error");
		}
		
		if($_GET['action'] == 'export') {
			$this->handleExport();
		} else {
			$this->handleImport();
		}
	}
	
	private function handleExport() {
		
		if(!current_user_can('wpProQuiz_export')) {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}
		
		if(isset($this->_post ['exportType']) && $this->_post ['exportType'] == 'xml') {
			$export = new WpProQuiz_Helper_ExportXml();
			$filename = 'WpProQuiz_export_'.time().'.xml';
		} else {
			$export = new WpProQuiz_Helper_Export();
			$filename = 'WpProQuiz_export_'.time().'.wpq';
		}
		
		$a = $export->export($this->_post['exportIds']);
		
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="'.$filename.'"');
		
		echo $a;
		
		exit;
	}
	
	private function handleImport() {
		
		if(!current_user_can('wpProQuiz_import')) {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}
		
		$this->view = new WpProQuiz_View_Import();
		$this->view->error = false;
		
		if(isset($_FILES, $_FILES['import']) && substr($_FILES['import']['name'], -3) == 'xml' || isset($this->_post['importType']) && $this->_post['importType'] == 'xml') {
			$import = new WpProQuiz_Helper_ImportXml();
			$importType = 'xml';			
		} else {
			$import = new WpProQuiz_Helper_Import();
			$importType = 'wpq';			
		}
		
		$this->view->importType = $importType;
		
		if(isset($_FILES, $_FILES['import']) && $_FILES['import']['error'] == 0) {
			if($import->setImportFileUpload($_FILES['import']) === false) {
				$this->view->error = $import->getError();
			} else {
				$data = $import->getImportData();
				
				if($data === false) {
					$this->view->error = $import->getError();
				}

				$this->view->import = $data;
				$this->view->importData = $import->getContent();
				
				unset($data);
			}
		} else if(isset($this->_post, $this->_post['importSave'])) {
			if($import->setImportString($this->_post['importData']) === false) {
				$this->view->error = $import->getError();
			} else {
				$ids = isset($this->_post['importItems']) ? $this->_post['importItems'] : false;
				
				if($ids !== false && $import->saveImport($ids) === false) {
					$this->view->error = $import->getError();
				} else {
					$this->view->finish = true;
					$this->view->import_post_id = absint( $import->import_post_id );
				}			
			}
		} else {
			$this->view->error = esc_html__('File cannot be processed', 'learndash');
		}
		
		$this->view->show();
	}
}