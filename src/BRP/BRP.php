<?php

namespace Webien\Site\BRP;

// BRP SYSTEMS API
// DOCS API V3: https://member24.brpsystems.com/brponline/external/documentation/api3?key=cfc527d02f0b4a74af165a7143da6b5a


use Brick\PhoneNumber\PhoneNumber;
use Brick\PhoneNumber\PhoneNumberParseException;


class BRP
{
    public static $apis = [
        // Old: https://ssl2.brpsystems.se/member24gog/api
        // New: https://member24.brpsystems.com/brponline/api

        'production' => [
            'url' => 'https://member24.brpsystems.com/brponline/api',
            'ver2_key' => '61733b22efa5400c9bddb0aae04af058'
        ],
        'test' => [
            'url' => 'https://member24.brpsystems.com/brponline/api',
            'ver2_key' => '61733b22efa5400c9bddb0aae04af058'
        ]
    ];

    const DIRECT_DEBIT_SE_COMPANY_ID = 2;

    public $api = null;
    public $order = null;
    public $customerPassword = null;
    public $customer = null;
    public $ver2Person = null;
    public $ver2PersonAccessToken = null;
    public $directDebit = null;
    public $customerLogin = null;
    public $payment = null;
    public $receipt = null;
    public $signatureCases = null;
    public $personFromSSN = null;

    public $errorResponse;

    /* -----------------------------------------------------------
    |
    |---------------------------------------------------------- */
    public function __construct ()
    {
        $apiVersion = self::isDev() ? 'test' : 'production';
        $this->api = self::$apis[$apiVersion];
    }

    /* -----------------------------------------------------------
    | Check environment.
    |---------------------------------------------------------- */
    public static function isDev()
    {
        if ($_SERVER['REMOTE_ADDR'] === '127.0.0.1') return true;
        if (strpos($_SERVER['SERVER_NAME'], '.test') !== false) return true;
        if (strpos($_SERVER['SERVER_NAME'], '.dev') !== false) return true;
        if ($_ENV['WP_ENV'] === 'development') return true;
        return false;
    }
    
    
    /* -----------------------------------------------------------
    | Create BRP order.
    |---------------------------------------------------------- */
    public function orderCreate ($data)
    {
        $businessUnit = $data['businessUnit'];

        $this->order = $this->cURL('/ver3/orders', [
            'businessUnit' => $businessUnit
        ]);

        return $this->order;
    }

    public function getVisitors() {
        $transient = get_transient('currentvisitors');
        if(!empty($transient)) {
            return $transient;
        }

        $cust = (object)['email' => 'integration.api@Member24.se'];
        $login = $this->customerAuthLogin($cust, '100literhallon');
        if ($this->errorResponse) {
            return null;
        }


        $accessToken = null;
        if ($this->customerLogin) {
            $accessToken = $this->customerLogin->access_token;
        }

        if (empty($accessToken)) {
            elog('Empty access token');
            return null;
        }

        $visitors = $this->cURL('/ver3/services/exporttemplate/7/', null, [
            "Authorization: Bearer $accessToken"
        ]);

        set_transient('currentvisitors', $visitors->result, 60 * 5); // 5 minuter
        return $visitors->result;
    }

    /* -----------------------------------------------------------
    | Add subscription to BRP order.
    |---------------------------------------------------------- */
    public function orderAddSubscription ($order, $data)
    {
        $subscriptionProduct = $data['subscriptionProduct'];
        $birthDate = $data['birthDate'];

        $this->order = $this->cURL('/ver3/orders/' . $order->id . '/items/subscriptions', [
            'subscriptionProduct' => $subscriptionProduct,
            'birthDate' => $birthDate,
        ]);

        return $this->order;
    }

