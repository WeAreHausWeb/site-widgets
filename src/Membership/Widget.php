<?php namespace Webien\Site\Membership;

//use Webien\Site\BRP;

use Webien\Site\BRP\BRPHelper;
use Webien\Site\BRP\BRPManager;

class Widget extends \Elementor\Widget_Base
{

    public function get_name()
    {
        return 'webien_membership';
    }

    public function get_title()
    {
        return __('Medlemskap', 'webien');
    }

    public function get_icon()
    {
        return 'eicon-call-to-action';
    }

    public function get_categories()
    {
        return ['webien'];
    }

    public function get_script_depends()
    {
        return [
            'nets-checkout',
            'webien-member-apps'
        ];
    }


    protected function _register_controls()
    {


        $this->start_controls_section(
            'section_content',
            [
                'label' => __('Allmänt', 'webien'),
            ]
        );

        $this->add_control(
            'view',
            [
                'label' => esc_html__('Välj vy', 'webien'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'multiple' => false,
                'label_block' => true,
                'options' => [
                    'selection' => 'Gymväljare + Medlemskap',
                    'checkout' => 'Checkout',
                ],
                'default' => 'selection',
            ]
        );

        $this->add_control(
            'selection_view',
            [
                'label' => esc_html__('Välj undervy', 'webien'),
                'description' => __('För att kunna jobba bättre med layout delas gymväljaren och medlemsskapen upp i två vyer. Välj här vilken du vill visa ut och glöm sedan inte att addera den andra där du vill ha den.', 'webien'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'multiple' => false,
                'options' => [
                    'gyms' => 'Gymväljare',
                    'memberships' => 'Medlemskap lista',
                ],
                'default' => 'select',
                'label_block' => true,
                'condition' => [
                    'view' => 'selection',
                ],
            ]
        );


        $this->add_control(
            'gym_selection',
            [
                'label' => esc_html__('Urval av gym', 'webien'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'multiple' => false,
                'options' => [
                    'all' => __('Visa alla', 'webien'),
                    'city' => __('Välj gym från stad', 'webien'),
                    'ids' => __('Välj enskilda gym', 'webien'),
                ],
                'default' => 'all',
                'label_block' => true,
                'condition' => [
                    'view' => 'selection',
                    'selection_view' => 'gyms',
                ],
            ]
        );

        $this->add_control(
            'city_selection',
            [
                'label' => esc_html__('Välj städer att inkludera', 'webien'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => $this->getCitiesForOptions(),
                'label_block' => true,
                'condition' => [
                    'view' => 'selection',
                    'selection_view' => 'gyms',
                    'gym_selection' => 'city',
                ],
            ]
        );

        $this->add_control(
            'gym_ids',
            [
                'label' => esc_html__('Välj gym att inkludera', 'webien'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => BRPHelper::getGymOptions(),
                'label_block' => true,
                'condition' => [
                    'view' => 'selection',
                    'selection_view' => 'gyms',
                    'gym_selection' => 'ids',
                ],
            ]
        );


        $this->add_control(
            'limit_subscriptions',
            [
                'label' => esc_html__('Limitera medlemsskap', 'webien'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'no',
                'separator' => 'before',
                'condition' => [
                    'view' => 'selection',
                    'selection_view' => 'gyms',
                ],
            ]
        );

        $this->add_control(
            'subscription_ids',
            [
                'label' => esc_html__('Välj medlemsskap att inkludera', 'webien'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => BRPHelper::getSubscriptionOptions(),
                'label_block' => true,
                'condition' => [
                    'view' => 'selection',
                    'selection_view' => 'gyms',
                    'limit_subscriptions' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'persongroup_ids',
            [
                'label' => esc_html__('Välj persongroup som tilldelas kund efter genomfört köp', 'webien'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => BRPHelper::getPersonGroupOptions(),
                'label_block' => true,
                'condition' => [
                    'view' => 'selection',
                    'selection_view' => 'gyms',
                ],
            ]
        );

        $this->add_control(
            'gym_dropdown_placeholder',
            [
                'label' => esc_html__('Gym dropdown placeholder', 'webien'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Välj ort för utbud och priser', 'webien'),
                'label_block' => true,
                'separator' => 'before',
                'condition' => [
                    'view' => 'selection',
                    'selection_view' => 'gyms',
                ],

            ]
        );


        $this->add_control(
            'success_page',
            [
                'label' => esc_html__('Sida för genomfört köp', 'webien'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'multiple' => false,
                'options' => $this->getPagesForOptions(),
                'label_block' => true,
                'condition' => [
                    'view' => 'checkout',
                ],
                'separator' => 'before',
            ]
        );


        $this->add_control(
            'terms_page',
            [
                'label' => esc_html__('Sida för allmänna villkor', 'webien'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'multiple' => false,
                'options' => $this->getPagesForOptions(),
                'label_block' => true,
                'condition' => [
                    'view' => 'checkout',
                ],
            ]
        );


        $this->end_controls_section();

    }


    protected function render()
    {

        $membershipFormSubmission = new MembershipFormSubmission;

        $nets = [];

        $form = [];
        $form['posted'] = $membershipFormSubmission->posted;
        $form['errors'] = $membershipFormSubmission->errors;
        $form['returnedFromPayment'] = $membershipFormSubmission->returnedFromPayment;
        $form['paymentFailure'] = $membershipFormSubmission->paymentFailure;
        $form['receipt'] = $membershipFormSubmission->receipt;
        $form['signatureCases'] = $membershipFormSubmission->signatureCases;

        //  Populate posted values.

        $inputs = $membershipFormSubmission->inputs;


        $form['selectedGym'] = '';
        $form['selectedType'] = '';
        $form['selectedPaymentType'] = '';
        $form['selectedNoBinding'] = 0;


        if ($inputs) {
            if (isset($inputs['gym']) && $inputs['gym']['valid']) {
                $form['selectedGym'] = $inputs['gym']['value'];
            }
            if (isset($inputs['type']) && $inputs['type']['valid']) {
                $form['selectedType'] = $inputs['type']['value'];
            }
            if (isset($inputs['paymenttype']) && $inputs['paymenttype']['valid']) {
                $form['selectedPaymentType'] = $inputs['paymenttype']['value'];
            }
            if (isset($inputs['nobinding']) && $inputs['nobinding']['valid']) {
                $form['selectedNoBinding'] = $inputs['nobinding']['value'];
            }
            $form['inputs'] = $inputs;
        }


        $settings = $this->get_settings_for_display();
        $unique_str = substr(md5(uniqid(rand(), true)), 0, 5);

        /* -----------------------------------------------------------
        | Assemble membership data according to widget settings.
        |---------------------------------------------------------- */

        if ($settings['view'] === 'checkout') {

            $membershipId = isset($_GET['mid']) ? $_GET['mid'] : null;

            // For preview mode select any for display
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                $membershipId = ['20']; // Student
            }

            $selectedSubscriptions = BRPHelper::getSelectedSubscriptions('yes', [$membershipId]);

            $nets['key'] = NETS_CHECKOUT_KEY_TEST; // Defined in wp-config.php

            $currentUrl = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

            $widgetSettings = [
                'checkoutUrl' => $currentUrl,
                'successPage' => get_permalink($settings['success_page']),
                'termsUrl' => get_permalink($settings['terms_page']),
            ];
        }

        if ($settings['view'] === 'selection') {
            $selectedSubscriptions = BRPHelper::getSelectedSubscriptions($settings['limit_subscriptions'], $settings['subscription_ids']);
            $widgetSettings = [
                'selectPlaceholder' => $settings['gym_dropdown_placeholder'] ?: 'Välj ort för utbud och priser',
                'formView' => $settings['selection_view']
            ];
        }


        // Get selected gyms
        if (!isset($settings['gym_selection']) || $settings['gym_selection'] === 'all') {
            $selectedGyms = BRPHelper::getSelectedGyms('all');
        } else if ($settings['gym_selection'] === 'city') {
            $selectedGyms = BRPHelper::getSelectedGyms($settings['gym_selection'], $settings['city_selection']);
        } else if ($settings['gym_selection'] === 'ids') {
            $selectedGyms = BRPHelper::getSelectedGyms($settings['gym_selection'], $settings['gym_ids']);
        }


        $gyms = array_map(function ($item) {
            return BRPHelper::getGymModel($item);
        }, $selectedGyms);

        $subscriptions = array_map(function ($item) {
            return BRPHelper::getSubscriptionModel($item);
        }, $selectedSubscriptions);

       // dump($subscriptions);


        $subscriptionGroups = BRPHelper::convertSubscriptionsToGroups($subscriptions);


        include(WEBIEN_SITE_PLUGIN_PATH . '/src/Membership/templates/' . $settings['view'] . '.php');

    }


    public function getPagesForOptions()
    {
        $pages = get_pages();
        $options = [];
        foreach ($pages as $page) {
            $options[$page->ID] = $page->post_title;
        }
        return $options;
    }

    public function getCitiesForOptions()
    {
        $terms = get_terms([
            'taxonomy' => 'stader',
            'hide_empty' => false,
        ]);

        $options = [];
        foreach ($terms as $term) {
            $options[$term->term_id] = $term->name;
        }

        return $options;
    }


}
