<?php

class OroWSSEKey
{
    /** @var string */
    protected $algorithm;

    /** @var bool */
    protected $encodeHashAsBase64;

    /** @var int */
    protected $iterations;

    /** @var resource */
    protected $curl;

    /** @var array */
    protected $curlTypes = ['GET', 'POST', 'PUT', 'DELETE'];

    /**
     * @param string $userName
     * @param string $apiKey
     * @param string $algorithm
     * @param bool   $encodeHashAsBase64
     * @param int    $iterations
     */
    public function __construct($userName, $apiKey, $algorithm = 'sha1', $encodeHashAsBase64 = true, $iterations = 1)
    {
        $this->algorithm          = $algorithm;
        $this->encodeHashAsBase64 = $encodeHashAsBase64;
        $this->iterations         = $iterations;
        $this->apiKey             = $apiKey;
        $this->userName           = $userName;
    }

    /**
     * @param string $raw
     * @param string $salt
     *
     * @return string
     */
    public function encodePassword($raw, $salt)
    {
        $salted = $this->mergePasswordAndSalt($raw, $salt);

        $digest = hash($this->algorithm, $salted, true);

        for ($i = 1; $i < $this->iterations; $i++) {
            $digest = hash($this->algorithm, $digest . $salted, true);
        }

        return $this->encodeHashAsBase64 ? base64_encode($digest) : bin2hex($digest);
    }

    /**
     * @param string $password
     * @param string $salt
     *
     * @return string
     */
    protected function mergePasswordAndSalt($password, $salt)
    {
        if (empty($salt)) {
            return $password;
        }

        if (false !== strrpos($salt, '{') || false !== strrpos($salt, '}')) {
            throw new \InvalidArgumentException('Cannot use { or } in salt.');
        }

        return $password . '{' . $salt . '}';
    }

    /**
     * @return array
     */
    public function getHeaders($param)
    {
        $result  = [];
        #$created = $this->getDate('America/Los_Angeles');
        $created = $this->getDate('Europe/Kiev');

        $prefix = gethostname();
        $nonce  = base64_encode(substr(md5(uniqid($prefix . '_', true)), 0, 16));
        $salt   = '';

        $passwordDigest = $this->encodePassword(
            sprintf(
                '%s%s%s',
                base64_decode($nonce),
                $created,
                $this->apiKey
            ),
            $salt
        );

        if (!empty($param)) {
            array_push($result, 'Content-Length: ' . strlen($param));
        }

        array_push($result, 'Content-Type: application/json');
        array_push($result, 'Authorization: WSSE profile="UsernameToken"');
        array_push($result,
            sprintf(
                'X-WSSE: UsernameToken Username="%s", PasswordDigest="%s", Nonce="%s", Created="%s"',
                $this->userName,
                $passwordDigest,
                $nonce,
                $created
            )
        );

        return $result;
    }

    /**
     * @param string $url
     * @param string $requestType
     * @param array  $request
     * @param bool   $isDebug
     *
     * @return array|string
     */
    public function send($url, $requestType, array $request = [], $isDebug = false)
    {
        $result = [];
        $query  = '';

        if (!empty($request)) {
            $query = json_encode($request);
        }


        $headers    = $this->getHeaders($query);
        $this->curl = curl_init();

        if (!in_array($requestType, $this->curlTypes)) {
            return 'Incorrect request type';
        }

        if($isDebug) {
            curl_setopt($this->curl, CURLOPT_VERBOSE, true);
            $verbose = fopen('1.txt', 'a+');
            curl_setopt($this->curl, CURLOPT_STDERR, $verbose);
        }

        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);

        if ($requestType === 'POST') {
            curl_setopt($this->curl, CURLOPT_POST, true);
        } elseif ($requestType !== 'GET') {
            curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $requestType);
        }

        if (!empty($request)) {
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $query);
        }

        array_push($result, curl_exec($this->curl));
        array_push($result, curl_getinfo($this->curl, CURLINFO_HTTP_CODE));

        if ($isDebug) {
            array_push($result, $query);
            array_push($result, $headers);
            array_push($result, curl_getinfo($this->curl));
        }

        curl_close($this->curl);

        return $result;
    }

    /**
     * Time zone should be server time zone
     *
     * @param string $timeZone
     *
     * @return string
     */
    protected function getDate($timeZone)
    {
        $timeZone = new \DateTimeZone($timeZone);
        $date     = new \DateTime('now', $timeZone);

        return $date->format(\DateTime::ISO8601);
    }
}
