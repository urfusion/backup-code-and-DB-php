<?php

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

/* creates a compressed zip file */
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('max_execution_time', 0);
//require_once './sql_backup.php';


//If your server support mysql dump by command line then no need to use backup_sql.php
//$sqlfilename = 'database_backup_' . date('G_a_d_m_y') . '.sql';
//$result = exec('mysqldump host --password=pass --user=user >' . $sqlfilename, $output);

require_once './backup_sql.php';

echo "Backup Started.. <br />";

/**
 * Instantiate Backup_Database and perform backup
 */
$rootPath = realpath('../');


$filename = date("d-m-Y", time());

$zip = new ZipArchive();
$zip->open('../' . $filename . '.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);


$files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($rootPath), RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($files as $name => $file) {

    if (!$file->isDir()) {

        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen($rootPath) + 1);
        $filePaths = explode(".", $filePath);
        $ext = end($filePaths);
        $exclude = array(
            'editorconfig',
            'gitattributes',
            'gitignore',
            'htaccess',
            'yml',
            'zip',
            'psd',
        );

        $excludeFolder = array(
            '.git',
            'logs',
            'nbproject',
            'tmp',
            'Database',
            'Designs',
            'documents',
            'final-design-changes',
            'graphics',
            'mobile',
            'vendor',
            'web',
        );

        $relativePathString = explode("/", $relativePath);

        if (!in_array($ext, $exclude) && !in_array($relativePathString[0], $excludeFolder)) {
            $zip->addFile($filePath, $relativePath);
        }

        // Add current file to archive
    }
}
// Zip archive will be created only after closing object
if ($zip->close()) {
    echo "Backup Created <br /> Now Uploading...";

    require '../aws/aws-autoloader.php';

    $s3 = new S3Client([
        'version' => 'latest',
        'region' => 'ap-southeast-1',
        'credentials' => [
            'key' => 'key',
            'secret' => 'secret'
        ]
    ]);

    try {

        $fileUploads = '../' . $filename . '.zip';
        //$fileUploads = '../README.zip';
        $date = new DateTime();
        $date->sub(new DateInterval('P30D'));
        $s3->deleteObject([
            'Bucket' => 'bucket',
            'Key' => $date->format('d-m-Y') . '.zip',
        ]);

        $r = $s3->putObject([
            'Bucket' => 'bucket',
            'Key' => $filename . '.zip',
            'Body' => fopen($fileUploads, 'r'),
            'ACL' => 'public-read',
        ]);

        echo $r['ObjectURL'];

        if (file_exists($fileUploads)) {
            unlink($fileUploads);
        }
    } catch (Aws\Exception\S3Exception $e) {
        echo "There was an error uploading the file.\n";
    }
}