    /* -----------------------------------------------------------
    | Create BRP customer.
    |---------------------------------------------------------- */
    public function customerCreate ($businessUnit, $data)
    {
        $password = uniqid();
        $mobilePhone = $this->getMobilePhoneElements($data['mobile']);

        $firstName = $data['firstName'];
        $lastName = $data['lastName'];
        $sex = $data['sex'];
        $ssn = $data['ssn'];
        $birthDate = $data['birthDate'];
        $email = $data['email'];
        $street = $data['street'];
        $postalCode = $data['postalCode'];
        $city = $data['city'];

        $this->customer = $this->cURL('/ver3/customers', [
            'businessUnit' => $businessUnit,
            'customerType' => 1,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'sex' => $sex,
            'password' => $password,
            'ssn' => $ssn,
            'birthDate' => $birthDate,
            'email' => $email,
            'shippingAddress' => [
                'street' => $street,
                'postalCode' => $postalCode,
                'city' => $city,
                'country' => [
                    'id' => 205,
                    'name' => 'Sverige',
                    'alpha2' => 'SE'
                ]
            ],
            'mobilePhone' => [
                'number' => $mobilePhone->number,
                'countryCode' => $mobilePhone->countryCode
            ]
        ]);
        $this->customerPassword = $password;

        $return = new \stdClass;
        $return->customer = $this->customer;
        $return->password = $this->customerPassword;

        return $return;
    }

    /* -----------------------------------------------------------
    | Convert mobile phone number to number and country code.
    |---------------------------------------------------------- */
    protected function getMobilePhoneElements ($number)
    {
        /* -----------------------------------------------------------
        | Remove all characters except plus sign and digits.
        |---------------------------------------------------------- */
        $number = preg_replace('/[^+0-9]/', '', $number);

        $elements = new \stdClass;
        $elements->number = $number;
        $elements->countryCode = 46;

        $char0 = substr($number, 0, 1);
        if ($char0 == '+') {
            try {
                $phoneNumber = PhoneNumber::parse($number);
                if ($phoneNumber->isValidNumber()) {
                    $elements->number = strval($phoneNumber->getNationalNumber());
                    $elements->countryCode = $phoneNumber->getCountryCode();
                }
            }
            catch (PhoneNumberParseException $e) {
                // 'The string supplied is too short to be a phone number.'
            }
        }
        /* -----------------------------------------------------------
        | Don't remove the initial zero, since BRP's API specified
        | phone number with leading zero.
        |---------------------------------------------------------- */
        // else if ($char0 == '0') {
        //     $elements->number = substr($number, 1);
        // }
        return $elements;
    }

    /* -----------------------------------------------------------
    | Check if person exists, create if person wasn't found.
    |---------------------------------------------------------- */
    public function loadOrCreateVer2Person ($businessUnit, $data)
    {
        $response = $this->cURL('/ver2/persons.json?apikey='.$this->api['ver2_key'].'&email='.$data['email']);

        /* -----------------------------------------------------------
        | Return person if it exists in the business unit.
        |---------------------------------------------------------- */
        if (!empty($response->persons) && is_array($response->persons) && count($response->persons)) {
            $personsInBusinessUnit = array_values(array_filter($response->persons, function ($person) use ($businessUnit) {
                if ($person->businessunit->id == $businessUnit) {
                    return true;
                }

                if (is_array($person->businessunits)) {
                    return count(array_filter($person->businessunits, function ($personBusinessUnit) use ($businessUnit) {
                            return $personBusinessUnit->id == $businessUnit;
                        })) > 0;
                }

                return false;
            }));

            if (count($personsInBusinessUnit)) {
                $this->ver2Person = $personsInBusinessUnit[0];
                return $this->ver2Person;
            }
        }

        return $this->createVer2Person($businessUnit, $data);
    }

    /* -----------------------------------------------------------
    | Create person.
    |---------------------------------------------------------- */
    protected function createVer2Person ($businessUnit, $data)
    {
        $email = $data['email'];
        $firstName = $data['firstName'];
        $lastName = $data['lastName'];

        if (empty($email) || empty($firstName) || empty($lastName)) {
            return null;
        }

        $mobile = $data['mobile'];
        $street = $data['street'];
        $postalCode = $data['postalCode'];
        $city = $data['city'];
        $ssn = $data['ssn'];

        $body = [
            'businessunitid' => $businessUnit,
            'firstname' => $firstName,
            'lastname' => $lastName,
            'email' => $email,
            'password' => uniqid()
        ];
        if (!empty($mobile)) {
            $body['mobilephone'] = $mobile;
        }
        if (!empty($street)) {
            $body['shippingstreet'] = $street;
            $body['billingstreet'] = $street;
        }
        if (!empty($zipcode)) {
            $body['shippingpostal'] = $postalCode;
            $body['billingpostal'] = $postalCode;
        }
        if (!empty($city)) {
            $body['shippingcity'] = $city;
            $body['billingcity'] = $city;
        }
        if (!empty($ssn)) {
            $body['ssn'] = $ssn;
        }

        $response = $this->cURL('/ver2/persons.json?apikey='.$this->api['ver2_key'], $body);

        if (empty($response->person)) {
            return null;
        }

        $this->ver2Person = $response->person;
        return $response->person;
    }

