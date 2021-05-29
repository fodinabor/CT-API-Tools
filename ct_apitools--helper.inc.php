<?php
/*
MIT License

Copyright (c) Joachim Meyer 2018
          (c) Bernhard Weichel 2021

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/

/*
--- Churchtools API Helper

https://api.church.tools/index.html
*/


namespace CT_APITOOLS;

/**
 * helper for jsonpath
 */

use JsonPath\JsonObject;


/**
 * find a set in a JSONPath
 *
 * @param $masterdata  the Jason Path objct
 * @param $jsonpath    the path to search for
 * @return mixed
 */
function find_in_JSONPath(&$masterdata, $jsonpath)
{
    return ($masterdata->get($jsonpath));
}

/**
 *
 * find one in a JSONPath
 *
 * @param $masterdata  the Jason Path objct
 * @param $jsonpath    the path to search for
 * @return mixed | null
 */
function find_one_in_JSONPath(&$masterdata, $jsonpath)
{
    $result = find_in_JSONPath($masterdata, $jsonpath);
    $result = empty($result) ? null : $result[0];
    return ($result);
}

/**
 * create a new instance of JSONObject to be used to retrieve json data
 *
 * @param $masterdata
 * @return JsonObject
 * @throws \JsonPath\InvalidJsonException
 */
function create_JSONPath($masterdata){
    return new JSONObject($masterdata);
}

/**
 * Extract key for cokie store etc.
 *
 * @param $domain the url as to be specified in the api calls e.g. https://mychurch.church.tools/?q=api/ajax
 * @return  the domain e.g. 'mychurch.church.tools'
 *
 * for internal use
 */

function _getCoreDomain($domain)
{
    $pos = strpos($domain, "://");
    if ($pos !== false) {
        $domain = substr($domain, $pos + 3);
    }

    $pos = strpos($domain, "/");
    if ($pos !== false) {
        $domain = substr($domain, 0, $pos);
    }

    $pos = strpos($domain, "?");
    if ($pos !== false) {
        $domain = substr($domain, 0, $pos);
    }

    return $domain;
}

/**
 * return a pointer to the static cookies array
 *
 * todo: this is needed as long as the entire stuff is not held in a Class
 *
 * @return array
 *
 * for internal use
 */
function &_getCookiesArray()
{
    static $CT_cookies = array();
    return $CT_cookies;
}


/**
 *
 * Return the cookies for a given ct url
 *
 * note that cookies are not preserved when the script terminates.
 *
 * @param $domain the url as to be specified in the api calls e.g. https://mychurch.church.tools/?q=api/ajax
 * @return string the cookie string
 *
 * for internal use
 */
function _getCookies($domain)
{
    $domain = _getCoreDomain($domain);
    $CT_cookies = &_getCookiesArray();
    if (!isset($CT_cookies[$domain])) {
        $CT_cookies[$domain] = array();
    }
    $res = "";
    foreach ($CT_cookies[$domain] as $key => $cookie) {
        $res .= "$key=$cookie; ";
    }
    return $res;
}

/**
 *
 * save recceived cookies
 *
 * note that cookies are not preserved when the script terminates.
 *
 * @param $domain the url as to be specified in the api calls e.g. https://mychurch.church.tools/?q=api/ajax
 * @param $r  the result headers of the most recent call
 *
 * for internal use
 */
function _saveCookies($domain, $r)
{
    $domain = _getCoreDomain($domain);
    $CT_cookies = &_getCookiesArray();
    foreach ($r as $hdr) {
        if (preg_match('/^Set-Cookie:\s*([^;]+)/i', $hdr, $matches)) {
            parse_str($matches[1], $tmp);
            $CT_cookies[$domain] += $tmp;
        }
    }
}


/**
 *
 * build a post data string for file attachments
 *
 * @param $boundary  a string to separate the parts
 * @param $fields    the fields to be packed
 * @param $filename
 * @param $file_content
 * @return string
 *
 * for internal uses
 *
 */
