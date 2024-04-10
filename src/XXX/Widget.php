<?php namespace Webien\Site\XXX;

class Widget extends \Elementor\Widget_Base
{

    public function get_name()
    {
        return 'webien_xxx';
    }

    public function get_title()
    {
        return __('XXX', 'webien');
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
                    'view' => 'Vy 1',
                    'view2' => 'Vy 2',
                ],
                'default' => 'view',
            ]
        );




        $this->end_controls_section();

    }


    protected function render()
    {
        include(WEBIEN_SITE_PLUGIN_PATH . '/src/XXX/templates/view.php');
    }

}