    /* -----------------------------------------------------------
    | Create person.
    |---------------------------------------------------------- */
    public function updateVer2Person ($person, $data)
    {
        $params = [];
        foreach ($data as $key => $value) {
            $params[] = "$key=$value";
        }
        $response = $this->cURL('/ver2/persons/'.$person->id.'.json?apikey='.$this->api['ver2_key'].'&'.implode('&', $params), null, null, 'PUT', true);

        if (empty($response->person)) {
            return null;
        }

        $this->ver2Person = $response->person;
        return $response->person;
    }

    /* -----------------------------------------------------------
    | Get person's access token.
    |---------------------------------------------------------- */
    public function getVer2PersonAccessToken ($person)
    {
        $token = $this->cURL('/ver2/generateapi3token.json?apikey='.$this->api['ver2_key'], [
            'personid' => $person->id
        ]);

        if (empty($token)) {
            return null;
        }

        $this->ver2PersonAccessToken = $token;

        return $token;
    }

    /* -----------------------------------------------------------
    | Get person by ID.
    |---------------------------------------------------------- */
    public function getVer2PersonById ($id)
    {
        $response = $this->cURL('/ver2/persons.json?apikey=' . $this->api['ver2_key'] . '&id=' . $id);

        return $response;
    }

    /* -----------------------------------------------------------
    | Get customer access token.
    |---------------------------------------------------------- */
    public function customerAuthLogin ($customer, $password)
    {
        $email =  $customer->email;

        $this->customerLogin = $this->cURL('/ver3/auth/login', [
            'username' => $email,
            'password' => $password
        ]);

        /* -----------------------------------------------------------
        | If login fails the API responds with nothing.
        | Manually create error response.
        |---------------------------------------------------------- */
        if (!$this->customerLogin) {
            $this->errorResponse = new \stdClass;
            $this->errorResponse->errorCode = 'Authentication failed';
        }

        return $this->customerLogin;
    }

    /* -----------------------------------------------------------
    | Get customer's direct debits.
    |---------------------------------------------------------- */
    public function getDirectDebitsSe ($customer, $accessToken)
    {
        $directDebits = $this->cURL('/ver3/customers/' . $customer->id . '/consents/sedirectdebits', null, [
            "Authorization: Bearer $accessToken"
        ]);

        if (!$directDebits || !count($directDebits)) {
            return [];
        }

        /* -----------------------------------------------------------
        | Return the first direct debit for correct company.
        |---------------------------------------------------------- */
        $matchingDirectDebits = array_values(array_filter($directDebits, function ($item) {
            return $item->company->id == self::DIRECT_DEBIT_SE_COMPANY_ID;
        }));

        return $matchingDirectDebits;
    }

    /* -----------------------------------------------------------
    | Create mandate to pay with swedish autogiro.
    |---------------------------------------------------------- */
    public function createDirectDebitSe ($customer, $data, $accessToken)
    {
        $clearingNumber = $data['clearingNumber'];
        $accountNumber = $data['accountNumber'];

        $this->directDebit = $this->cURL('/ver3/customers/' . $customer->id . '/consents/sedirectdebits', [
            'company' => self::DIRECT_DEBIT_SE_COMPANY_ID,
            'bankAccount' => [
                'clearingNumber' => $clearingNumber,
                'accountNumber' => $accountNumber
            ]
        ], [
            "Authorization: Bearer $accessToken"
        ]);

        /* -----------------------------------------------------------
        | If login fails the API responds with nothing.
        | Manually create error response.
        |---------------------------------------------------------- */
        if (!$this->directDebit) {
            $this->errorResponse = new \stdClass;
            $this->errorResponse->errorCode = 'Kunde inte skapa autogiro';
        }

        return $this->directDebit;
    }

