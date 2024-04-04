<?php

namespace Webien\Site\BRP;


class BRPHelper
{


    public static function getValidGyms()
    {
        $gymData = get_field('gym_data', 'option') ?: [];

        return array_values(array_filter($gymData, function ($item) {
            return !($item['gym_removed'] || $item['gym_deactivated']);
        }));
    }


    public static function getFallbackImageId()
    {
        return get_field('fallback_image', 'option') ?? null;
    }

    /* -----------------------------------------------------------
   | Get gym options.
   |---------------------------------------------------------- */
    public static function getGymOptions()
    {
        BRPManager::getInstance()->loadBusinessUnits();

        $gyms = self::getValidGyms();

        $options = [];

        foreach ($gyms as $gym) {
            $options[$gym['wp_gym_id']] = $gym['gym_name'];
        }

        asort($options);

        // Add all to array.
        //$options = ['all' => 'Alla'] + $options;

        return $options;
    }


    /* -----------------------------------------------------------
    | Get subscription options.
    |---------------------------------------------------------- */
    public static function getSubscriptionOptions()
    {
        BRPManager::getInstance()->loadSubscriptions();

        $subscriptions = self::getValidSubscriptions();

        $options = [];

        foreach ($subscriptions as $subscription) {
            $options[$subscription['subscription_id']] = $subscription['subscription_name'];
        }

        asort($options);

        $options = ['all' => 'Alla'] + $options;

        return $options;
    }

    public static function getValidSubscriptions()
    {
        $subscriptionData = get_field('subscription_data', 'option') ?: [];

        return array_values(array_filter($subscriptionData, function ($item) {
            return !($item['subscription_removed'] || $item['subscription_deactivated']);
        }));
    }

    /* -----------------------------------------------------------
    | Get person group options.
    |---------------------------------------------------------- */
    public static function getPersonGroupOptions()
    {
        $personGroups = BRPManager::getInstance()->loadPersonGroups();
        $options = [
            '0' => ''
        ];

        if (!empty($personGroups) && !empty($personGroups->persongroups)) {
            foreach ($personGroups->persongroups as $group) {
                $options[$group->id] = "{$group->name} (#{$group->id})";
            }
        }

        asort($options);

        return $options;
    }


    /* -----------------------------------------------------------
    | Get selected gyms.
    |---------------------------------------------------------- */
    public static function getSelectedGyms($selection, $gymIds = null)
    {
        $validGyms = self::getValidGyms();

        $selectedGyms = $validGyms;

        if ($selection === 'city') {
            // Get all gyms within the selected terms
            $gymIds = get_posts([
                'post_type' => 'gym',
                'numberposts' => -1,
                'tax_query' => [
                    [
                        'taxonomy' => 'stader',
                        'field' => 'term_id',
                        'terms' => $gymIds,
                        'operator' => 'IN'
                    ]
                ],
                'fields' => 'ids'
            ]);
        }

        $selectedGyms = $gymIds ? array_values(array_filter($validGyms, function ($item) use ($gymIds) {
            if (is_array($gymIds)) {
                return in_array((int)$item['wp_gym_id'], $gymIds);
            }
        })) : $validGyms;

        /* -----------------------------------------------------------
        | Populate gyms with groups and BRP data.
        |---------------------------------------------------------- */
        self::setGymsBrpData($selectedGyms);

        /* -----------------------------------------------------------
        | Remove gyms without BRP data.
        |---------------------------------------------------------- */
        return array_values(array_filter($selectedGyms, function ($item) {
            return !empty($item['brp_data']);
        }));
    }


    /* -----------------------------------------------------------
    | Set gyms BRP data.
    |---------------------------------------------------------- */
    public static function setGymsBrpData(array &$gyms)
    {
        $brpGyms = BRPManager::getInstance()->loadBusinessUnits() ?: [];

        foreach ($gyms as &$gym) {
            $brpData = array_values(array_filter($brpGyms, function ($item) use ($gym) {
                return (int)$gym['gym_id'] == $item->id;
            }));

            $gym['brp_data'] = empty($brpData) ? null : self::brpGymToData($brpData[0]);
        }
    }

    public static function brpGymToData($gym)
    {
        return (object)[
            'id' => $gym->id,
            'name' => $gym->name
        ];
    }


