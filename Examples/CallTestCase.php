<?php

echo '<h3>Incoming Call test case</h3>';


$oroWSSEKey = new OroWSSEKey(Params::USER_NAME, Params::API_KEY);

# By this parameter OroCRM will search contact phone
$phone = '123-456-789';

#Oro use name
$user  = 'admin';

$result = null;

# This request will create call and add information into notice window
$baseUrl = Params::HOST . '/api/rest/latest/calls/adds/calls';
$result = $oroWSSEKey->send($baseUrl . '.json', 'POST', ['phone' => $phone, 'user' => $user], false);

# This request will add duration to the call
#$baseUrl = Params::HOST . '/api/rest/latest/c2cs/updates/calls/durations';
#$result = $oroWSSEKey->send($baseUrl . '.json', 'POST', ['phone' => $phone, 'user' => $user, 'duration' => '00:05:30', 'iden' => $iden], false);


var_dump($result);
