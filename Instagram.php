<?php

require_once 'Encrypt.php';

class Instagram{

    public $session;
    public $user_agent = ['user-agent: Mozilla/5.0 (X11; Linux x86_64; rv:78.0) Gecko/20100101 Firefox/78.0'];
    public $publicKey, $keyId, $version;
    public $cookie;

    const login = 'login';
    const password = 'password';

    public function auth(){
        $cookie_str = [];
        foreach ($this->cookie as $key => $item)
            $cookie_str[] = "{$key}={$item}";
        $cookie_str = implode('; ', $cookie_str);

        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
            'X-IG-WWW-Claim: 0',
            'X-Requested-With: XMLHttpRequest',
            'X-Instagram-AJAX: fd2afcb4bfab',
            'X-ig-app-id: 936619743392459',
            'X-CSRFToken: '.$this->cookie['csrftoken'],
            'X-ASBD-ID: 437806',
            'Cookie: '.$cookie_str,
            $this->user_agent[0]
        ];
        var_dump((new Encrypt(self::password, $this->publicKey, $this->keyId))->generateEncPassword());
        $params = [
            'username' => self::login,
            //$password, $publicKey, $keyId
            'enc_password' => (new Encrypt(self::password, $this->publicKey, $this->keyId))->generateEncPassword(),
            'queryParams' => '{}',
            'optIntoOneTap' => 'false',
            'stopDeletionNonce' => '',
            'trustedDeviceRecords' => '{}'
        ];
        //$params = json_encode($params);
        $crl = curl_init();
        curl_setopt($crl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($crl, CURLOPT_URL, 'https://www.instagram.com/accounts/login/ajax/');
        curl_setopt($crl, CURLOPT_POST,true);
        curl_setopt($crl, CURLOPT_POSTFIELDS, http_build_query($params));
        $rest = curl_exec($crl);
        curl_close($crl);
        var_dump($rest);
    }

    public function run()
    {
        try {
            $this->session = json_decode($this->curl('https://www.instagram.com/data/shared_data/'));
            $this->publicKey = $this->session->encryption->public_key;
            $this->keyId = $this->session->encryption->key_id;
            $this->version = $this->session->encryption->version;
        }catch (Exception $e){
            exit("[!] Ошибка получения сессии!\n");
        }
        $this->cookie = $this->getCookie();
        $this->auth();
    }

    public function getCookie(){
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://www.instagram.com/',
            CURLOPT_HEADER => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLINFO_HEADER_OUT => true,
            CURLOPT_HTTPHEADER => $this->user_agent,
        ]);
        $response = curl_exec($curl);
        $headers  = curl_getinfo($curl);
        $header_content = substr($response, 0, $headers['header_size']);
        curl_close($curl);
        $cookie = [];
        preg_match_all("/Set-Cookie:\s*(?<cookie>[^=]+=[^;]+)/mi", $header_content, $matches);
        foreach ($matches['cookie'] as $c) {
            if ($c = str_replace(['sessionid=""', 'target=""'], '', $c)) {
                $c = explode('=', $c);
                $cookie = array_merge($cookie, [trim($c[0]) => trim($c[1])]);
            }
        }
        if (!isset($cookie['csrftoken'])) {
            //$cookie['csrftoken'];
            exit("[!] Cookie not found");
        }
        return $cookie;
    }

    public function curl($url){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->user_agent);
        $html = curl_exec($ch);
        curl_close($ch);
        return $html;
    }
}

$instagram = new Instagram();
$instagram->run();
