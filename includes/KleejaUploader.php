<?php
/**
 *
 * @package Kleeja
 * @copyright (c) 2007 Kleeja.net
 * @license ./docs/license.txt
 *
 */

//no direct access
if (!defined('IN_COMMON')) {
    exit();
}

interface KleejaUploader
{
    /**
     * set the allowed extensions of uploaded files
     * @param  array $allowed_file_extensions an array of allowed extensions, and sizes ['gif'=>122, 'png'=>2421 ..]
     * @return void
     */
    public function setAllowedFileExtensions(array $allowed_file_extensions): void;

    /**
     * get the allowed extensions of uploaded files
     * @return array
     */
    public function getAllowedFileExtensions(): array;

    /**
     * set the allowed limit of the uploaded files
     * @param  int  $limit
     * @return void
     */
    public function setUploadFieldsLimit(int $limit): void;

    /**
     *  get the allowed limit of the uploaded files
     * @return int
     */
    public function getUploadFieldsLimit(): int;

    /**
     * add an information message to output it to the user
     * @param  string $message
     * @return void
     */
    public function addInfoMessage(string $message): void;

    /**
     * add an error message to output it to the user
     * @param  string $message
     * @return void
     */
    public function addErrorMessage(string $message): void;

    /**
     * get all the messages
     * @return array
     */
    public function getMessages(): array;

    /**
     * save the file information to the database
     * @param  array $fileInfo
     * @return void
     */
    public function saveToDatabase(array $fileInfo): void;

    /**
     * generate a box of the result and add it to addInfoMessage
     * @param  array $fileInfo
     * @return void
     */
    public function generateOutputBox(array $fileInfo): void;

    /**
     * here happens the magic, call this on upload submit
     * @return void
     */
    public function upload(): void;
}