    public static function getGymModel($gym)
    {
        $defaultImage = 'https://cdn.dribbble.com/userupload/3184605/file/original-65e1f8643edcf236da2b5acdf51c2749.jpeg?crop=314x0-1754x1080&resize=1000x750&vertical=center';
        $thumbnail = get_post_thumbnail_id($gym['wp_gym_id']);
        $cities = $gym['wp_gym_id'] ? get_the_terms($gym['wp_gym_id'], 'stader') : [];
        $city = isset($cities[0]) ? ['id' => $cities[0]->term_id, 'slug' => $cities[0]->slug, 'name' => $cities[0]->name] : [];

        //dump($gym);

        $gymTitle = $gym['wp_gym_id'] ? get_the_title($gym['wp_gym_id']) : $gym['gym_name'];
        $gymTitle = isset($city['name']) ? $city['name'] . ' ' . $gymTitle : $gymTitle;

        return (object)[
            'id' => (int)$gym['gym_id'],
            'name' => $gymTitle,
            'address' => 'Adress..',
            'city' => $city,
            'post' => [
                'img' => [
                    'thumb' => $thumbnail ? \Webien\Image::render([
                        'imgId' => $thumbnail,
                        'output' => 'url',
                        'width' => '320',
                        'ratio' => '16x9'
                    ]) : $defaultImage,
                ]
            ],
            'brp_data' => $gym['brp_data']
        ];
    }


    public static function getSubscriptionModel($subscription)
    {

        // Trim texts
        $subscription['subscription_usps'] = isset($subscription['subscription_usps']) && !empty($subscription['subscription_usps']) ? array_map(function ($usp) {
            return htmlspecialchars(str_replace(["\r", "\n", "<p>", "</p>"], '', trim($usp['txt'])));
        }, $subscription['subscription_usps']) : [];

        //$subscription['subscription_info'] = htmlspecialchars(str_replace(["\r", "\n", "<p>", "</p>"], '', trim($subscription['subscription_info'])));

        if (isset($subscription['group']->info)) {
            $subscription['group']->info = isset($subscription['group']->info) ? htmlspecialchars(str_replace(["\r", "\n", "<p>", "</p>"], '', trim($subscription['group']->info))) : '';

        }


        return (object)[
            'id' => (int)$subscription['subscription_id'],
            'name' => $subscription['subscription_title'] ?: $subscription['subscription_name'],
            'autogiro' => (bool)$subscription['subscription_ag'],
            'unbound' => (bool)$subscription['subscription_unbound'],
            'price' => $subscription['subscription_api_price'],
            'campaign' => (bool)$subscription['is_campaign'],
            'price_txt' => self::formatPrices($subscription),
            //'info' => $subscription['subscription_info'],
            'info' => 'Old info string, replace with usps',
            'usps' => $subscription['subscription_usps'],
            'terms' => htmlspecialchars($subscription['subscription_terms']),
            'group' => $subscription['group'],
            'brp_data' => $subscription['brp_data'],
        ];
    }


    /* -----------------------------------------------------------
    | Format prices to readable text
    |---------------------------------------------------------- */

    public static function formatPrices($subscription)
    {
        $monthly = $subscription['subscription_api_price'];
        $yearly = (int)$monthly * 12;

        // Format price with a thousand separator
        $monthFormated = number_format($monthly, 0, ',', ' ');
        $yearlyFormated = number_format($yearly, 0, ',', ' ');

        return [
            'monthly' => $monthFormated . __(' kr/mån', 'webien'),
            'yearly' => $yearlyFormated . __(' kr/år', 'webien')
        ];
    }


    /* -----------------------------------------------------------
    | Convert selected subscriptions to groups, with at most one
    | subscription of each:
    |   Card payment, Autogiro payment and Autogiro unbound.
    |---------------------------------------------------------- */
    public static function convertSubscriptionsToGroups(&$subscriptions)
    {
        $groups = [];

        foreach ($subscriptions as &$subscription) {
            $isAutogiro = $subscription->autogiro;
            $isUnbound = $subscription->unbound;

            $groupKey = isset($subscription->group) && is_object($subscription->group) ? $subscription->group->key : 'subscription_' . $subscription->id;
            $group = empty($groups[$groupKey]) ? self::getEmptyGroup($groupKey, $subscription->group, $subscription->group ? null : $subscription->name, $subscription->group ? null : $subscription->info, $subscription->group ? null : $subscription->terms) : $groups[$groupKey];
            $groupSubscriptionType = $isAutogiro ? ($isUnbound ? 'autogiroUnbound' : 'autogiro') : 'card';

            // // Create new group if position already filled.
            // if (!empty($group->subscriptions->{$groupSubscriptionType})) {
            //     $groupKey = 'subscription_' . $subscription->id;
            //     $group = $this->getEmptyGroup($groupKey, null, $subscription->name);
            // }

            $group->subscriptions->{$groupSubscriptionType}[] = $subscription;

            $groups[$groupKey] = $group;
        }

        return array_values($groups);
    }

