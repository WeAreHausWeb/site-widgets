<?php


class API
{

    function __construct()
    {
        $this->registerHooks();
    }

    function registerHooks()
    {
        //-----------------------------------------//
        // API END POINTS
        //-----------------------------------------//

        // Public ajax calls from misc quiz functionality

        add_action('rest_api_init', function () {
            register_rest_route('m24/v1', '/api', [
                'methods' => 'GET',
                'callback' => [$this, 'handle_api']
            ]);
        });
    }


    public function handle_api($req)
    {
        $action = $req['action'];
        $data = $req['data'];

        // Check what to do...
        $response = [];

        if ($action === 'nets_payment') {
            $response = (new \Webien\Site\Nets\NETS())->createPayment($data);
        }

        if ($action === 'person_lookup') {
            $response = (new \Webien\Site\BRP\BRPManager())->getPersonLookup($data);
        }


        $res = new \WP_REST_Response($response);
        $res->set_status(200);

        return $res->data;
    }

}

(new API());