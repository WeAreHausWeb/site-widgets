<?php

namespace Webien\Site\Membership;

use Webien\Site\BRP;

class MembershipFormSubmission
{
    public $inputs;
    public $posted = false;
    public $errors = null;
    public $returnedFromPayment = false;
    public $paymentFailure = false;
    public $receipt = null;
    public $signatureCases = null;

    function __construct ()
    {
        $this->validateInputParams();
        $this->checkFormSubmission();
    }

    /* -----------------------------------------------------------
    | Validate input parameters.
    |---------------------------------------------------------- */
    public function validateInputParams ()
    {
        /* -----------------------------------------------------------
        | Customer ID may be passed instead of form.
        |---------------------------------------------------------- */
        $customerId = empty($_REQUEST['customerId']) ? null : sanitize_text_field($_REQUEST['customerId']);

        $inputs = [
            'gym' => [
                'required' => true
            ],
            'type' => [
                'required' => true
            ],
            'subscription' => [
                'required' => true
            ],
            'paymentType' => [
                'required' => true
            ],
            'noBinding' => [
                'required' => false
            ],
            'autogiroBankClr' => [
                'required' => false
            ],
            'autogiroBankAccount' => [
                'required' => false
            ],
            'customerId' => [
                'required' => $customerId != null
            ],
            'name1' => [
                'required' => $customerId == null
            ],
            'name2' => [
                'required' => $customerId == null
            ],
            'ssn' => [
                'required' => $customerId == null
            ],
            'email' => [
                'required' => $customerId == null
            ],
            'phone' => [
                'required' => false
            ],
            'phoneMobile' => [
                'required' => $customerId == null
            ],
            'phoneWork' => [
                'required' => false
            ],
            'street' => [
                'required' => $customerId == null
            ],
            'zipCode' => [
                'required' => $customerId == null
            ],
            'city' => [
                'required' => $customerId == null
            ],
            'terms' => [
                'required' => $customerId == null
            ],
            'privacy' => [
                'required' => $customerId == null
            ],
        ];

        /* -----------------------------------------------------------
        | Input fields.
        |---------------------------------------------------------- */
        array_walk($inputs, function(&$item, $key) {
            $item['value'] = null;
            $item['valid'] = isset($item['required']) && $item['required'] ? false : true;

            /* -----------------------------------------------------------
            | Set value and default to valid if parameter exists.
            |---------------------------------------------------------- */
            if (isset($_REQUEST[$key])) {
                $item['value'] = sanitize_text_field(trim($_REQUEST[$key]));
                $item['valid'] = true;

                /* -----------------------------------------------------------
                | Required inputs must not be empty.
                |---------------------------------------------------------- */
                if (isset($item['required']) && $item['required']) {
                    if (!strlen($item['value'])) {
                        $item['valid'] = false;
                    }
                }

                /* -----------------------------------------------------------
                | No HTML or PHP tags.
                |---------------------------------------------------------- */
                if (strip_tags($item['value']) !== $item['value']) {
                    $item['valid'] = false;
                }
            }
        });

        /* -----------------------------------------------------------
        | Gym.
        |---------------------------------------------------------- */
        if (isset($_REQUEST['gym'])) {
            /* -----------------------------------------------------------
            | Gym must be digits only.
            |---------------------------------------------------------- */
            preg_match("/^\d+$/", $inputs['gym']['value'], $matches);
            if (count($matches) != 1) {
                $inputs['gym']['valid'] = false;
            }
        }

        /* -----------------------------------------------------------
        | Payment type.
        |---------------------------------------------------------- */
        if (isset($_REQUEST['paymentType'])) {
            /* -----------------------------------------------------------
            | Must be a supported value.
            |---------------------------------------------------------- */
            $paymentTypes = ['card', 'autogiro'];
            if (in_array($inputs['paymentType']['value'], $paymentTypes)) {
                $inputs['paymentType']['valid'] = true;
            }
        }

        /* -----------------------------------------------------------
        | No binding time for membership.
        |---------------------------------------------------------- */
        if (isset($_REQUEST['noBinding'])) {
            if ($inputs['noBinding']['valid']) {
                $inputs['noBinding']['value'] = $inputs['noBinding']['value'] == 'on';

                /* -----------------------------------------------------------
                | No binding time is only available for autogiro payments.
                |---------------------------------------------------------- */
                if ($inputs['noBinding']['value']) {
                    $inputs['noBinding']['valid'] = $inputs['paymentType']['value'] == 'autogiro';
                }
            }
        }

        /* -----------------------------------------------------------
        | Autogiro info required if payment type is autogiro.
        | $autogiroInputs = ['autogiro-ssn1', 'autogiro-ssn2', 'autogiro-bank-clr', 'autogiro-bank-account'];
        |---------------------------------------------------------- */
        $autogiroInputs = ['autogiroBankClr', 'autogiroBankAccount'];
        foreach($autogiroInputs as $key) {
            $item = $inputs[$key];
            if (empty($item['value'])) {
                $item['valid'] = false;
            }
        }

        /* -----------------------------------------------------------
        | Terms.
        |---------------------------------------------------------- */
        $inputs['terms']['valid'] = false;
        if (isset($_REQUEST['terms'])) {
            $inputs['terms']['value'] = $inputs['terms']['value'] == 'on';
            $inputs['terms']['valid'] = $inputs['terms']['value'];
        }

        /* -----------------------------------------------------------
        | Privacy.
        |---------------------------------------------------------- */
        $inputs['privacy']['valid'] = false;
        if (isset($_REQUEST['privacy'])) {
            $inputs['privacy']['value'] = $inputs['privacy']['value'] == 'on';
            $inputs['privacy']['valid'] = $inputs['privacy']['value'];
        }

        /* -----------------------------------------------------------
        | 18 years age limit.
        |---------------------------------------------------------- */
        $ssnParts = $this->getSSNParts($inputs['ssn']['value']);
        $birthDate = $ssnParts ? $this->getBirthDateFromSSN1($ssnParts['ssn1']) : '';
        if (empty($birthDate)) {
            $inputs['ssn']['valid'] = false;
        } else {
            $birthDateTime = strtotime($birthDate);
            $maxBirthDateTime = strtotime(date('Y-m-d') . ' - 18 years');
            if ($birthDateTime > $maxBirthDateTime) {
                $inputs['ssn']['valid'] = false;
            }
        }

        $this->inputs = $inputs;
    }

