<?php

/**
* Author: Jon Garcia 
*/

namespace App\Core\FileHandler;

use App\Core\Request;
use App\Core\Http\JsonResponse;
use App\Core\Http\View;

class FileUploads {

    /**
     * A directory pointer
     *
     * @var string
     */
    protected static $directory;

    /**
     * @param $dirName
     */
    public static function dir_exists($dirName) {
        $dirName = rtrim($dirName, '/');
        self::$directory = FILES_PATH . $dirName . '/';
        if (!file_exists(self::$directory)) {
            mkdir(self::$directory, 0775, true);
        }
    }

    /**
     * Method to hanlde CKeditor image uploads.
     */
    public static function CKimages() {

        include_once(CORE_PATH . 'view_helpers/general_functions.php');
        $directory = FILES_PATH . '/ckeditor/images/';
        if ( !file_exists($directory) ) {
            mkdir($directory, 0777, true );
        }
        $filename = $_FILES['upload']['name'];

        //extensive suitability check before doing anything with the file…
        if (empty($_FILES['upload']) || (empty($_FILES['upload']['name']))) {
            $message = "No file uploaded.";
            $url = '';
        }
        elseif ($_FILES['upload']["size"] == 0) {
            $message = "The file is of zero length.";
            $url = '';
        }
        elseif (($_FILES['upload']["type"] != "image/pjpeg") && ($_FILES['upload']["type"] != "image/jpeg") && ($_FILES['upload']["type"] != "image/png")) {
            $message = "The image must be in either JPG or PNG format. Please upload a JPG or PNG instead.";
            $url = '';
        }

        elseif (!is_uploaded_file($_FILES['upload']["tmp_name"])) {
            $message = "You may be attempting to hack our server. We're on to you; expect a knock on the door sometime soon.";
            $url = '';
        }
        else  {
            $message = "";

            unique_file_name($filename, $directory);
            $move = move_uploaded_file($_FILES['upload']['tmp_name'], $directory.$filename);
            $url = '/files/ckeditor/images/' . $filename;

            if(!$move) {
                $message = "Error moving uploaded file. Check the script is granted Read/Write/Modify permissions.";
            }
        }
        $funcNum = $_GET['CKEditorFuncNum'];
        echo "<script type='text/javascript'>window.parent.CKEDITOR.tools.callFunction($funcNum, '$url', '$message');</script>";
        exit;
    }


    /**
     * @param $field_name
     * @param $dirName
     */
    public static function upload($field_name = null, $dirName = null)
    {
        if (is_null($field_name) && is_null($dirName)) {
            $params = new Request();
            $field_name = $params->file;
            $dirName = $params->dir;
        }

        self::dir_exists($dirName);
        include_once(CORE_PATH . 'view_helpers/general_functions.php');

        $directory = self::$directory;
        $filename = $_FILES[$field_name]['name'];

        //extensive suitability check before doing anything with the file…
        if (empty($_FILES[$field_name]) || (empty($_FILES[$field_name]['name']))) {
            View::error("No file uploaded.");
            $url = '';
        }
        elseif ($_FILES[$field_name]["size"] == 0) {
            View::error("The file is of zero length.");
            $url = '';
        }
        elseif (($_FILES[$field_name]["type"] != "image/pjpeg") && ($_FILES[$field_name]["type"] != "image/jpeg") && ($_FILES[$field_name]["type"] != "image/png")) {
            View::error("The image must be in either JPG or PNG format. Please upload a JPG or PNG instead.");
            $url = '';
        }

        elseif (!is_uploaded_file($_FILES[$field_name]["tmp_name"])) {
            View::error("You may be attempting to hack our server. We're on to you. Expect a knock on the door sometime soon.");
            $url = '';
        }
        else  {
            unique_file_name($filename, $directory);
            $move = move_uploaded_file($_FILES[$field_name]['tmp_name'], $directory.$filename);
            $url = '/images/uploads/' . $filename;

            if(!$move) {
                View::error("Error moving uploaded file. Check the script is granted Read/Write/Modify permissions.");
            }
        }

        View::info('File Uploaded Successfully');
        View::info('You may find the file here: ' . $url);
        return;
    }

    /**
     * Json uploader
     */
    public static function jsonUpload()
    {
        $params = new Request();
        $field_name = 'file';
        $dirName = $params->dir;

        self::dir_exists($dirName);
        include_once(CORE_PATH . 'view_helpers/general_functions.php');

        $directory = self::$directory;
        $filename = $_FILES[$field_name]['name'];

        //extensive suitability check before doing anything with the file…
        if (empty($_FILES[$field_name]) || (empty($_FILES[$field_name]['name']))) {
            $message = "No file uploaded.";
            $status = 400;
        }
        elseif ($_FILES[$field_name]["size"] == 0) {
            $message = "The file is of zero length.";
            $status = 400;
        }
        elseif (($_FILES[$field_name]["type"] != "image/pjpeg") && ($_FILES[$field_name]["type"] != "image/jpeg") && ($_FILES[$field_name]["type"] != "image/png")) {
            $message = "The image must be in either JPG or PNG format. Please upload a JPG or PNG instead.";
            $status = 400;
        }

        elseif (!is_uploaded_file($_FILES[$field_name]["tmp_name"])) {
            $message = "You may be attempting to hack our server. We're on to you. Expect a knock on the door sometime soon.";
            $status = 403;
        }
        else  {
            unique_file_name($filename, $directory);
            $move = move_uploaded_file($_FILES[$field_name]['tmp_name'], $directory.$filename);
            $url = '/files/' . $dirName . '/' . $filename;

            $message = $url;
            $status = 200;

            if(!$move) {
                $message = "Error moving uploaded file. Check the script is granted Read/Write/Modify permissions.";
                $status = 400;
            }
        }
        JsonResponse::Response($message, $status);
    }


}