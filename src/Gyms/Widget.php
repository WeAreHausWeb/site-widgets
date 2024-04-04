<?php namespace Webien\Site\Gyms;

//use Webien\Site\BRP;

use Webien\Site\BRP\BRPHelper;
use Webien\Site\BRP\BRPManager;

class Widget extends \Elementor\Widget_Base
{
    public function get_name()
    {
        return 'webien_gyms';
    }

    public function get_title()
    {
        return __('Gym lista', 'webien');
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
                'options' => [
                    'list' => 'Lista',
                ],
                'default' => 'list',
            ]
        );

        $this->end_controls_section();

    }


    protected function render()
    {

        $settings = $this->get_settings_for_display();

        $gymList = $this->getGymList();
        $gymFilters = $this->getFilters();

        include(WEBIEN_SITE_PLUGIN_PATH . '/src/Gyms/templates/view.php');
    }


    function getGymList()
    {

        $args = [
            'post_type' => 'gym',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'fields' => 'ids',
            'post_parent' => 0,
        ];

        $topPosts = get_posts($args);
        $gyms = [];

        foreach ($topPosts as $id) {
            $arr = $this->gymModel($id);
            $arr['children'] = [];
            $gyms[get_post_field('post_name', $id)] = $arr;
        }


        // Add children
        $args = [
            'post_type' => 'gym',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'fields' => 'ids',
        ];

        $posts = get_posts($args);

        foreach ($posts as $id) {

            $type = get_field('typ', $id);
            if ($type === 'parent') continue;

            $parentSlug = get_post_field('post_name', wp_get_post_parent_id($id));

            $arr = $this->gymModel($id);

            // Insert after the parent index and simplify array
            $index = array_search($parentSlug, array_keys($gyms));

            if($index !== false){
                $gyms[$parentSlug]['gymCount']++;
            }

            array_splice( $gyms, $index+1, 0, [$arr] );
        }

        // reindex to simplify
        return array_values($gyms);
    }


    function gymModel($id)
    {

        $cities = get_the_terms($id, 'stader');
        $city = isset($cities[0]) ? ['id' => $cities[0]->term_id, 'slug' => $cities[0]->slug, 'name' => $cities[0]->name] : [];
        $type = get_field('typ', $id);
        $imgId = get_post_thumbnail_id($id);
        $imgId = $imgId ? $imgId : BRPHelper::getFallbackImageId();

        $maps = get_field('google_maps', $id);
        // https://www.google.se/maps/place/Member+24+G%C3%A4vle+WEST/@60.6754888,17.1126652,17.05z/data=!4m6!3m5!1s0x4660c7d70fdd4ed1:0x3641c2ed8eaaf375!8m2!3d60.67548!4d17.112565!16s%2Fg%2F11pq59xhz2?entry=ttu
        // Cut string at @
        $maps = explode('@', $maps);
        $maps = isset($maps[1]) ? $maps[1] : '';
        $maps = explode(',', $maps);
        // Extrude lat and long from url
        $lat = isset($maps[0]) ? $maps[0] : '';
        $lng = isset($maps[1]) ? $maps[1] : '';


        $activities = [];
        if(get_field('erbjuder_gymmet_grupptraning', $id)) $activities[] = 'group';
        if(get_field('har_gymmet_personliga_tranare', $id)) $activities[] = 'pt';



        /*
         * Taxonomy versino
        $activities = get_field('utbud', $id);
        $activities = !empty($activities) ? array_map(function ($item) {
            $term = get_term($item, 'utbud');
            return $term->term_id;
        }, $activities) : [];
        */

        if (get_option('optimize_images')) {
            $image = \Webien\Image::render([
                'imgId' => $imgId,
                'output' => 'url',
                'width' => '640',
                'ratio' => '16x9'
            ]);
        } else {
            $image = wp_get_attachment_image_url($imgId, 'medium');
        }


        $arr = [
            'id' => $id,
            'type' => $type,
            'name' => get_the_title($id),
            'address' => get_field('adress', $id),
            'gymCount' => 0,
            'lat' => $lat,
            'lng' => $lng,
            'img' => $image,
            'city' => $city,
            'activities' => $activities,
            'url' => get_permalink($id),
            'distance' => false
        ];

        return $arr;

    }


    function getFilters()
    {
        $filters = [
            'city' => [
                'title' => 'Stad',
                'key' => 'city',
                'items' => $this->getCities()
            ],
            'activities' => [
                'title' => 'Utbud',
                'key' => 'activities',
                'items' => $this->getActivities()
            ]
        ];

        return $filters;
    }


    function getCities()
    {
        $terms = get_terms(array(
            'taxonomy' => 'stader',
            'hide_empty' => true,
        ));

        $cities = [];
        foreach ($terms as $term) {

            if ($term->slug === "alla-gym") {
                continue;
            }

            $cities[] = [
                'id' => $term->term_id,
                'slug' => $term->slug,
                'name' => $term->name,
            ];
        }

        return $cities;
    }

    function getActivities()
    {
        return [
            [
                'id' => 'group',
                'name' => __('Gruppträning', 'webien'),
            ],
            [
                'id' => 'pt',
                'name' => __('Personlig träning', 'webien'),
            ]
        ];

        /*
        $terms = get_terms(array(
            'taxonomy' => 'utbud',
            'hide_empty' => true,
        ));


        $arr = [];
        foreach ($terms as $term) {
            $arr[] = [
                'id' => $term->term_id,
                'slug' => $term->slug,
                'name' => $term->name,
            ];
        }
        return $arr;
        */
    }


}