    /* -----------------------------------------------------------
    | Check if form was submitted.
    |---------------------------------------------------------- */
    protected function checkFormSubmission ()
    {
        // Check return from payment if form was not submitted.
        if (!isset($_REQUEST['submit-membershipform'])) {
            $this->checkReturnFromPayment();
            return;
        }

        // Bail out if we couldn't successfully validate input parameters.
        if (!$this->inputs) {
            return;
        }

        // Post form data if valid request.
        $allInputsValid = !in_array(false, array_column($this->inputs, 'valid'));

        if (!$allInputsValid) {
            return;
        }

        /* -----------------------------------------------------------
        | Create order in BRP.
        |---------------------------------------------------------- */
        $status = $this->postFormData();

        /*-----------------------------------------------------------
        | If order was successfully created – redirect to the
        | payment page.
        |----------------------------------------------------------*/
        $paymentURL = !empty($status['paymentURL']) ? $status['paymentURL'] : null;

        if ($paymentURL) {
            elog($paymentURL);
            wp_redirect($paymentURL);

            exit;
        }

        $this->posted = true;

        if (!empty($status['errors'])) {
            $this->errors = $status['errors'];
        }
    }

    /* -----------------------------------------------------------
    | Check if user returns form payment.
    |---------------------------------------------------------- */
    protected function checkReturnFromPayment ()
    {
        if (empty($_REQUEST['token'])) {
            return;
        }

        $this->returnedFromPayment = true;

        /* -----------------------------------------------------------
        | Payment was unsuccessful, or aborted.
        |---------------------------------------------------------- */
        if (!empty($_REQUEST['error'])) {
            $this->errors = [
                'Felkod ' . sanitize_text_field($_REQUEST['error'])
            ];

            $this->paymentFailure = true;
            return;
        }

        /* -----------------------------------------------------------
        | Bail out if we don't have both order and receipt id.
        |---------------------------------------------------------- */
        if (empty($_REQUEST['orderid']) || empty($_REQUEST['receiptid'])) {
            return;
        }

        /* -----------------------------------------------------------
        | Payment was successful – load receipt and check
        | for signature cases (autogiro).
        |---------------------------------------------------------- */
        $brp = new BRP;
        $accessToken = sanitize_text_field($_REQUEST['token']);
        $receipt = $brp->getReceipt(sanitize_text_field($_REQUEST['receiptid']), $accessToken);

        if ($brp->errorResponse) {
            $status = $this->getErrorsFromResponse($brp->errorResponse);
            $this->errors = $status['errors'];
            return;
        }

        /* -----------------------------------------------------------
        | BRP responds with a receipt even if the access token was
        | not valid. Nullify receipt if it has no customer.
        |---------------------------------------------------------- */
        if (empty($receipt->customer)) {
            $receipt = null;
        }

        if (!$receipt) {
            $this->errors = [
                'Tekniskt fel vid inläsning av kvitto'
            ];

            return;
        }

        $this->receipt = $receipt;

        /* -----------------------------------------------------------
        | Removed 2022-04-20.
        |
        | Signature cases should be signed at the desk.
        |---------------------------------------------------------- */
        // $signatureCases = $brp->getSignatureCases($receipt->customer, $accessToken);
        // if ($brp->errorResponse) {
        //     $status = $this->getErrorsFromResponse($brp->errorResponse);
        //     $this->errors = $status['errors'];
        // } else {
        //     $this->signatureCases = $signatureCases;
        // }
    }

