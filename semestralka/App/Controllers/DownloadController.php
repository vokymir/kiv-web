<?php

namespace App\Controllers;

use App\Config\Config;
use App\Core\Controller;
use App\Core\Flash;

class DownloadController extends Controller
{
	// show download page for file if exists
	public function pdf(string $filename): void
	{
		$filePath = Config::UPLOAD_DIR . $filename;

		if (!file_exists($filePath)) {
			Flash::set('error', 'File not found.');
			self::redirect('error');
		}

		// force the browser to display PDF inline
		header('Content-Type: application/pdf');
		header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
		header('Content-Transfer-Encoding: binary');
		header('Accept-Ranges: bytes');
		header('Content-Length: ' . filesize($filePath));

		// clear output buffer before reading the file
		if (ob_get_level()) {
			ob_end_clean();
		}

		readfile($filePath);
		exit;
	}
}
