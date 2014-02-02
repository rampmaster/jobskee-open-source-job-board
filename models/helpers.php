<?php
/**
 * Jobskee - open source job board 
 *
 * @author      Elinore Tenorio <elinore.tenorio@gmail.com>
 * @license     MIT
 * @url         http://www.jobskee.com
 * 
 * A list of generic functions used around Jobskee
 */

function isValidImageExt($image) {
    $ext = array('jpg', 'JPG', 'jpeg', 'JPEG', 'png', 'PNG', 'gif', 'GIF');
    if (in_array($image, $ext)) {
        return true;
    }
    return false;
}

/*
 * converts spaces to dash and makes the string URL friendly
 */
function slugify($string) {
    $string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
    $string = strtolower($string);
    $string = preg_replace("/\W/", "-", $string);
    $string = preg_replace("/-+/", '-', $string);
    $string = trim($string, '-');
    return $string;
}

/*
 * removes unnecessary spaces
 */
function clean($string) {
    $string = preg_replace("/\W\s+/", ' ', $string);
    $string = preg_replace("/-+/", ' ', $string);
    $string = trim($string, '');
    return $string;
}

/*
 * return an excerpt text with X number of characters
 */
function excerpt($text, $count=160) {
    if (strlen($text) > $count) {
        $text = substr($text, 0, $count);
        if (strrpos($text, ' ') !== false) {
            $text = substr($text, 0, strrpos($text, ' '));
        }
        $text = $text . '...';
    }
    return $text;
}
/*
 * escape output
 */
function _e($string, $method=null) {
    if (isset($method) && $method != 'input') {
        echo nl2br(htmlspecialchars(trim($string)));
    } else {
        echo htmlspecialchars(trim($string));
    }
}

function niceDate($date) {
    echo date('M j', strtotime($date));
}

function token() {
    return md5(uniqid() . time());
}

function accessToken($id) {
    $j = new Jobs($id);
    $job = $j->showJobDetails();
    return sha1($job->id . $job->created);
}

/*
 * strip HTML tags from input - could be used for a string or array
 */
function escape($raw) {
    if (is_array($raw)) {
        foreach ($raw as $k=>$v) {
            $data[$k] = strip_tags($v);
        }
    } else {
        $data = strip_tags($raw);
    }
    return $data;
}

/*
 * allow job posting if ALLOW_JOB_POST = 1
 */
function isJobPostAllowed() {
    $app = \Slim\Slim::getInstance();
    if (ALLOW_JOB_POST != 1 && (!isset($_SESSION['email']) || !$_SESSION['email'])) {
        $app->flash('danger', 'Job posting is not allowed.');
        $app->redirect(BASE_URL);
    }
}

/*
 * protects admin pages by first authenticating the user
 */
function validateUser() {
    $app = \Slim\Slim::getInstance();
    if (!isset($_SESSION['email']) || !$_SESSION['email']) {
        $app->flash('danger', 'You must be logged in to browse the site.');
        $app->redirect(LOGIN_URL);
    } else {
        $admin = R::findOne('admin', ' email=:email ', array(':email'=>$_SESSION['email']));
        if (!$admin->id) {
            $app->flash('danger', 'Invalid login. You are not allowed to access this site.');
            $app->redirect(LOGIN_URL);
        }
    }
}

/*
 * returns true if the user logged in is a valid user
 */
function userIsValid() {
    if (!isset($_SESSION['email']) || !$_SESSION['email']) {
        return false;
    } else {
        $admin = R::findOne('admin', ' email=:email ', array(':email'=>$_SESSION['email']));
        if (isset($admin) && $admin->id) {
            return true;
        }
        return false;
    }
}

/*
 * checks whether IP address is in the ban list
 */
function isBanned() {
    $app = \Slim\Slim::getInstance();
    $ban = R::findOne('banlist', ' value=:value ', array(':value'=>$_SERVER['REMOTE_ADDR']));
    if ($ban && $ban->id) {
        $app->flash('danger', "Your IP address {$_SERVER['REMOTE_ADDR']} is not allowed to access this page.");
        $app->redirect(BASE_URL);
    }
}

/*
 * checks whether the referrer is the site itself
 */
function isValidReferrer() {
    $app = \Slim\Slim::getInstance();
    $req = $app->request;
    if (stripos($req->getReferrer(), BASE_URL, 0) === false) {
        $app->flash('danger', "Operation is not allowed due to invalid referrer.");
        $app->redirect(BASE_URL);
    }
}

/*
 * get pagination start
 */
function getPaginationStart($page=null) {
    return (!$page || $page==1) ? 0 : ((($page-1)*LIMIT));
}

/*
* Search
* http://www.iamcal.com/publish/articles/php/search/
*/
function splitTerms($terms){

   $terms = str_replace('"', '', $terms);
   $terms = preg_replace("/\"(.*?)\"/e", transformTerms('\$1'), $terms);
   $terms = preg_split("/\s+|,/", $terms);

   $out = array();
   foreach($terms as $term){
           $term = preg_replace("/\{WHITESPACE-([0-9]+)\}/e", "chr(\$1)", $term);
           $term = preg_replace("/\{COMMA\}/", ",", $term);
           $out[] = $term;
   }
   return $out;
}

/*
* Search
* http://www.iamcal.com/publish/articles/php/search/
*/
function transformTerms($term){
   $term = preg_replace("/(\s)/e", "'{WHITESPACE-'.ord('\$1').'}'", $term);
   $term = preg_replace("/,/", "{COMMA}", $term);
   return $term;
}

/*
 * check if URL is valid
 */
function checkURL($url) {
    $valid = filter_var($url, FILTER_VALIDATE_URL);
    return $valid;
}

/*
 * cURL get function
 */
function curlGet($url) {
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $url,
        CURLOPT_USERAGENT => 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)'
    ));
    $resp = curl_exec($curl);
    curl_close($curl);
}