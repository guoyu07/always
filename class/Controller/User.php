<?php

namespace always\Controller;

/**
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class User extends \Http\Controller {

    /**
     *
     * @var \always\Student
     */
    private $student;

    private function checkShibLogin()
    {
        if (!isset($_SERVER['HTTP_SHIB_EP_AFFILIATION']) || !preg_match('/student@appstate.edu/',
                        $_SERVER['HTTP_SHIB_EP_AFFILIATION'])) {
            throw new \always\UserException('You must <a href="login/">log-in</a> with an ASU account to use this service.');
        }
    }

    private function loadStudent()
    {
        require_once PHPWS_SOURCE_DIR . 'mod/always/class/Student.php';
        $db = \Database::newDB();
        $rds = $db->addTable('rd_student');
        $db->addConditional($rds->getFieldConditional('user_id',
                        \Current_User::getId()));
        $result = $db->selectOneRow();
        if (!$result) {
            $this->createStudent();
        } else {
            $this->student = new \always\Student;
            $this->student->setVars($result);
        }
    }

    private function createStudent()
    {
        $student = new \always\Student;
        $student->user_id = (int) \Current_User::getId();
        $student->username = \Current_User::getUsername();
        $student->banner_id = str_replace('@appstate.edu', '',
                $_SERVER['HTTP_SHIB_CAMPUSPERMANENTID']);
        $student->first_name = $_SERVER['HTTP_SHIB_INETORGPERSON_GIVENNAME'];
        $student->last_name = $_SERVER['HTTP_SHIB_PERSON_SURNAME'];
        \ResourceFactory::saveResource($student);
        $this->student = $student;
    }

    public function get(\Request $request)
    {
        try {
            //$this->checkShibLogin();
            //$this->loadStudent();

            $token = $request->getCurrentToken();
            switch ($token) {
                case '/':
                    $data = $this->main();
                    break;
            }
        } catch (\always\UserException $ue) {
            $data['title'] = 'Sorry but there is a problem';
            $data['content'] = $ue->getMessage();
        }

        $data = $this->main();
        $view = $this->getView($data, $request);
        $response = new \Response($view);
        return $response;
    }

    /**
     * source http://www.barattalo.it/2010/01/10/sending-emails-with-attachment-and-html-with-php/
     * @param type $to
     * @param type $subject
     * @param type $messagehtml
     * @param type $from
     * @param type $fileatt
     * @param type $replyto
     * @return type
     */
    public function mail_file($to, $subject, $messagehtml, $from, $fileatt, $replyto = "")
    {
        // handles mime type for better receiving
        $ext = strrchr($fileatt, '.');
        $ftype = "";
        if ($ext == ".doc")
            $ftype = "application/msword";
        if ($ext == ".jpg")
            $ftype = "image/jpeg";
        if ($ext == ".gif")
            $ftype = "image/gif";
        if ($ext == ".zip")
            $ftype = "application/zip";
        if ($ext == ".pdf")
            $ftype = "application/pdf";
        if ($ftype == "")
            $ftype = "application/octet-stream";

        // read file into $data var
        $file = fopen($fileatt, "rb");
        $data = fread($file, filesize($fileatt));
        fclose($file);

        // split the file into chunks for attaching
        $content = chunk_split(base64_encode($data));
        $uid = md5(uniqid(time()));

        // build the headers for attachment and html
        $h = "From: $from\r\n";
        if ($replyto) {
            $h .= "Reply-To: " . $replyto . "\r\n";
        }
        $h .= "MIME-Version: 1.0\r\n";
        $h .= "Content-Type: multipart/mixed; boundary=\"" . $uid . "\"\r\n\r\n";
        $h .= "This is a multi-part message in MIME format.\r\n";
        $h .= "--" . $uid . "\r\n";
        $h .= "Content-type:text/html; charset=iso-8859-1\r\n";
        $h .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $h .= $messagehtml . "\r\n\r\n";
        $h .= "--" . $uid . "\r\n";
        $h .= "Content-Type: " . $ftype . "; name=\"" . basename($fileatt) . "\"\r\n";
        $h .= "Content-Transfer-Encoding: base64\r\n";
        $h .= "Content-Disposition: attachment; filename=\"" . basename($fileatt) . "\"\r\n\r\n";
        $h .= $content . "\r\n\r\n";
        $h .= "--" . $uid . "--";

        // send mail
        return mail($to, $subject, strip_tags($messagehtml),
                str_replace("\r\n", "\n", $h));
    }

    private function uploadFile()
    {
        require_once PHPWS_SOURCE_DIR . 'mod/always/class/UploadHandler.php';
        $upload_handler = new \UploadHandler();
        if (!$upload_handler->error) {
            $this->emailResume($upload_handler->stamped_file);
        }
    }

    private function emailResume($file)
    {
        $first_name = $this->student->first_name;
        $last_name = $this->student->last_name;
        $student_email = $this->student->username . '@appstate.edu';

        $db = \Database::newDB();
        $cttbl = $db->buildTable('rd_ctocollege');
        $cotbl = $db->buildTable('rd_counselor');
        $utbl = $db->addTable('users');

        $utbl->addField('email');

        $db->join($cttbl->getField('counselor_id'), $cotbl->getField('id'));
        $db->join($cotbl->getField('user_id'), $utbl->getField('id'));

        $c1 = $cttbl->getFieldConditional('college_id',
                $this->student->college_id);
        $db->setConditional($c1);

        $counselors = $db->select();

        $contact_email = \PHPWS_Settings::get('always', 'contact_email');

        if (empty($counselors)) {
            $to[] = $contact_email;
            $subject = 'Always: Counselor not found';
            $message = <<<EOF
<html>
    <body>
        <p>This student's college did not have an assigned counselor.</p>
    </body>
</html>
EOF;
        } else {
            foreach ($counselors as $row) {
                $to[] = $row['email'];
            }
            $subject = 'Always: Student Resume Request';
            $message = <<<EOF
<html>
    <body>
        <p> $first_name $last_name (<a href="mailto:$student_email">$student_email</a>) has requested your help with a resume.</p>
    </body>
</html>
EOF;
        }

        $from = \PHPWS_Settings::get('always', 'contact_email');

        foreach ($to as $refemail) {
            $this->mail_file($refemail, $subject, $message, $from, $file);
        }

        $subject = 'Always Confirmation';
        $message = <<<EOF
<html>
    <body>
        <p>$first_name $last_name,</p>
        <p>Thank you for using The Career Development Center resume drop.
We have received your resume and a career counselor will review it and
email you within 3 to 5 business days.</p>
<p>Thank you,<br />Career Development Staff</p>
    </body>
</html>
EOF;

        $this->mail_file($student_email, $subject, $message, $from, $file);

        unlink($file);
    }

    public function post(\Request $request)
    {
        $this->checkShibLogin();
        $this->loadStudent();
        $token = $request->getCurrentToken();
        try {
            if ($token == 'upload') {
                $this->uploadFile();
                exit();
            } else {
                if (!$request->isVar('command')) {
                    throw new \always\UserException('Colleges have not been created. Please contact the administrators.');
                }

                switch ($request->getVar('command')) {
                    case 'update_student':
                        $this->updateStudent($request);
                        break;
                }
                $response = new \Http\SeeOtherResponse(\Server::getCurrentUrl(false));
            }
        } catch (\always\UserException $ue) {
            $data['title'] = 'Sorry but there is a problem';
            $data['content'] = $ue->getMessage();
            $view = $this->getView($data, $request);
            $response = new \Response($view);
            return $response;
        }
        return $response;
    }

    private function updateStudent(\Request $request)
    {
        $first_name = $request->getVar('first_name');
        if (empty($first_name)) {
            throw new \always\UserException('Please review your information. Your first name must not be blank.');
        } else {
            $this->student->first_name = $first_name;
        }
        $last_name = $request->getVar('first_name');
        if (empty($last_name)) {
            throw new \always\UserException('Please review your information. Your last name must not be blank.');
        } else {
            $this->student->last_name = $request->getVar('last_name');
        }
        $college_id = $request->getVar('college');
        if (empty($college_id)) {
            throw new \always\UserException('Please review your information. You must pick a college.');
        } else {
            $this->student->college_id = $college_id;
        }
        $this->student->reviewed = true;
        \ResourceFactory::saveResource($this->student);
    }

    public function getHtmlView($data, \Request $request)
    {
        $template = new \Template($data);
        $template->setModuleTemplate('always', 'User/main.html');
        return $template;
    }

    private function main()
    {
        $title = 'Always';
        \Layout::addPageTitle($title);
        $content = $this->notLogged();
        /*if (!\Current_User::isLogged()) {
            $content = $this->notLogged();
        } else {
            $content = $this->userMenu();
        }*/

        return array('title' => $title, 'content' => $content);
    }

    private function notLogged()
    {
        $data['login'] = PHPWS_HOME_HTTP . 'secure/';
        $template = new \Template($data);
        $template->setModuleTemplate('always', 'User/login.html');
        return $template;
    }

    private function userMenu()
    {
        if (!$this->student->isReviewed()) {
            return $this->reviewData();
        } else {
            return $this->uploadScreen();
        }
    }

    private function uploadScreen()
    {
        require_once PHPWS_SOURCE_DIR . 'Global/Form.php';
        javascript('jquery');
        javascript('jquery_ui');
        $scs = '<script type="text/javascript" src="' . PHPWS_SOURCE_HTTP .
                'mod/always/javascript/';
        $sce = '"></script>';

        \Layout::addToStyleList('mod/always/javascript/fileupload/css/jquery.fileupload-ui.css');
        \Layout::addJSHeader($scs . 'fileupload/js/jquery.iframe-transport.js' . $sce);
        \Layout::addJSHeader($scs . 'fileupload/js/jquery.fileupload.js' . $sce);
        \Layout::addJSHeader($scs . 'fileupload/js/jquery.fileupload-process.js' . $sce);
        \Layout::addJSHeader($scs . 'fileupload/js/jquery.fileupload-validate.js' . $sce);
        \Layout::addJSHeader($scs . 'User/script.js' . $sce);

        $template = new \Template;
        $template->setModuleTemplate('always', 'User/upload.html');
        return $template->get();
    }

    private function reviewData()
    {
        $db = \Database::newDB();
        $college = $db->addTable('rd_college');
        $college->addField('id');
        $cname = $college->addField('name');
        $college->addOrderBy($cname);
        $colleges = $db->select();

        if (empty($colleges)) {
            throw new \always\UserException('There was a problem with our software. Colleges have not been created. Please contact the administrators.');
        }
        $sel[0] = '---';
        foreach ($colleges as $c) {
            $sel[$c['id']] = $c['name'];
        }

        \Form::requiredScript();

        $form = $this->student->pullForm();
        $form->getSingleInput('first_name')->setRequired();
        $form->getSingleInput('last_name')->setRequired();
        $form->addSelect('college', $sel, 'College of study')->setRequired();
        $form->setAction('/always/');
        $form->addHidden('command', 'update_student');
        $form->addSubmit('submit', 'The information above is correct');
        $vars = $form->getInputStringArray();
        $template = new \Template($vars);
        $template->setModuleTemplate('always', 'User/review_data.html');
        return $template->get();
    }

}

?>