    /* -----------------------------------------------------------
    | Place order in BRP API v3.
    |---------------------------------------------------------- */
    public function postFormData ()
    {
        $brp = new BRP;

        $inputs = $this->inputs;

        $order = $brp->orderCreate([
            'businessUnit' => $inputs['gym']['value']
        ]);

        if ($brp->errorResponse) {
            return $this->getErrorsFromResponse($brp->errorResponse);
        }

        // elog($order);

        $subscriptionProduct = $inputs['subscription']['value'];
        if (!$subscriptionProduct) {
            return [
                'errors' => ['Systemfel – hittade inte rätt produkt']
            ];
        }

        /* -----------------------------------------------------------
        | Get customer if ID was passed.
        |---------------------------------------------------------- */
        $customer = null;
        if ($inputs['customerId']['value']) {
            $ver2PersonAccessToken = $brp->getVer2PersonAccessToken((object) [
                'id' => $inputs['customerId']['value']
            ]);

            if (!$ver2PersonAccessToken) {
                return $this->getErrorsFromResponse($brp->errorResponse);
            }

            $customer = $brp->getCustomer($inputs['customerId']['value']);

            if (!$customer) {
                return $this->getErrorsFromResponse($brp->errorResponse);
            }
        }

        $birthDate = null;

        if ($customer) {
            $birthDate = $customer->birthDate;
        } else {
            $ssnParts = $this->getSSNParts($inputs['ssn']['value']);
            $birthDate = $ssnParts ? $this->getBirthDateFromSSN1($ssnParts['ssn1']) : '';
        }

        $order = $brp->orderAddSubscription($order, [
            'subscriptionProduct' => $subscriptionProduct,
            'birthDate' => $birthDate
        ]);

        if ($brp->errorResponse) {
            return $this->getErrorsFromResponse($brp->errorResponse);
        }

        // elog($order);

        if (!$customer) {
            $customerData = $this->getCustomerData($inputs);
            $customerCreated = $brp->customerCreate($order->businessUnit->id, $customerData);

            // elog($customerCreated);

            /* -----------------------------------------------------------
            | If customer was successfully created – login using
            | e-mail and password.
            |---------------------------------------------------------- */
            if (!$brp->errorResponse) {
                $login = $brp->customerAuthLogin($customerCreated->customer, $customerCreated->password);
                if ($brp->errorResponse) {
                    return $this->getErrorsFromResponse($brp->errorResponse);
                }

                // elog($login);
            }

            /* -----------------------------------------------------------
            | If customer couldn't be created we try to find the person
            | and get an access token using API ver2.
            |---------------------------------------------------------- */
            else {
                $ver2Person = $brp->loadOrCreateVer2Person($order->businessUnit->id, $customerData);
                if (!$ver2Person) {
                    return $this->getErrorsFromResponse($brp->errorResponse);
                }

                $ver2PersonAccessToken = $brp->getVer2PersonAccessToken($ver2Person);
                if (!$ver2PersonAccessToken) {
                    return $this->getErrorsFromResponse($brp->errorResponse);
                }

                $customerCreated = new \stdClass;
                $customerCreated->customer = $ver2Person;
            }

            $customer = $customerCreated->customer;

            // elog($customer);
        }

        $accessToken = null;
        if ($brp->customerLogin) {
            $accessToken = $brp->customerLogin->access_token;
        } else if ($brp->ver2PersonAccessToken) {
            $accessToken = $brp->ver2PersonAccessToken->access_token;
        }

        // elog($accessToken);

        if (empty($accessToken)) {
            return [
                'errors'=> [
                    'Tekniskt fel. Kunde inte auktorisera användare.'
                ]
            ];
        }

        /* -----------------------------------------------------------
        | Autogiro payment.
        |---------------------------------------------------------- */
        if ( $inputs['paymentType']['value'] == 'autogiro' ) {
            $directDebit = $brp->updateOrCreateDirectDebitSe($customer, [
                'clearingNumber' => $inputs['autogiroBankClr']['value'],
                'accountNumber' => $inputs['autogiroBankAccount']['value']
            ], $accessToken);
            if ($brp->errorResponse) {
                return $this->getErrorsFromResponse($brp->errorResponse);
            }

            // elog($directDebit);
        }

        $order = $brp->orderSetCustomer($order, $customer, $accessToken);
        if ($brp->errorResponse) {
            return $this->getErrorsFromResponse($brp->errorResponse);
        }

        // elog($order);

        $uriParts = explode('?', $_SERVER['REQUEST_URI'], 2);
        $returnUrl = 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 's' : '') . "://" . $_SERVER['HTTP_HOST'] . $uriParts[0] . '?token=' . $accessToken;

