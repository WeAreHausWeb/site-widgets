<script>
    <?php if($settings['selection_view'] === 'gyms'){ ?>
    const membershipForm = {
        gyms: '<?= json_encode($gyms) ?>',
        memberships: '<?= json_encode($subscriptions) ?>',
    };
    <?php } ?>
    window.widget_<?= $unique_str ?> = {
        settings: '<?= json_encode($widgetSettings) ?>'
    };
</script>



<div class="membership-form" id="membership-form-<?= $unique_str ?>" data-unique="<?= $unique_str ?>">
    <div class="inner" :class="{ready:appReady}">

        <select v-model="selectedGym" v-if="settings.formView === 'gyms'">
            <option value="0" disabled>{{settings.selectPlaceholder}}</option>
            <option v-for="gym in gyms" :value="gym.id">{{ gym.name }}</option>
        </select>


        <div class="memberships" v-if="settings.formView === 'memberships'">
            <div class="item" v-for="(item, index) in membershipsFiltered" :key="item.id" :class="{campaign: item.campaign}">
                <div class="ticket">
                    <img :src="getTicket(item)" :alt="'Ticket:' + item.name">
                    <div class="txt">
                        <h4 class="name" v-html="item.name"></h4>
                        <h5 class="price" v-html="formatPrice(item)"></h5>
                    </div>

                </div>
                <div class="action">
                    <a :href="getMembershipUrl(item)" class="elementor-button elementor-button-link elementor-size-sm">Välj {{item.name}}</a>
                </div>
                <ul class="usps">
                   <li v-for="(usp, index) in item.usps" :key="index">
                       <i class="icon icon-Done-v2"></i>
                       <span class="txt" v-html="fixHtmlEntities(usp)"></span>
                   </li>
                </ul>
            </div>

        </div>

    </div>

</div>


<?php
// In preview mode, force load of js for better preview experience
if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
    echo '<script src="' . WEBIEN_SITE_PLUGIN_URI . 'dist/member-apps.js"></script>';
    /*
    if(!defined('MEMBER_APPS_LOADED')){
        define('MEMBER_APPS_LOADED', true);
    }
    */
}
