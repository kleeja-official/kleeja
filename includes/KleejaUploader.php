<?php
/**
*
* @package Kleeja
* @copyright (c) 2007 Kleeja.com
* @license ./docs/license.txt
*
*/


//no direct access
if (! defined('IN_COMMON'))
{
    exit();
}



interface KleejaUploader
{

    /**
     * set the allowed extensions of uploaded files
     * @param  array $allowed_file_extensions an array of allowed extensions, and sizes ['gif'=>122, 'png'=>2421 ..]
     * @return void
     */
    public function setAllowedFileExtensions($allowed_file_extensions);


    /**
     * get the allowed extensions of uploaded files
     * @return array
     */
    public function getAllowedFileExtensions();


    /**
     * set the allowed limit of the uploaded files
     * @param  int  $limit
     * @return void
     */
    public function setUploadFieldsLimit($limit);

    /**
     *  get the allowed limit of the uploaded files
     * @return int
     */
    public function getUploadFieldsLimit();


    /**
     * add an information message to output it to the user
     * @param  string $message
     * @return void
     */
    public function addInfoMessage($message);

    /**
     * add an error message to output it to the user
     * @param  string $message
     * @return void
     */
    public function addErrorMessage($message);

    /**
     * get all the messages
     * @return array
     */
    public function getMessages();

    /**
     * save the file information to the database
     * @param  array $fileInfo
     * @return void
     */
    public function saveToDatabase($fileInfo);


    /**
     * generate a box of the result and add it to addInfoMessage
     * @param  array $fileInfo
     * @return void
     */
    public function generateOutputBox($fileInfo);


    /**
     * here happens the magic, call this on upload submit
     * @param  int  $uploadType upload from files input or links
     * @return void
     */
    public function upload($uploadType);
}
