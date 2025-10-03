<?php

namespace App\Controllers;

use App\Config\Config;

class DownloadController
{
	public function pdf(string $filename): void
	{
		$filePath = Config::UPLOAD_DIR . $filename;

		if (!file_exists($filePath)) {
			http_response_code(404);
			exit("File not found");
		}

		// Force the browser to display PDF inline
		header('Content-Type: application/pdf');
		header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
		header('Content-Transfer-Encoding: binary');
		header('Accept-Ranges: bytes');
		header('Content-Length: ' . filesize($filePath));

		// Clear output buffer before reading the file
		if (ob_get_level()) {
			ob_end_clean();
		}

		readfile($filePath);
		exit;
	}
}
