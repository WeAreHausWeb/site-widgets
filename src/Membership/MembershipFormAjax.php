<?php

// This class has been replaced by the API class and is no longer in use.


namespace Webien\Site\Membership;

use Webien\Site\BRP;

class MembershipFormAjax
{
    /* -----------------------------------------------------------
    | Register WP Ajax end point.
    |---------------------------------------------------------- */
    function __construct ()
    {
        //add_action( 'wp_ajax_membershipform', [$this, 'ajax'] );
        //add_action( 'wp_ajax_nopriv_membershipform', [$this, 'ajax'] );
    }

    /* -----------------------------------------------------------
    | WP Ajax request.
    |---------------------------------------------------------- */
    public function ajax ()
    {
        $response = [];
        $type = sanitize_text_field($_REQUEST['type']);

        /* -----------------------------------------------------------
        | Person lookup.
        |---------------------------------------------------------- */
        if ($type == 'person-lookup') {
            $businessUnit = sanitize_text_field($_REQUEST['businessUnit']);
            $ssn = sanitize_text_field($_REQUEST['ssn']);
            $brp = new \Webien\Site\BRP\BRP;

            $person = $brp->getPersonLookupFromSSN($businessUnit, $ssn);

            var_dump($person);
            die();

            /* -----------------------------------------------------------
            | Respond with person if not already a customer in BRP.
            |---------------------------------------------------------- */
            if ($person->status == 'FOUND') {
                return wp_send_json($person);
            }

            $person = $brp->getPersonFromSSN($businessUnit, $ssn);


            // elog($person);

            /* -----------------------------------------------------------
            | Bail out if no user was found.
            |---------------------------------------------------------- */
            if (!$person) {
                return wp_send_json([]);
            }

            /* -----------------------------------------------------------
            | Check if user has membership.
            |---------------------------------------------------------- */
            $brp->getVer2PersonAccessToken($person);

            $customer = $brp->getCustomer($person->id);

            // elog($customer);

            $subscriptions = $brp->getCustomerSubscriptions($customer->id);

            // elog($subscriptions);

            if (is_array($subscriptions) && count($subscriptions)) {
                wp_send_json([
                    'status' => 'has_membership'
                ]);
            }

            /* -----------------------------------------------------------
            | Respons with customer if purchase is ok.
            |---------------------------------------------------------- */
            wp_send_json([
                'id' => $customer->id,
                'email' => $this->scrambleEmail($customer->email)
            ]);
        }

        wp_send_json($response);
    }

    public function scrambleEmail ($email)
    {
        return preg_replace_callback('/^([^@]+)@([^\.]+)\.([^.]+)$/', function ($matches) {
            return $this->scramble($matches[1], 3) . '@' . $this->scramble($matches[2]) . '.' . $matches[3];
        }, $email);
    }

    protected function scramble ($str, $numStart = 1, $numEnd = 1) {
        if (strlen($str) < 7) {
            $numStart = $numStart < 2 ? $numStart : 2;
            $numEnd = $numEnd < 1 ? $numEnd : 1;
        }
        if (strlen($str) < 6) {
            $numStart = $numStart < 1 ? $numStart : 1;
        }
        if (strlen($str) < 4) {
            $numEnd = 0;
        }
        if (strlen($str) < 3) {
            $numStart = 0;
        }

        $regexNumStart = !$numStart ? '{0}' : ($numStart == 1 ? '' : "{1,$numStart}");
        $regexNumEnd = $numEnd == 1 ? '?' : "{0,$numEnd}";

        return preg_replace_callback('/^(.' . $regexNumStart . ')(.*?)(.' . $regexNumEnd . ')$/', function ($matches) use ($numStart) {
            return $matches[1] . preg_replace('/./', '*', $matches[2]) . $matches[3];
        }, $str);
    }
}
