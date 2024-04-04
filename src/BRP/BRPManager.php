<?php

namespace Webien\Site\BRP;

use Exception;

class BRPManager
{
    protected static $instance;

    public $cacheTime = 3600; // Cache for one hour (86400 = 1 day)
    public $membershipData;

    public static function getInstance()
    {

        if (!self::$instance) {
            self::$instance = new BRPManager();
        }

        return self::$instance;
    }

    /* -----------------------------------------------------------
    | Get fixed membership definitions. Should be replaced with
    | dynamical definitions.
    |---------------------------------------------------------- */
    public function getMembershipData()
    {
        if ($this->membershipData) {
            return $this->membershipData;
        }

        /* -----------------------------------------------------------
        | Load JSON file with gym data.
        |---------------------------------------------------------- */
        $membershipData = null;
        $json_file = strpos($_SERVER['REQUEST_URI'], 'summer-deal') == 1 ? '/components/MembershipForm/gyms_summerdeal.json' : '/components/MembershipForm/gyms.json';
        $file = get_stylesheet_directory() . $json_file;
        if ($fp = fopen($file, 'r')) {
            if ($str = fread($fp, filesize($file))) {
                $membershipData = json_decode($str, true);
            }
            fclose($fp);
        }

        if ($membershipData) {
            $this->$membershipData = $membershipData;
        }

        return $membershipData;
    }

    /* -----------------------------------------------------------
    | Get subscriptions from BRP / cache.
    |---------------------------------------------------------- */
    public function loadSubscriptions()
    {
        $fileName = 'brp-subscriptions.json';

        $data = $this->getCache($fileName);

        if (!$data) {
            $brp = new BRP;
            $data = $brp->getSubscriptions();

            if ($brp->errorResponse) {
                $data = [];
            }

            $this->setCache($fileName, $data);

            $this->syncOptionSubscriptions($data);
        }

        return $data;
    }

    /* -----------------------------------------------------------
    | Get person groups from BRP / cache.
    |---------------------------------------------------------- */
    public function loadPersonGroups()
    {
        $fileName = 'brp-persongroups.json';

        $data = $this->getCache($fileName);

        if (!$data) {
            $brp = new \Webien\Site\BRP\BRP();
            $data = $brp->getPersonGroups();

            if ($brp->errorResponse) {
                $data = ['persongroups' => []];
            }

            $this->setCache($fileName, $data);
        }

        return $data;
    }

    /* -----------------------------------------------------------
    | Get business units.
    |---------------------------------------------------------- */
    public function loadBusinessUnits()
    {
        $fileName = 'brp-businessunits.json';

        $data = $this->getCache($fileName);

        /* -----------------------------------------------------------
        | Load and cache if necessary.
        |---------------------------------------------------------- */
        if (!$data) {
            $brp = new BRP;
            $data = $brp->getBusinessUnits();

            if ($brp->errorResponse) {
                $data = [];
            }

            $this->setCache($fileName, $data);

            $this->syncOptionGyms($data);
        }

        return $data;
    }

    /* -----------------------------------------------------------
    | Synchronize option gyms with BRP gyms.
    |---------------------------------------------------------- */
    protected function syncOptionGyms($data)
    {
        if (empty($data)) {
            return;
        }

        /* -----------------------------------------------------------
        | Add option rows.
        |---------------------------------------------------------- */
        $optionGyms = get_field('gym_data', 'option');

        if (!$optionGyms) {
            $optionGyms = [];
        }

        $optionGymIds = array_map(function ($item) {
            return $item['gym_id'];
        }, $optionGyms);

        foreach ($data as $item) {
            $index = array_search($item->id, $optionGymIds);

            /* -----------------------------------------------------------
            | Update gym if necessary.
            |---------------------------------------------------------- */
            if ($index !== false) {
                $optionGym = $optionGyms[$index];

                $update = false;

                if ($optionGym['gym_name'] !== $item->name) {
                    $update = true;
                    $optionGym['gym_name'] = $item->name;
                }

                if ($optionGym['gym_removed']) {
                    $update = true;
                    $optionGym['gym_removed'] = 0;
                }

                if ($update) {
                    update_row('gym_data', $index + 1, $optionGym, 'option');
                }

                continue;
            }

            /* -----------------------------------------------------------
            | Add gym.
            |---------------------------------------------------------- */
            add_row('gym_data', [
                'gym_id' => $item->id,
                'gym_name' => $item->name
            ], 'option');
        }

        /* -----------------------------------------------------------
        | Mark removed gyms.
        |---------------------------------------------------------- */
        $gymIds = array_map(function ($item) {
            return $item->id;
        }, $data);

        $removedIds = array_diff($optionGymIds, $gymIds);

        foreach ($removedIds as $index => $id) {
            $optionGym = $optionGyms[$index];

            if ($optionGym['gym_removed']) {
                continue;
            }

            $optionGym['gym_removed'] = 1;
            update_row('gym_data', $index + 1, $optionGym, 'option');
        }
    }