        elog($returnUrl);

        $payment = $brp->paymentCreate($order, $returnUrl, $accessToken);
        if ($brp->errorResponse) {
            return $this->getErrorsFromResponse($brp->errorResponse);
        }

        /* -----------------------------------------------------------
        | Add persongroup to customer (via API ver2).
        |---------------------------------------------------------- */
        $personGroupIds = !empty($_REQUEST['persongroup_ids']) ? preg_replace('/\s/', '', $_REQUEST['persongroup_ids']) : null;
        if (!empty($personGroupIds)) {
            $ver2PersonWithPersonGroups = $brp->updateVer2Person($customer, [
                'persongroups' => $personGroupIds
            ]);
            elog($ver2PersonWithPersonGroups);
        }

        // elog($payment);

        return [
            'paymentURL' => $payment->url
        ];
    }

    protected function getErrorsFromResponse ($response)
    {
        $return = [
            'errors' => [
                $response->errorCode
            ]
        ];

        if (!empty($response->fieldErrors)) {
            foreach ($response->fieldErrors as $fieldError) {
                $return['errors'][] = $fieldError->field . ': ' . $fieldError->errorCode;
            }
        }
        return $return;
    }

    protected function getSSNParts ($ssn) {
        $ssns = explode('-', $ssn);
        if (count($ssns) != 2) {
            return null;
        }
        return [
            'ssn1' => $ssns[0],
            'ssn2' => $ssns[1],
        ];
    }

    /* -----------------------------------------------------------
    | Convert SSN1 to birth date format (YYYY-MM-DD).
    |---------------------------------------------------------- */
    protected function getBirthDateFromSSN1 ($ssn1)
    {
        if (strlen($ssn1) == 6) {
            $twoDigitYear = intval(substr($ssn1, 0, 2));
            $yearNow = intval(date('Y'));
            $centuryNow = floor($yearNow / 100) * 100;
            $year = $centuryNow + $twoDigitYear;
            if ($year > $yearNow) {
                $year -= 100;
            }
            return $year . '-' . substr($ssn1, 2, 2) . '-' . substr($ssn1, 4, 2);
        } else if (strlen($ssn1) == 8) {
            return substr($ssn1, 0, 4) . '-' . substr($ssn1, 4, 2) . '-' . substr($ssn1, 6, 2);
        }
        return '';
    }

    /* -----------------------------------------------------------
    | Get customer data from input values.
    |---------------------------------------------------------- */
    protected function getCustomerData (&$inputs)
    {
        $ssnParts = $this->getSSNParts($inputs['ssn']['value']);
        $sex = null;
        $birthDate = null;
        if ($ssnParts) {
            $sex = !(intval(substr($ssnParts['ssn2'], 2, 1)) % 2) ? 'female' : 'male';
            $birthDate = $this->getBirthDateFromSSN1($ssnParts['ssn1']);
        }

        return [
            'firstName' => $inputs['name1']['value'],
            'lastName' => $inputs['name2']['value'],
            'sex' => $sex,
            'ssn' => $inputs['ssn']['value'],
            'birthDate' => $birthDate,
            'email' => $inputs['email']['value'],
            'street' => $inputs['street']['value'],
            'postalCode' => $inputs['zipCode']['value'],
            'city' => $inputs['city']['value'],
            'mobile' => $inputs['phoneMobile']['value']
        ];
    }
}
