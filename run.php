<?php

require __DIR__ . '/vendor/autoload.php';

define('SERVICE_ACCOUNT_FILE', 'service-account.json');
define('OLD_FOLDER', '...');
define('NEW_FOLDER', '...');

class Copy_Folder {

	private $service;

	public function __construct() {
		$client = new Google\Client();
		$client->setAuthConfig(SERVICE_ACCOUNT_FILE);
		$client->setScopes(Google\Service\Drive::DRIVE);

		$this->service = new Google\Service\Drive($client);
	}

	public function get_tree($parent) {
		$files = [];
		$next_page_token = null;
		do {
			$params = [
				'includeItemsFromAllDrives' => true,
				'supportsAllDrives' => true,
				'fields' => '*',
				'q' => "'{$parent}' in parents",
				'pageSize' => 1000,
			];
			if($next_page_token) {
				$params['pageToken'] = $next_page_token;
			}
			$results = $this->service->files->listFiles($params);
			$next_page_token = $results->getNextPageToken();
			
			if($results->getFiles()) {
				foreach($results->getFiles() as $file) {
					$files[$file->id] = [
						'name' => $file->name,
						'parent' => $parent,
						'mimeType' => $file->mimeType,
					];
					if($file->mimeType === 'application/vnd.google-apps.folder') {
						$files = array_merge($files, $this->get_tree($file->id));
					}
				}
			}
		} while($next_page_token);
		return $files;
	}

	public function create_folder($settings, $parent) {
		$file = new Google\Service\Drive\DriveFile();
		$file->setName($settings['name']);
		$file->setMimeType($settings['mimeType']);
		$file->setParents([$parent]);
		$results = $this->service->files->create($file);
		return $results->getId();
	}

	public function copy_file($file_id, $settings, $parent) {
		$file = new Google\Service\Drive\DriveFile();
		$file->setName($settings['name']);
		$file->setMimeType($settings['mimeType']);
		$file->setParents([$parent]);
		$results = $this->service->files->copy($file_id, $file);
		return $results->getId();
	}

}

$service = new Copy_Folder();
$tree = $service->get_tree(OLD_FOLDER);
$new_tree = [OLD_FOLDER => NEW_FOLDER];
foreach($tree as $id => $file) {
	if($file['mimeType'] === 'application/vnd.google-apps.folder') {
		$new_tree[$id] = $service->create_folder($file, $new_tree[$file['parent']]);
	} else {
		$new_tree[$id] = $service->copy_file($id, $file, $new_tree[$file['parent']]);
	}
}
foreach($new_tree as $old => $new) {
	echo "{$old}\t{$new}\n";
}