    /* -----------------------------------------------------------
    | Synchronize option subscription with BRP subscriptions.
    |---------------------------------------------------------- */
    protected function syncOptionSubscriptions($data)
    {
        if (empty($data)) {
            return;
        }

        /* -----------------------------------------------------------
        | Add option rows.
        |---------------------------------------------------------- */
        $optionSubscriptions = get_field('subscription_data', 'option');

        if (!$optionSubscriptions) {
            $optionSubscriptions = [];
        }

        $optionSubscriptionIds = array_map(function ($item) {
            return $item['subscription_id'];
        }, $optionSubscriptions);

        foreach ($data as $item) {
            $index = array_search($item->id, $optionSubscriptionIds);

            $name = $item->name;
            $price = round($item->priceWithInterval->price->amount / 100);
            $isAutogiro = preg_match('/^DIRECT_DEBIT/i', $item->debitMethod);
            $isUnbound = preg_match('/utan bindningstid/i', $name);

            /* -----------------------------------------------------------
            | Update subscription if necessary.
            |---------------------------------------------------------- */
            if ($index !== false) {
                $optionSubscription = $optionSubscriptions[$index];
                $update = [];

                if ($optionSubscription['subscription_name'] !== $name) {
                    $update['subscription_name'] = $name;
                }

                if ($optionSubscription['subscription_api_price'] !== $price) {
                    $update['subscription_api_price'] = $price;
                }

                if ($optionSubscription['subscription_ag'] !== $price) {
                    $update['subscription_ag'] = $isAutogiro;
                }

                if ($optionSubscription['subscription_removed']) {
                    $update['subscription_removed'] = 0;
                }

                if (!empty($update)) {
                    $row = array_merge($optionSubscription, $update);
                    update_row('subscription_data', $index + 1, $row, 'option');
                }

                continue;
            }

            /* -----------------------------------------------------------
            | Add subscription.
            |---------------------------------------------------------- */
            add_row('subscription_data', [
                'subscription_id' => $item->id,
                'subscription_name' => $name,
                'price' => $price,
                'subscription_ag' => $isAutogiro,
                'subscription_unbound' => $isUnbound
            ], 'option');
        }

        /* -----------------------------------------------------------
        | Mark removed subscriptions.
        |---------------------------------------------------------- */
        $subscriptionIds = array_map(function ($item) {
            return $item->id;
        }, $data);

        $removedIds = array_diff($optionSubscriptionIds, $subscriptionIds);

        foreach ($removedIds as $index => $id) {
            $optionSubscription = $optionSubscriptions[$index];

            if ($optionSubscription['subscription_removed']) {
                continue;
            }

            $optionSubscription['subscription_removed'] = 1;
            update_row('subscription_data', $index + 1, $optionSubscription, 'option');
        }
    }

    /* -----------------------------------------------------------
    | Get data from cache if available.
    |---------------------------------------------------------- */
    protected function getCache($fileName)
    {
        $filePath = wp_upload_dir()['basedir'] . '/' . $fileName;

        if (!file_exists($filePath)) {
            return null;
        }

        try {
            $cache_age = time() - filemtime($filePath);
            if ($cache_age < $this->cacheTime) {
                $cache_fp = fopen($filePath, 'r');
                $json = fread($cache_fp, filesize($filePath));
                return json_decode($json);
            }
        } catch (Exception $e) {
            return null;
        }

        return null;
    }

    protected function setCache($fileName, $data)
    {
        $filePath = wp_upload_dir()['basedir'] . '/' . $fileName;

        try {
            $cache_fp = fopen($filePath, 'w');
            fwrite($cache_fp, json_encode($data));
            fclose($cache_fp);
        } catch (Exception $e) {
            error_log("Error opening file");
        }
    }


    public function getPersonLookup($data)
    {

        /* -----------------------------------------------------------
        | Person lookup.
        |---------------------------------------------------------- */
        $businessUnit = sanitize_text_field($data['businessUnit']);
        $ssn = sanitize_text_field($data['ssn']);
        $brp = new \Webien\Site\BRP\BRP;

        $person = $brp->getPersonLookupFromSSN($businessUnit, $ssn);

        /* -----------------------------------------------------------
        | Respond with person if not already a customer in BRP.
        |---------------------------------------------------------- */
        if ($person->status == 'FOUND') {
            return $person;
        }

        $person = $brp->getPersonFromSSN($businessUnit, $ssn);


        // elog($person);

        /* -----------------------------------------------------------
        | Bail out if no user was found.
        |---------------------------------------------------------- */
        if (!$person) {
            return [];
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
            return [
                'status' => 'has_membership'
            ];
        }

        /* -----------------------------------------------------------
        | Respons with customer if purchase is ok.
        |---------------------------------------------------------- */
        return [
            'id' => $customer->id,
            'email' => $this->scrambleEmail($customer->email)
        ];


        return $response;
    }

    public function scrambleEmail($email)
    {
        return preg_replace_callback('/^([^@]+)@([^\.]+)\.([^.]+)$/', function ($matches) {
            return $this->scramble($matches[1], 3) . '@' . $this->scramble($matches[2]) . '.' . $matches[3];
        }, $email);
    }

    protected function scramble($str, $numStart = 1, $numEnd = 1)
    {
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