function CT_build_data_files($boundary, $fields, $filename, $file_content)
{
    $data = '';
    $eol = "\r\n";

    $delimiter = '-------------' . $boundary;

    foreach ($fields as $name => $content) {
        $data .= "--" . $delimiter . $eol
            . 'Content-Disposition: form-data; name="' . $name . "\"" . $eol . $eol
            . $content . $eol;
    }


    $data .= "--" . $delimiter . $eol
        . 'Content-Disposition: form-data; name="files[]"; filename="' . $filename . '"' . $eol
        . 'Content-Type: text/plain' . $eol;

    $data .= $eol;
    $data .= $file_content . $eol;

    $data .= "--" . $delimiter . "--" . $eol;


    return $data;
}


/**
 * geth teh CSRFToken for a givn url
 *
 * @param $url  the url as to be specified in the api calls e.g. https://mychurch.church.tools/?q=api/ajax
 * @return mixed th CSRF Token
 *
 * for internal use
 */
function _getCSRFToken($url)
{
    static $token = array();

    $domain = _getCoreDomain($url);

    if (isset($token[$domain]) && !empty($token[$domain])) {
        return $token[$domain];
    }

    $result = CTV2_sendRequest("GET", "https://$domain/api/csrftoken", array(), array());
    // toto handle failure
    $token[$domain] = $result['data'];
    return $token[$domain];
}


/**
 * Submit a post with multipe parts to be used for CT API V1
 *
 * @param $domain    the domain of the ct instanc e.g. https://mychurch.church.tools
 * @param $url       the url as to be specified in the api calls e.g. https://mychurch.church.tools/?q=api/ajax
 * @param $content   the content to be sent
 * @param $delimiter the delimiter for the parts
 * @return mixed     todo
 */

function CTV1_sendRequestMultipart($domain, $url, $content, $delimiter)
{
    $options = array(
        'http' => array(
            'header' => "Cookie: " . _getCookies($domain) . "\r\nContent-Type: multipart/form-data; boundary=" . $delimiter . "\r\nContent-Length: " . strlen($content) . "\r\n",
            'method' => 'POST',
            'content' => $content
        )
    );
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    $obj = json_decode($result, true);
    if (isset($obj->status) && $obj->status == 'error') {
        echo "There is an error: $obj->message";
    }
    _saveCookies($domain, $http_response_header);
    return $obj;
}


/**
 * send a request for CT API V1
 *
 * note this is always a post request
 *
 * @param $domain - The
 * @param $url
 * @param $data
 * @param bool $csrf_token_required
 * @return mixed
 *
 *
 *
 */
function CTV1_sendRequest($domain, $url, $data, $csrf_token_required = true)
{
    $header = "Cookie: " . _getCookies($domain) . "\r\nContent-type: application/x-www-form-urlencoded\r\n";
    if ($csrf_token_required) {
        $header .= "csrf-token: " . _getCSRFToken($domain) . "\r\n";
    }

    $options = array(
        'http' => array(
            'header' => $header,
            'method' => 'POST',
            'content' => http_build_query($data),
        )
    );
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    $obj = json_decode($result, true);
    if (!isset($obj)) {
        return [
            'status' => 'fail',
            'message' => $result ? $result : $http_response_header[0],
            'response' => ['data' => []],  // only to provide the empty result
            'response_header' => $http_response_header,
            'request' => [
                'url' => $url,
                'data' => $data,
            ]
        ];
    }
    _saveCookies($domain, $http_response_header);
    return $obj;
}


/**
 *
 * send a request to the CT V2 API
 *
 * @param $method HTTP-Method  or Array with parameters
 * @param $url the url
 * @param $query array data to be encodd in the url
 * @param $body array to be sent as JSON in the body
 * @return array the response
 *
 * note you can use the explicit parameters or an Array with the Arguments
 *
 * $report = [
 *   'url' => $url ,
 *   'method' => "PATCH",
 *   'data' => [],
 *   'body' => String request body
 * ];
 *
 */

