<?php
namespace Webien\Site\NETS;

use Webien\Site\BRP\BRPHelper;

/**
 * Class NETS
 * @package Webien\Site\NETS
 *
 * ····· Docs:
 * https://developer.nexigroup.com/nexi-checkout/en-EU/docs/web-integration/integrate-checkout-on-your-website-embedded/
 * https://developer.nexigroup.com/nexi-checkout/en-EU/api/
 * https://developer.nexigroup.com/nexi-checkout/en-EU/docs/test-card-processing/
 *
 *
 * ····· Defines needs to be added to ex. wp-config.php like:
 *
 * define('NETS_SECRET_KEY_LIVE', 'xxx');
 * define('NETS_CHECKOUT_KEY_LIVE', 'xxx');
 * define('NETS_SECRET_KEY_TEST', 'xxx');
 * define('NETS_CHECKOUT_KEY_TEST', 'xxx');
 *
 */

class NETS
{
    private $dibsApiBase;
    private $checkoutJsUrl;

    public function __construct()
    {
        //add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);

        if(1 === 1){
            // TEST URL:s
            $this->checkoutJsUrl = 'https://test.checkout.dibspayment.eu/v1/checkout.js?v=1';
            $this->dibsApiBase = 'https://test.api.dibspayment.eu/v1';
        } else {
            $this->checkoutJsUrl = 'https://checkout.dibspayment.eu/v1/checkout.js?v=1';
            $this->dibsApiBase = 'https://api.dibspayment.eu/v1';
        }

    }


    public function enqueue_scripts()
    {

    }

    public function render($data = [], $view = 'form')
    {
        ob_start();
        include __DIR__ . '/templates/view.' . $view . '.php';
        return ob_get_clean();
    }

    public function createPayment($data = [])
    {
        $ch = curl_init( $this->dibsApiBase . '/payments');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: ' . NETS_SECRET_KEY_TEST)); // Defined in wp-config.php
        $result = curl_exec($ch);

        return json_decode($result);
    }



}