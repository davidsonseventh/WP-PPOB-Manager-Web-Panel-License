<?php
defined('ABSPATH') || exit;

class WPPPOB_API {
    private $provider;
    private $username;
    private $api_key;
    private $base_url;

    public function __construct() {
        $this->provider = get_option('wppob_api_provider', 'digiflazz');
        $this->username = get_option('wppob_api_username');
        $this->api_key = get_option('wppob_api_key'); // Sebaiknya dienkripsi

        if ($this->provider === 'digiflazz') {
            $this->base_url = 'https://api.digiflazz.com/v1/';
        }
        // Tambahkan provider lain di sini
    }

    private function generate_sign($ref_id = '') {
        if ($this->provider === 'digiflazz') {
            return md5($this->username . $this->api_key . $ref_id);
        }
        return '';
    }
    
    private function request($endpoint, $body, $timeout = 45) {
        if (empty($this->username) || empty($this->api_key)) {
            return ['error' => true, 'message' => 'API Username atau API Key belum diatur.'];
        }

        $url = $this->base_url . $endpoint;

        $response = wp_remote_post($url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => json_encode($body),
            'timeout' => $timeout,
        ]);

        if (is_wp_error($response)) {
            return ['error' => true, 'message' => $response->get_error_message()];
        }
        
        return json_decode(wp_remote_retrieve_body($response), true);
    }

    public function get_price_list() {
        $body = [
            'cmd'      => 'prepaid',
            'username' => $this->username,
            'sign'     => $this->generate_sign('pricelist'),
        ];
        return $this->request('price-list', $body, 60);
    }

    public function transaction($sku, $customer_no, $ref_id) {
        $body = [
            'username'       => $this->username,
            'buyer_sku_code' => $sku,
            'customer_no'    => $customer_no,
            'ref_id'         => $ref_id,
            'sign'           => $this->generate_sign($ref_id),
        ];
        return $this->request('transaction', $body);
    }

    public function check_balance() {
        $body = [
            'cmd'      => 'deposit',
            'username' => $this->username,
            'sign'     => $this->generate_sign('depo'),
        ];
        return $this->request('cek-saldo', $body);
    }
}