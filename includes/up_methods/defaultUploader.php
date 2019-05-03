<?php
/**
*
* @package Kleeja
* @copyright (c) 2007 Kleeja.com
* @license ./docs/license.txt
*
*/


//no for directly open
if (! defined('IN_COMMON'))
{
    exit();
}


//includes important functions
include_once dirname(__file__) . '/../up_helpers/others.php';
include_once dirname(__file__) . '/../up_helpers/thumbs.php';
include_once dirname(__file__) . '/../up_helpers/watermark.php';
include_once dirname(__file__) . '/../up_helpers/remote_uploading.php';


/*
 * uploading class, the most important class in Kleeja
 * Where files uploaded by this class, depend on Kleeja settings
 */
class defaultUploader implements KleejaUploader
{
    protected $messages = [];

    protected $allowed_file_extensions = [];

    protected $upload_fields_limit = 0;

    protected $total_uploaded_files = 0;

    /**
     * set the allowed extensions of uploaded files
     * @param array $allowed_file_extensions an array of allowed extensions ['gif', 'png' ..]
     */
    public function setAllowedFileExtensions($allowed_file_extensions)
    {
        $this->allowed_file_extensions = $allowed_file_extensions;
    }


    /**
     * get the allowed extensions of uploaded files
     * @return array
     */
    public function getAllowedFileExtensions()
    {
        return $this->allowed_file_extensions;
    }


    /**
     * set the allowed limit of the uploaded files
     * @param int $limit
     */
    public function setUploadFieldsLimit($limit)
    {
        $this->upload_fields_limit = $limit;
    }


    /**
     *  get the allowed limit of the uploaded files
     * @return int
     */
    public function getUploadFieldsLimit()
    {
        return $this->upload_fields_limit;
    }


    /**
     * add an information message to output it to the user
     * @param  string $message
     * @return void
     */
    public function addInfoMessage($message)
    {
        array_push($this->messages, [$message, 'info']);
    }


    /**
     * add an error message to output it to the user
     * @param  string $message
     * @return void
     */
    public function addErrorMessage($message)
    {
        array_push($this->messages, [$message, 'error']);
    }


