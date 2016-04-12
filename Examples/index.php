<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once '../OroWSSEKey.php';

class Params
{
    # API key that you can get on `System/ User Management/ Users` page using `Generate key` button
    const API_KEY = '';

    # Full domain URL without last slash
    const HOST = 'http://test.crm/app_dev.php';

    # OroCRM user name for request sending
    const USER_NAME = 'userName';
}

include_once 'CallTestCase.php';

/*
curl -i -H 'Content-Type: application/json' -H 'Authorization: WSSE profile="UsernameToken"' -H 'X-WSSE: UsernameToken Username="fusion", PasswordDigest="MMgUQ0fkTa5tSLcr5Z7hJTLDovk=", Nonce="ODdiYmQ1NjE4ZjQ4MDA2MA==", Created="2015-04-01T15:56:29Z"' http://oro-dev.icedesign.com.au/api/rest/latest/users
*/