function CTV2_sendRequest($method, $url = null, $query = null, $body = null)
{
    if (is_array($method)) {
        $_method = $method['method'];
        $_url = $method['url'];
        $_data = array_key_exists('data', $method) ? $method['data'] : null;
        $_body = array_key_exists('body', $method) ? $method['body'] : null;

        if (isset($url) or isset($query) or isset($body)) {
            return [
                'status' => 'fail',
                'message' => "fist parameter of CTV2_sendReauest is array but other parameters given",
                'request' => [
                    'url' => $_url,
                    'data' => $query,
                    'body' => $body
                ]
            ];
        }
    } else {
        $_method = $method;
        $_url = $url;
        $_data = $query;
        $_body = $body;
    }

    if (isset ($_data)) {
        $_url = $_url . "?" . http_build_query($_data);
    }

    // todo verify parameters

    $options = array(
        'http' => array(
            'header' => "Cookie: " . _getCookies($_url)
                . "\r\nContent-type: application/json"
                . "\r\naccept: application/json",
            'method' => $_method,
            'content' => json_encode($_body),
            'ignore_errors' => true,
        )
    );

    $context = stream_context_create($options);
    $result = file_get_contents($_url, false, $context);
    $obj = json_decode($result, true);
    if (!isset($obj)) {
        return [
            'status' => 'fail',
            'message' => $result ? $result : $http_response_header[0],
            'response' => ['data' => []],  // only to provide the empty result
            'response_header' => $http_response_header,
            'request' => [
                'url' => $_url,
                'data' => $query,
                'body' => $body
            ]
        ];
    }
    _saveCookies($_url, $http_response_header);
    return $obj;
}

/**
 *
 * perform a paginated CTV2 request. Pagination is controlled with data fields:
 *
 * 'page'    = the first page tobe retrieved
 * 'limit    = the amount of results per page
 *
 * optional:
 * 'lastpage = the last page to be retrieved. Without this page all remaining pages are retrieved
 *
 * @param array $report an array like this:
 *
 * ```php
 * $report = [
 *   'method' => 'GET',
 *   'url' => "$ctdomain/api/persons",
 *   'data' => [
 *      'page' => 1,
 *      'limit' => 50,
 *      'lastpage' => 10
 *     ]
 * ];
 * ```
 */
function CTV2_sendRequestWithPagination(array $report)
{
    $resultdata = [];
    $response = [];

    if (array_key_exists('lastpage', $report['data'])) {
        $lastpage = min($report['data']['lastpage'], $report['data']['page']);
        $requestedlastpage = $lastpage;
    }
    else{
        $lastpage = $report['data']['page'];
        $requestedlastpage = 1000000;
    }

    while ($lastpage >= $report['data']['page']) {

        // echo "\nreading {$report['data']['page']} of $lastpage";
        $response = CTV2_sendRequest($report);

        $resultdata = array_merge($resultdata, $response['data']);
        // obey 'lastpage' in request
        $lastpage = min($response['meta']['pagination']['lastPage'], $requestedlastpage);
        $report['data']['page'] = $response['meta']['pagination']['current'] + 1;
    }

    $response['data'] = $resultdata;
    return $response;
}

    /**
     *
     * login to Churchtools using an access token
     *
     * note that the login is valid as long as the script runs. The session is
     * not preserverd oveer multiple invocations (as the cookies are not saved)
     *
     * @param $domain
     * @param $token
     * @param $id
     * @return bool
     */
    function CT_login($domain, $token, $id)
    {
        $url = $domain . 'login/ajax';

        // Now use token to login
        $data = array(
            'func' => 'loginWithToken',
            'token' => $token,
            'id' => $id,
            'directtool' => 'API Tools'
        );
        $result = CTV1_sendRequest($domain, $url, $data, false);
        return $result;
    }


    /**
     *
     * login to Churchtools using credentials
     *
     * note that the login is valid as long as the script runs. The session is
     * not preserverd oveer multiple invocations (as the cookies are not saved)
     *
     * @param $domain
     * @param $email    username or email
     * @param $pw       password
     * @return Array 'status' => 'success'| 'fail'
     */
    function CT_loginAuth($domain, $email, $pw)
    {
        $url = $domain . '/?q=login/ajax';

        // Now use creds to login
        $data = array(
            'func' => 'login',
            'email' => $email,
            'password' => $pw,
            'directtool' => 'API Tools'
        );
        $result = CTV1_sendRequest($domain, $url, $data, false);
        return ($result);
    }

    /**
     * terminate the current sessio
     *
     * @param $domain
     */
    function CT_logout($domain)
    {
        $url = $domain . '/?q=login/ajax';

        $data = array('func' => 'logout');
        $result = CTV1_sendRequest($domain, $url, $data);
    }