    /**
     * get all the messages
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }


    /**
     * save the file information to the database
     * @param  array $fileInfo
     * @return void
     */
    public function saveToDatabase($fileInfo)
    {
        global $SQL, $dbprefix, $config;

//        $fileInfo =
//         [
//            'saveToFolder'
//            'originalFileName'
//            'generatedFileName'
//            'fileSize'
//            'currentUserId'
//            'fileExtension
//        ];
//      +  to be added in this method and goes to generateOutputBox
//        [
//            'DeleteCode'
//            'insertId'
//        [


        //sometime cant see file after uploading.. but ..
        @chmod($fileInfo['saveToFolder'] . '/' . $fileInfo['generatedFileName'], 0644);

        $fileInfo['DeleteCode'] = sha1($fileInfo['generatedFileName'] . uniqid());

        $queryValues = 
        [
            'name'           => $fileInfo['generatedFileName'],
            'real_filename'  => $fileInfo['originalFileName'],
            'size'           => intval($fileInfo['fileSize']),
            'time'           => time(),
            'folder'         => $fileInfo['saveToFolder'],
            'type'           => $fileInfo['fileExtension'],
            'user'           => $fileInfo['currentUserId'],
            'code_del'       => $fileInfo['DeleteCode'],
            'user_ip'        => get_ip(),
            'id_form'        => $config['id_form'],
        ];

        $is_img = in_array($fileInfo['fileExtension'], ['png','gif','jpg','jpeg', 'bmp']) ? true : false;


        is_array($plugin_run_result = Plugins::getInstance()->run('defaultUploader_saveToDatabase_qr', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook


        // insertion query
        $insert_query = [
            'INSERT'	=> '`' . implode('` , `', array_keys($queryValues)) . '`',
            'INTO'		 => "{$dbprefix}files",
            'VALUES'	=> "'" . implode("', '", array_map([$SQL, 'escape'], array_values($queryValues))) . "'"
        ];


        // do the query
        $SQL->build($insert_query);


        // inset id so it can be used in url like in do.php?id={id_for_url}
        $fileInfo['insertId'] = $SQL->insert_id();



        // update Kleeja stats
        $update_query = [
            'UPDATE'	=> "{$dbprefix}stats",
            'SET'		  => ($is_img ? 'imgs=imgs+1' : 'files=files+1') . ',sizes=sizes+' . intval($fileInfo['fileSize']) . ''
        ];

        $SQL->build($update_query);


        $this->generateOutputBox($fileInfo);
    }


    /**
     * generate a box of the result and add it to addInfoMessage
     * @param  array $fileInfo
     * @return void
     */
    public function generateOutputBox($fileInfo)
    {
        global $config, $lang;

//        $fileInfo =
//         [
//            'saveToFolder'
//            'originalFileName'
//            'generatedFileName'
//            'fileSize'
//            'currentUserId'
//            'fileExtension
//            'DeleteCode'
//            'insertId'
//        [


        $is_img = in_array($fileInfo['fileExtension'], ['png','gif','jpg','jpeg', 'bmp']) ? true : false;


        // information of file, used for generating a url boxes
        $file_info = [
            '::ID::'    => $fileInfo['insertId'],
            '::NAME::'  => $fileInfo['generatedFileName'],
            '::DIR::'   => $fileInfo['saveToFolder'],
            '::FNAME::' => $fileInfo['originalFileName'],
        ];

        // show del code link box
        $extra_del = '';

        if ($config['del_url_file'])
        {
            $extra_del	= get_up_tpl_box('del_file_code',
                     [
                         'b_title'     => $lang['URL_F_DEL'],
                         'b_code_link' => kleeja_get_link('del', ['::CODE::'=>$fileInfo['DeleteCode']])
                     ]
                );
        }

        //show imgs
        if ($is_img)
        {
            $img_html_result = '';

            // get default thumb dimensions
            $thmb_dim_w = $thmb_dim_h = 150;

            if (strpos($config['thmb_dims'], '*') !== false)
            {
                list($thmb_dim_w, $thmb_dim_h) = array_map('trim', explode('*', $config['thmb_dims']));
            }

            // generate a thumbnail
            helper_thumb(
                $fileInfo['saveToFolder'] . '/' . $fileInfo['generatedFileName'],
                 $fileInfo['fileExtension'],
                $fileInfo['saveToFolder'] . '/thumbs/' . $fileInfo['generatedFileName'],
                $thmb_dim_w,
                $thmb_dim_h
            );


            $img_html_result .= get_up_tpl_box('image_thumb',
                        [
                            'b_title'	  => $lang['URL_F_THMB'],
                            'b_url_link'=> kleeja_get_link('image', $file_info),
                            'b_img_link'=> kleeja_get_link('thumb', $file_info)
                        ]
                    );


            // watermark on image
            if ($config['write_imgs'] != 0 && in_array($fileInfo['fileExtension'], ['gif', 'png', 'jpg', 'jpeg', 'bmp']))
            {
                helper_watermark($fileInfo['saveToFolder'] . '/' . $fileInfo['generatedFileName'], $fileInfo['fileExtension']);
            }

            //then show, image box
            $img_html_result .= get_up_tpl_box('image',
                            [
                                'b_title'	   => $lang['URL_F_IMG'],
                                'b_bbc_title'=> $lang['URL_F_BBC'],
                                'b_url_link' => kleeja_get_link('image', $file_info),
                            ]
                    );

            //add del link box to the result if there is any
            $img_html_result .= $extra_del;

            is_array($plugin_run_result = Plugins::getInstance()->run('defaultUploader_generateOutputBox_image_result', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook


            //show success message
            $this->addInfoMessage(
                '<div class="up-box-title">' . $lang['IMG_DOWNLAODED'] . ': ' .
                htmlspecialchars($fileInfo['originalFileName']) . '</div>' . "\n" .
                $img_html_result
            );
        }
        else
        {
            //then show other files
            $else_html_result = get_up_tpl_box('file',
                    [
                        'b_title'	   => $lang['URL_F_FILE'],
                        'b_bbc_title'=> $lang['URL_F_BBC'],
                        'b_url_link' => kleeja_get_link('file', $file_info),
                    ]
            );


            //add del link box to the result if there is any
            $else_html_result .= $extra_del;

            is_array($plugin_run_result = Plugins::getInstance()->run('defaultUploader_generateOutputBox_file_result', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook


            //show success message
            $this->addInfoMessage(
                '<div class="up-box-title">' . $lang['FILE_DOWNLAODED'] . ': '
                . htmlspecialchars($fileInfo['originalFileName']) . '</div>' . "\n" .
                $else_html_result
            );
        }

        $this->total_uploaded_files++;
    }


    /**
     * here happens the magic, call this on upload submit
     * @param int $uploadType upload from files input or links
     */
    public function upload($uploadType)
    {
        global $usrcp, $config, $lang;

        // when $uploadType = 1, then we upload from _file input
        // if $uploadType = 2, then we uploading from url which is disabled by default and is buggy


        //upload to this folder
        $current_uploading_folder = $config['foldername'];

        //current user id
        $current_user_id = $usrcp->name() ? $usrcp->id() : '-1';


        //is captcha is on?
        $captcha_enabled = intval($config['safe_code']);

        $return_now = false;

        is_array($plugin_run_result = Plugins::getInstance()->run('defaultUploader_upload_1st', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook


        // check folder our real folder
        if (! file_exists($current_uploading_folder))
        {
            if (! make_folder($current_uploading_folder))
            {
                $this->addErrorMessage($lang['CANT_DIR_CRT']);
            }
        }


        if ($return_now)
        {
            return;
        }

        //no uploading yet, or just go to index.php, so we have make a new session
        if (! $uploadType)
        {
            unset($_SESSION['FIILES_NOT_DUPLI'], $_SESSION['FIILES_NOT_DUPLI_LINKS']);
        }


        // is captcha on, and there is uploading going on
        if ($captcha_enabled && $uploadType)
        {
            //captcha is wrong
            if (! kleeja_check_captcha())
            {
                $this->addErrorMessage($lang['WRONG_VERTY_CODE']);
                return;
            }
        }

        // to prevent flooding, user must wait, waiting-time is grapped from Kleeja settings, admin is exceptional
        if (! user_can('enter_acp') && user_is_flooding($current_user_id))
        {
            $this->addErrorMessage(sprintf($lang['YOU_HAVE_TO_WAIT'],
                                    $current_user_id == '-1' ? $config['guestsectoupload'] : $config['usersectoupload']));
            return;
        }


        //detect flooding, TODO fix it or remove it
        if ($uploadType == 1 && isset($_SESSION['FIILES_NOT_DUPLI']))
        {
            if (! empty($_SESSION['FIILES_NOT_DUPLI']) && $_SESSION['FIILES_NOT_DUPLI']  == sha1(serialize(array_column($_FILES, 'name'))))
            {
                unset($_SESSION['FIILES_NOT_DUPLI']);
//                if(!ip('ajax'))
//                {
//                    redirect('./');
//                }

                $this->addErrorMessage($lang['U_R_FLOODER']);
                return;
            }
        }

        if ($uploadType == 2 && isset($_SESSION['FIILES_NOT_DUPLI_LINKS']))
        {
            if ($_SESSION['FIILES_NOT_DUPLI_LINKS'] == sha1(serialize($_POST)))
            {
                unset($_SESSION['FIILES_NOT_DUPLI_LINKS']);

                if (! ip('ajax'))
                {
                    redirect('./');
                }

                $this->addErrorMessage($lang['U_R_FLOODER']);
                return;
            }
        }


        // flooding code, making sure every ok session is cleared
        if ($uploadType == 1 && sizeof($_FILES) > 0)
        {
            $_SESSION['FIILES_NOT_DUPLI'] = sha1(serialize(array_column($_FILES, 'name')));
        }
        elseif ($uploadType == 2)
        {
            $_SESSION['FIILES_NOT_DUPLI_LINKS'] = sha1(serialize($_POST));
        }


        //now close session to let user open any other page in Kleeja
        session_write_close();


        // do upload
        switch ($uploadType)
        {
            //uploading from a _files input
            default:
            case 1:

                if (! empty($_FILES['file']['tmp_name']))
                {
                    $_FILES['file'][0] = $_FILES['file'];
                }


                // loop the uploaded files
                for ($i=0; $i<=$this->getUploadFieldsLimit(); $i++)
                {
                    //no file!
                    if (empty($_FILES['file_' . $i . '_']['tmp_name']) && empty($_FILES['file'][$i]['tmp_name']))
                    {
                        if (is_null($_FILES['file_' . $i . '_']) && is_null($_FILES['file'][$i]))
                        {
                            continue;
                        }

                        $error = isset($_FILES['file_' . $i . '_'])
                                ? $_FILES['file_' . $i . '_']['error']
                                : (isset($_FILES['file'][$i]) ? $_FILES['file'][$i]['error'] : -1);

                        $filename = isset($_FILES['file'][$i]['name'])
                                    ? $_FILES['file'][$i]['name']
                                    : (isset($_FILES['file_' . $i . '_']['name']) ? $_FILES['file_' . $i . '_']['name'] : '....');

                        $upload_max_size = ini_get('upload_max_filesize');

                        if ($error !== UPLOAD_ERR_OK)
                        {
                            switch ($error)
                            {
                                case UPLOAD_ERR_INI_SIZE:
                                case UPLOAD_ERR_FORM_SIZE:
                                    $this->addErrorMessage(sprintf(
                                            $lang['SIZE_F_BIG'],
                                            htmlspecialchars($filename),
                                            'php.ini/upload_max_filesize: ' . $upload_max_size)
                                    );

                                    break;

                                case UPLOAD_ERR_PARTIAL:
                                   // $message = "The uploaded file was only partially uploaded";
                                    break;

                                case UPLOAD_ERR_NO_FILE:
                                 //   $message = "No file was uploaded";
                                    break;

                                case UPLOAD_ERR_NO_TMP_DIR:
                                    $this->addErrorMessage('Missing a temporary folder');

                                    break;

                                case UPLOAD_ERR_CANT_WRITE:
                                    $this->addErrorMessage('Failed to write file to disk');

                                    break;

                                case UPLOAD_ERR_EXTENSION:
                                    $this->addErrorMessage('File upload stopped by extension');

                                    break;

                                default:
                                    $this->addErrorMessage(sprintf($lang['CANT_UPLAOD'], htmlspecialchars($filename)));

                                    break;

                            }
                        }

                        continue;
                    }


                    $this->uploadTypeFile($i, $current_uploading_folder, $current_user_id);
                }

                break;


            //uploading from a url text-input
            case 2:

                //if not enabled, quit it
                if ($config['www_url'] != 1)
                {
                    break;
                }

                //loop text inputs
                for ($i=0; $i<=$this->getUploadFieldsLimit(); $i++)
                {
                    $this->uploadTypeUrl($i, $current_uploading_folder, $current_user_id);
                }

                break;
        }



        // well, no file uploaded, ask user to choose a file before submit
        if ($this->total_uploaded_files == 0 && sizeof($this->messages) == 0)
        {
            $this->addErrorMessage($lang['CHOSE_F']);
        }
    }


    /**
     * upload a file from $_FILES
     * @param integer $fieldNumber as in file[i]
     * @param $current_uploading_folder
     * @param $current_user_id
     */
    public function uploadTypeFile($fieldNumber, $current_uploading_folder, $current_user_id)
    {
        global $config, $lang;

        $fileInfo = [
            'saveToFolder',
            'originalFileName',
            'generatedFileName',
            'fileSize',
            'currentUserId',
            'fileExtension'
        ];


        $fileInfo['saveToFolder']  = $current_uploading_folder;
        $fileInfo['currentUserId'] = $current_user_id;


        if (! isset($_FILES['file_' . $fieldNumber . '_']) && isset($_FILES['file'][$fieldNumber]))
        {
            $_FILES['file_' . $fieldNumber . '_'] = $_FILES['file'][$fieldNumber];
        }

        // file name
        $fileInfo['originalFileName'] = isset($_FILES['file_' . $fieldNumber . '_']['name'])
                            ? urldecode(str_replace([';',','], '', $_FILES['file_' . $fieldNumber . '_']['name']))
                            : '';

        if (empty($fileInfo['originalFileName']))
        {
            $this->addErrorMessage(sprintf($lang['WRONG_F_NAME'], htmlspecialchars($_FILES['file_' . $fieldNumber . '_']['name'])));
            return;
        }

        // get the extension of file
        $fileInfo['fileExtension'] = strtolower(array_pop(explode('.', $fileInfo['originalFileName'])));


        // them the size
        $fileInfo['fileSize'] = ! empty($_FILES['file_' . $fieldNumber . '_']['size'])
                                ? intval($_FILES['file_' . $fieldNumber . '_']['size'])
                                : 0;


        // get the other filename, changed depend on kleeja settings
        $fileInfo['generatedFileName'] = change_filename_decoding($fileInfo['originalFileName'], $fieldNumber, $fileInfo['fileExtension']);


        // filename templates {rand:..}, {date:..}
        $fileInfo['generatedFileName'] = change_filename_templates(trim($config['prefixname']) . $fileInfo['generatedFileName']);


        // file exists before? change it a little
        if (file_exists($current_uploading_folder . '/' . $fileInfo['generatedFileName']))
        {
            $fileInfo['generatedFileName'] = change_filename_decoding(
                                                        $fileInfo['generatedFileName'],
                                                        $fieldNumber, $fileInfo['fileExtension'],
                                                        'exists'
                                                );
        }

        is_array($plugin_run_result = Plugins::getInstance()->run('defaultUploader_uploadTypeFile_1st', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook


        // now, let process it
        if (! in_array(strtolower($fileInfo['fileExtension']), array_keys($this->getAllowedFileExtensions())))
        {
            // guest
            if ($current_user_id == '-1')
            {
                $this->addErrorMessage(
                                    sprintf($lang['FORBID_EXT'], $fileInfo['fileExtension'])
                                    . '<br> <a href="' . ($config['mod_writer'] ? 'register.html' : 'ucp.php?go=register') .
                                    '" title="' . htmlspecialchars($lang['REGISTER']) . '">' . $lang['REGISTER'] . '</a>'
                                );
            }
            // a member
            else
            {
                $this->addErrorMessage(sprintf($lang['FORBID_EXT'], $fileInfo['fileExtension']));
            }
        }
        // bad chars in the filename
        elseif (preg_match ("#[\\\/\:\*\?\<\>\|\"]#", $fileInfo['generatedFileName']))
        {
            $this->addErrorMessage(sprintf($lang['WRONG_F_NAME'], htmlspecialchars($_FILES['file_' . $fieldNumber . '_']['name'])));
        }
        // check file extension for bad stuff
        elseif (ext_check_safe($_FILES['file_' . $fieldNumber . '_']['name']) == false)
        {
            $this->addErrorMessage(sprintf($lang['WRONG_F_NAME'], htmlspecialchars($_FILES['file_' . $fieldNumber . '_']['name'])));
        }
        // check the mime-type for the file
        elseif (check_mime_type($_FILES['file_' . $fieldNumber . '_']['type'], $fileInfo['fileExtension'], $_FILES['file_' . $fieldNumber . '_']['tmp_name']) == false)
        {
            $this->addErrorMessage(sprintf($lang['NOT_SAFE_FILE'], htmlspecialchars($_FILES['file_' . $fieldNumber . '_']['name'])));
        }
        // check file size
        elseif ($this->getAllowedFileExtensions()[$fileInfo['fileExtension']] > 0 && $fileInfo['fileSize'] >= $this->getAllowedFileExtensions()[$fileInfo['fileExtension']])
        {
            $this->addErrorMessage(sprintf(
                                        $lang['SIZE_F_BIG'],
                                        htmlspecialchars($_FILES['file_' . $fieldNumber . '_']['name']),
                                        readable_size($this->getAllowedFileExtensions()[$fileInfo['fileExtension']]))
                                    );
        }
        // no errors, so upload it
        else
        {
            is_array($plugin_run_result = Plugins::getInstance()->run('defaultUploader_uploadTypeFile_2nd', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

            // now, upload the file
            $file = move_uploaded_file($_FILES['file_' . $fieldNumber . '_']['tmp_name'], $current_uploading_folder . '/' . $fileInfo['generatedFileName']);

            if ($file)
            {
                $this->saveToDatabase($fileInfo);
            }
            else
            {
                $this->addErrorMessage(sprintf($lang['CANT_UPLAOD'], $fileInfo['originalFileName']));
            }
        }
    }


    /**
     * upload a file from a URL
     * @param $fieldNumber
     * @param $current_uploading_folder
     * @param $current_user_id
     */
    public function uploadTypeUrl($fieldNumber, $current_uploading_folder, $current_user_id)
    {
        global $config, $lang;

        $fileInfo = [
            'saveToFolder',
            'originalFileName',
            'generatedFileName',
            'fileSize',
            'currentUserId',
            'fileExtension'
        ];


        $fileInfo['saveToFolder']  = $current_uploading_folder;
        $fileInfo['currentUserId'] = $current_user_id;


        if (p('file_' . $fieldNumber . '_') == '' || p('file_' . $fieldNumber . '_') == $lang['PAST_URL_HERE'])
        {
            return;
        }


        // get file name
        $fileInfo['originalFileName'] = basename(p('file_' . $fieldNumber . '_'));


        // file extension, type
        $fileExtensions = array_map('strtolower', explode('.', $fileInfo['originalFileName']));


        if (sizeof($fileExtensions) > 1 && in_array($fileExtensions[sizeof($fileExtensions)-1], ['html', 'php', 'html']))
        {
            $fileInfo['fileExtension'] = strtolower($fileExtensions[sizeof($fileExtensions)-2]);
        }
        elseif (sizeof($fileExtensions) > 0)
        {
            $fileInfo['fileExtension'] = strtolower($fileExtensions[sizeof($fileExtensions)-1]);
        }
        else
        {
            $fileInfo['fileExtension'] = ''; //what to do?
        }


        // change to another filename depend on kleeja settings
        $fileInfo['generatedFileName'] = change_filename_decoding($fileInfo['originalFileName'], $fieldNumber, $fileInfo['fileExtension']);
        $fileInfo['generatedFileName'] = change_filename_templates(trim($config['prefixname']) . $fileInfo['generatedFileName']);


        is_array($plugin_run_result = Plugins::getInstance()->run('defaultUploader_uploadTypeUrl_1st', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook


        //forbbiden type ? quit it
        if (! in_array(strtolower($fileInfo['fileExtension']), array_keys($this->getAllowedFileExtensions())))
        {
            if ($current_user_id == '-1')
            {
                $this->addErrorMessage(
                    sprintf($lang['FORBID_EXT'], $fileInfo['fileExtension']) .
                    '<br> <a href="' . ($config['mod_writer'] ? 'register.html' : 'ucp.php?go=register') . '">' .
                    $lang['REGISTER'] . '</a>'
                );
            }
            // a member
            else
            {
                $this->addErrorMessage(sprintf($lang['FORBID_EXT'], $fileInfo['fileExtension']));
            }
        }
        // file exists before ? quit it
        elseif (file_exists($current_uploading_folder . '/' . $fileInfo['generatedFileName']))
        {
            $this->addErrorMessage(sprintf($lang['SAME_FILE_EXIST'], htmlspecialchars($fileInfo['generatedFileName'])));
        }
        // no errors, ok, lets upload now
        else
        {
            is_array($plugin_run_result = Plugins::getInstance()->run('defaultUploader_uploadTypeUrl_2nd', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook


            //no prefix ? http or even ftp, then add one
            if (! in_array(strtolower(substr(p('file_' . $fieldNumber . '_'), 0, 4)), ['http', 'ftp:']))
            {
                $_POST['file_' . $fieldNumber . '_'] = 'http://' . p('file_' . $fieldNumber . '_');
            }

            //get size, if big quit it
            $fileInfo['fileSize'] = get_remote_file_size(p('file_' . $fieldNumber . '_'));

            if ($this->getAllowedFileExtensions()[$fileInfo['fileExtension']] > 0 && $fileInfo['fileSize'] >= $this->getAllowedFileExtensions()[$fileInfo['fileExtension']])
            {
                $this->addErrorMessage(sprintf(
                                        $lang['SIZE_F_BIG'],
                                        p('file_' . $fieldNumber . '_'),
                                        readable_size($this->getAllowedFileExtensions()[$fileInfo['fileExtension']])
                                    ));
            }
            else
            {
                //get remote data, if no data quit it
                $data = fetch_remote_file(
                                    p('file_' . $fieldNumber . '_'),
                                    $current_uploading_folder . '/' . $fileInfo['generatedFileName'],
                                    15,
                                    false,
                                    2,
                                    true
                        );

                if ($data === false)
                {
                    $this->addErrorMessage($lang['URL_CANT_GET']);
                }
                else
                {
                    $this->saveToDatabase($fileInfo);
                }
            }
        }
    }
}