    public static function getEmptyGroup($key, $group = null, $name = null, $info = null, $terms = null)
    {
        return (object)[
            'key' => $key,
            'group' => $group,
            'name' => $name ?? ($group ? $group->name : ''),
            'info' => $info ?? ($group ? $group->info : ''),
            'terms' => $terms ?? ($group ? $group->terms : ''),
            'subscriptions' => (object)[
                'card' => [],
                'autogiro' => [],
                'autogiroUnbound' => []
            ]
        ];
    }


    public static function getSelectedSubscriptions($limit = false, $subscriptionIds = null)
    {
        $validSubscriptions = self::getValidSubscriptions();

        if($limit == 'yes' && is_array($subscriptionIds)){
            $selectedSubscriptions = $subscriptionIds ? array_values(array_filter($validSubscriptions, function ($item) use ($subscriptionIds) {
                return in_array((int)$item['subscription_id'], $subscriptionIds);
            })) : [];
        } else {
            $selectedSubscriptions = $validSubscriptions;
        }



        /* -----------------------------------------------------------
        | Populate subscriptions with groups and BRP data.
        |---------------------------------------------------------- */
        self::setSubscriptionsGroups($selectedSubscriptions);
        self::setSubscriptionsBrpData($selectedSubscriptions);

        /* -----------------------------------------------------------
        | Remove subscriptions without BRP data.
        |---------------------------------------------------------- */
        return array_values(array_filter($selectedSubscriptions, function ($item) {
            return !empty($item['brp_data']);
        }));
    }


    /* -----------------------------------------------------------
    | Set subscriptions groups.
    |---------------------------------------------------------- */
    public static function setSubscriptionsGroups(array &$subscriptions)
    {
        $subscriptionGroups = get_field('subscription_groups', 'option') ?: [];

        foreach ($subscriptions as &$subscription) {
            $group = empty($subscription['subscription_group']) ? null : array_values(array_filter($subscriptionGroups, function ($item) use ($subscription) {
                return $subscription['subscription_group'] == $item['subscription_group_key'];
            }));

            $subscription['group'] = empty($group) ? null : self::getSubscriptionGroupModel($group[0]);
        }
    }


    public static function getSubscriptionGroupModel($group)
    {
        return (object)[
            'key' => $group['subscription_group_key'],
            'name' => $group['subscription_group_name'],
            'info' => htmlspecialchars($group['subscription_group_info']),
            'terms' => htmlspecialchars($group['subscription_group_terms'])
        ];
    }


    /* -----------------------------------------------------------
    | Set subscriptions BRP data.
    |---------------------------------------------------------- */
    public static function setSubscriptionsBrpData(array &$subscriptions)
    {
        $brpSubscriptions = BRPManager::getInstance()->loadSubscriptions() ?: [];

        foreach ($subscriptions as &$subscription) {
            $brpData = array_values(array_filter($brpSubscriptions, function ($item) use ($subscription) {
                return (int)$subscription['subscription_id'] == $item->id;
            }));

            $subscription['brp_data'] = empty($brpData) ? null : self::brpSubscriptionToData($brpData[0]);
        }
    }


    public static function brpSubscriptionToData($subscription)
    {
        return (object)[
            'id' => $subscription->id,
            'name' => $subscription->name,
            'businessUnits' => array_map(function ($item) {
                return (object)[
                    'id' => $item->id,
                    'name' => $item->name,
                ];
            }, $subscription->businessUnits),
            'debitMethod' => $subscription->debitMethod,
            'priceWithInterval' => $subscription->priceWithInterval,
        ];
    }


}