    /* -----------------------------------------------------------
    | Update mandate to pay with swedish autogiro.
    |---------------------------------------------------------- */
    public function updateDirectDebitSe ($customer, $id, $data, $accessToken)
    {
        $clearingNumber = $data['clearingNumber'];
        $accountNumber = $data['accountNumber'];

        $this->directDebit = $this->cURL('/ver3/customers/' . $customer->id . '/consents/sedirectdebits/' . $id, [
            'bankAccount' => [
                'clearingNumber' => $clearingNumber,
                'accountNumber' => $accountNumber
            ]
        ], [
            "Authorization: Bearer $accessToken"
        ], 'PUT');

        /* -----------------------------------------------------------
        | If login fails the API responds with nothing.
        | Manually create error response.
        |---------------------------------------------------------- */
        if (!$this->directDebit) {
            $this->errorResponse = new \stdClass;
            $this->errorResponse->errorCode = 'Kunde inte uppdatera autogiro';
        }

        return $this->directDebit;
    }

    /* -----------------------------------------------------------
    | Update existing ot create new direct debit.
    |---------------------------------------------------------- */
    function updateOrCreateDirectDebitSe ($customer, $data, $accessToken)
    {
        /* -----------------------------------------------------------
        | Get existing direct debits.
        |---------------------------------------------------------- */
        $directDebits = $this->getDirectDebitsSe($customer, $accessToken);

        /* -----------------------------------------------------------
        | Create new if none existed.
        |---------------------------------------------------------- */
        if (empty($directDebits)) {
            return $this->createDirectDebitSe($customer, $data, $accessToken);
        }

        /* -----------------------------------------------------------
        | Update existing direct debit.
        |---------------------------------------------------------- */
        return $this->updateDirectDebitSe($customer, $directDebits[0]->id, $data, $accessToken);
    }

    /* -----------------------------------------------------------
    | Set order customer.
    |---------------------------------------------------------- */
    public function orderSetCustomer ($order, $customer, $accessToken)
    {
        $customerId =  $customer->id;

        $this->order = $this->cURL('/ver3/orders/' . $order->id, [
            'customer' => $customerId
        ], [
            "Authorization: Bearer $accessToken"
        ], 'PUT');

        return $this->order;
    }

    /* -----------------------------------------------------------
    | Generate payment link.
    |---------------------------------------------------------- */
    public function paymentCreate ($order, $returnUrl, $accessToken)
    {
        $this->payment = $this->cURL('/ver3/services/generatelink/payment', [
            'order' => $order->id,
            'returnUrl' => $returnUrl
        ], [
            "Authorization: Bearer $accessToken"
        ]);

        return $this->payment;
    }

    /* -----------------------------------------------------------
    | Get payment receipt.
    |---------------------------------------------------------- */
    public function getReceipt ($receiptId, $accessToken)
    {
        $this->receipt = $this->cURL('/ver3/receipts/' . $receiptId, null, [
            "Authorization: Bearer $accessToken"
        ]);

        return $this->receipt;
    }

    /* -----------------------------------------------------------
    | Get signature cases.
    |---------------------------------------------------------- */
    public function getSignatureCases ($customer, $accessToken)
    {
        $this->signatureCases = $this->cURL('/ver3/customers/' . $customer->id . '/signaturecases', null, [
            "Authorization: Bearer $accessToken"
        ]);

        return $this->signatureCases;
    }

    /* -----------------------------------------------------------
    | Get person info from SSN.
    |---------------------------------------------------------- */
    public function getPersonLookupFromSSN ($businessUnit, $ssn)
    {
        $ssn = preg_replace('/[^-0-9]/', '', $ssn);

        $this->personFromSSN = $this->cURL('/ver3/services/personlookup?businessUnit=' . $businessUnit . '&ssn=' . $ssn);
        // https://ssl2.brpsystems.se/member24gog/api/ver3/services/personlookup?businessUnit=162&ssn=701120-6997
        // https://member24.brpsystems.com/brponline/api/ver3/services/personlookup?businessUnit=162&ssn=701120-6997

        return $this->personFromSSN;
    }

    /* -----------------------------------------------------------
    | Get person info from SSN.
    |---------------------------------------------------------- */
    public function getPersonFromSSN ($businessUnit, $ssn)
    {
        $response = $this->getPersonsFromSSN($ssn);

        if (empty($response->persons) || !is_array($response->persons)) {
            return null;
        }

        // /* -----------------------------------------------------------
        // | Return person if it exists in the business unit.
        // |---------------------------------------------------------- */
        // $personsInBusinessUnit = array_values(array_filter($response->persons, function ($person) use ($businessUnit) {
        //     return $person->businessunit->id == $businessUnit;
        // }));

        // if (!count($personsInBusinessUnit)) {
        //     return null;
        // }

        // return $personsInBusinessUnit[0];

        return $response->persons[0];
    }

