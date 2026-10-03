<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class Uploads extends Controller
{
    public function serve($filename)
    {
        $path = WRITEPATH . 'uploads/' . $filename;

        if (!is_file($path)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException($filename);
        }

        $fileinfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $fileinfo->file($path);

        return $this->response
            ->setHeader('Content-Type', $mimeType)
            ->setBody(file_get_contents($path));
    }
}