    /* -----------------------------------------------------------
    | Get persons from SSN.
    | Uses API ver 2.
    |---------------------------------------------------------- */
    public function getPersonsFromSSN ($ssn)
    {
        if (preg_match('/^\d{10}$/', $ssn)) {
            $ssn = preg_replace('/^(\d{6})(\d{4})$/', '$1-$2', $ssn);
        }

        if (!preg_match('/^\d{6}-\d{4}$/', $ssn)) {
            return null;
        }

        return $this->cURL('/ver2/persons.json?apikey=' . $this->api['ver2_key'] . '&personnumber=' . $ssn);
    }

    /* -----------------------------------------------------------
    | Get customer.
    |---------------------------------------------------------- */
    public function getCustomer ($customerId, $accessToken = null)
    {
        if (!$accessToken && $this->ver2PersonAccessToken) {
            $accessToken = $this->ver2PersonAccessToken->access_token;
        }

        return $this->cURL('/ver3/customers/' . $customerId, null, [
            "Authorization: Bearer $accessToken"
        ]);
    }

    /* -----------------------------------------------------------
    | Get customer subscriptions.
    |---------------------------------------------------------- */
    public function getCustomerSubscriptions ($customerId, $accessToken = null)
    {
        if (!$accessToken && $this->ver2PersonAccessToken) {
            $accessToken = $this->ver2PersonAccessToken->access_token;
        }

        return $this->cURL('/ver3/customers/' . $customerId . '/subscriptions', null, [
            "Authorization: Bearer $accessToken"
        ]);
    }

    /* -----------------------------------------------------------
    | Get subscription
    |---------------------------------------------------------- */
    public function getSubscriptions ()
    {
        return $this->cURL('/ver3/products/subscriptions');
    }

    /* -----------------------------------------------------------
    | Get person groups
    |---------------------------------------------------------- */
    public function getPersonGroups ()
    {
        // https://member24.brpsystems.com/brponline/api/ver2/persongroups.json?apikey=61733b22efa5400c9bddb0aae04af058
        return $this->cURL('/ver2/persongroups.json?apikey=' . $this->api['ver2_key']);
    }

    /* -----------------------------------------------------------
    | Get business units (gyms).
    |---------------------------------------------------------- */
    public function getBusinessUnits ()
    {
        return $this->cURL('/ver3/businessunits');
    }

    /* -----------------------------------------------------------
    | cURL.
    |---------------------------------------------------------- */
    protected function cURL ($url, $data = null, $headers = null, $postMethod = 'POST', $usePostMethod = false)
    {
        $this->errorResponse = null;

        $ch = $this->getCURL($url, $data, $headers, $postMethod, $usePostMethod);
        $json = curl_exec($ch);
        curl_close($ch);

        elog('--- cURL response ---');
        elog($json);
        elog("\n\n");

        if (empty($json)) {
            $errorResponse = new \stdClass;
            $errorResponse->errorCode = 'No response';
            $this->errorResponse = $errorResponse;
        }

        $response = json_decode($json);

        if (!empty($response->errorCode)) {
            $this->errorResponse = $response;
        }

        if (!empty($response->errors)) {
            $this->errorResponse = $response;
        }

        return $response;
    }

    protected function getCURL ($url, $data = null, $headers = null, $postMethod = 'POST', $usePostMethod = false)
    {
        if (strpos($url, 'http') !== 0) {
            $url = $this->api['url'] . $url;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $method = 'GET';
        $json_data = null;

        // POST data
        if ($data) {
            $method = $postMethod;
            $json_data = json_encode($data);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $postMethod);
            $headers = array_merge([
                'Accept: application/json',
                'Content-Type: application/json',
                'Content-Length: ' . strlen($json_data)
            ], $headers ? $headers : []);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        }
        else {
            if ($usePostMethod) {
                $method = $postMethod;
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $postMethod);
            }
        }

        // Headers.
        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        elog('--- cURL ---');
        elog([
            'url' => $url,
            'method' => $method,
            'data' => $json_data ? json_decode($json_data, true) : null,
            'headers' => $headers
        ]);

        return $ch;
    }
}