<script>
    const membershipCheckout = {
        gyms: '<?= json_encode($gyms) ?>',
        memberships: '<?= json_encode($subscriptions) ?>',
        subscriptionGroups: '<?= str_replace(["'", '\r\n'], ["\'", '<br>'], json_encode($subscriptionGroups)) ?>',
        personGroupIds: '<?= empty($_GET['pg']) ? null : json_encode(explode(',', $_GET['pg'])) ?>',
        inputs: '<?= json_encode($form['inputs']) ?>',
        submitted: <?= $form['posted'] ? 1 : 0 ?>,
        now: '<?= date('Y-m-d H:i:s') ?>',
        nets: '<?= json_encode($nets) ?>',
        widget: '<?= json_encode($widgetSettings) ?>',
        ajax: {
            url: '<?= admin_url('admin-ajax.php') ?>',
            nonce: '<?= wp_create_nonce('wp_rest') ?>'
        }
    };
</script>

<div id="membership-checkout" class="membership-checkout">
    <div class="inner" :class="{ready:appReady}">

        <div v-if="!membership" class="no-membership-selected">
            <h4><?= __('Inget medlemskap valt..', 'webien') ?></h4>
        </div>

        <div class="checkout" v-else>
            <form>

                <?php // SELECT GYM --------------------------- // ?>

                <div class="step step-1" :class="{active:currentStep === 1}">
                    <div class="title" @click="goToStep(1)">
                        <span class="nr"><span>1</span></span>
                        <h3 class="txt"><?= __('Hemgym', 'webien') ?></h3>
                    </div>
                    <div class="fields">
                        <p><?= get_field('checkout_step_1_descr', 'options') ?></p>
                        <div class="gyms">
                            <button @click.stop.prevent="setGym(item)" class="item" v-for="item in availableGyms"
                                    :key="item.id" :class="{active:selectedGym === item.id}">
                                <img :src="item.post.img.thumb">
                                <span class="name" v-html="item.name"></span>
                            </button>
                        </div>

                        <button @click="goToStep(2)" type="button"
                                class="elementor-button elementor-size-sm"><?= __('Fortsätt', 'webien') ?></button>
                    </div>
                </div>

                <?php // CONTACT DETAILS --------------------------- // ?>

                <div class="step step-2" :class="[{active:currentStep === 2}, {inactive:currentStep < 2}]">
                    <div class="title" @click="goToStep(2)">
                        <span class="nr"><span>2</span></span>
                        <h3 class="txt"><?= __('Dina uppgifter', 'webien') ?></h3>
                    </div>
                    <div class="fields">

                        <div class="inputs">

                            <div class="dev-notice">Personnummer test: <b>020111-2398</b></div>


                            <div class="input">
                                <label for="input-ssn">Personnummer (YYMMDD-NNNN)</label>
                                <input type="text" id="input-ssn" placeholder="YYMMDD-NNNN" name="ssn" v-model="ssn"
                                       autocomplete="off"/>
                            </div>

                            <div class="errors" v-if="ssn && !hasValidBirthDate">
                                <p><?= __('Åldersgräns 18 år', 'webien') ?></p>
                            </div>

                            <div class="input" :class="validation.email ? '' :'invalid'">
                                <label for="input-email"><?= __('E-post', 'webien') ?></label>
                                <input type="email" id="input-email" name="email" v-model="form.email" @blur="inputBlur"
                                       autocomplete="email"/>
                            </div>

                            <button type="button" v-show="!showPersonInfo" @click.prevent="clickPersonLookupButton()" class="elementor-button elementor-size-sm"><?= __('Fortsätt', 'webien') ?></button>


                            <div class="errors" v-if="personLookupError">
                                <span>{{ personLookupError }}</span>
                            </div>

                            <div class="loading" v-show="lookupLoading">
                                <span><?= __('Hämtar adressuppgifter', 'webien') ?></span>
                                <?= file_get_contents(WEBIEN_SITE_PLUGIN_PATH . '/assets/images/loader.svg') ?>
                            </div>

                            <div class="more-fields inputs" v-show="showPersonInfo && hasValidBirthDate">


                                <div class="input" :class="validation.phoneMobile ? '' :'invalid'">
                                    <label for="input-phoneMobile"><?= __('Mobiltelefon', 'webien') ?></label>
                                    <input type="text" id="input-phoneMobile" name="phoneMobile"
                                           v-model="form.phoneMobile" @blur="inputBlur" autocomplete="tel"/>

                                </div>

                                <div class="input w50" :class="validation.name1 ? '' :'invalid'">
                                    <label for="input-name1"><?= __('Förnamn', 'webien') ?></label>
                                    <input type="text" id="input-name1" name="name1" v-model="form.name1"
                                           @blur="inputBlur" autocomplete="given-name"/>
                                </div>

                                <div class="input w50" :class="validation.name2 ? '' :'invalid'">
                                    <label for="input-name2"><?= __('Efternamn', 'webien') ?></label>
                                    <input type="text" id="input-name2" name="name2" v-model="form.name2"
                                           @blur="inputBlur" autocomplete="family-name"/>
                                </div>

                                <div class="input w50" :class="validation.street ? '' :'invalid'">
                                    <label for="input-street"><?= __('Gatuadress', 'webien') ?></label>
                                    <input type="text" id="input-street" name="street" v-model="form.street"
                                           @blur="inputBlur" autocomplete="address-line1"/>
                                </div>

                                <div class="input w50" :class="validation.zipCode ? '' :'invalid'">
                                    <label for="input-zipCode"><?= __('Postnummer', 'webien') ?></label>
                                    <input type="text" id="input-zipCode" name="zipCode" v-model="form.zipCode"
                                           @blur="inputBlur" autocomplete="postal-code"/>
                                </div>

                                <div class="input w50" :class="validation.city ? '' :'invalid'">
                                    <label for="input-city"><?= __('Postnummer', 'webien') ?></label>
                                    <input type="text" id="input-city" name="city" v-model="form.city" @blur="inputBlur"
                                           autocomplete="address-level2"/>
                                </div>

                            </div>

                        </div>


                        <button v-show="showPersonInfo" @click="goToStep(3)" type="button"
                                class="next-step elementor-button elementor-size-sm" :class="{disabled:!isValid}"><?= __('Fortsätt', 'webien') ?></button>

                        <div class="invalid-message" v-if="form.submitted && !isValid">
                            <p>Formuläret är ofullständigt och kan inte skickas</p>
                        </div>
                    </div>
                </div>

                <?php // PAYMENT DETAILS --------------------------- // ?>

                <div class="step step-3" :class="[{active:currentStep === 3}, {inactive:currentStep < 3}]">
                    <div class="title" @click="goToStep(3)">
                        <span class="nr"><span>3</span></span>
                        <h3 class="txt"><?= __('Betalningsuppgifter', 'webien') ?></h3>
                    </div>
                    <div class="fields">
                        <div class="payment-provider">
                            <?= (new Webien\Site\NETS\NETS())->render() ?>
                        </div>
                    </div>
                </div>
            </form>

            <section class="sidebar">
                <div class="summary">

                    <div class="toggle">
                        <button @click="form.autogiro = true"
                                :class="{active:form.autogiro}"><?= __('Autogiro', 'webien') ?></button>
                        <button @click="form.autogiro = false"
                                :class="{active:!form.autogiro}"><?= __('Förbetalt', 'webien') ?></button>
                    </div>

                    <div class="title">
                        <h4 class="name" v-html="membership.name"></h4>
                        <div class="prices">
                            <h4 class="price now" v-html="formatedPrice"></h4>
                            <h4 class="price then" v-html="formatedOldPrice"></h4>
                        </div>

                    </div>
                    <div class="usps">
                        <div v-for="usp in membership.usps" class="usp">
                            <i class="icon icon-Done"></i><span v-html="fixHtmlEntities(usp)"></span>
                        </div>
                    </div>

                    <div class="break-down">
                        <div class="row">
                            <span><?= __('Startavgift', 'webien') ?></span>
                            <span>start_cost</span>
                        </div>
                        <div class="row">
                            <span><?= __('Period', 'webien') ?></span>
                            <span>time_period</span>
                        </div>
                        <div class="row sale">
                            <span><?= __('Rabatt', 'webien') ?></span>
                            <span>sale_amount</span>
                        </div>
                        <div class="row total">
                            <span><?= __('Betala nu', 'webien') ?></span>
                            <span>pay_now_cost</span>
                        </div>

                        <div class="dev-notice">Hittar inte denna data i API:t än.</div>

                        <p class="summary-notice"><?= get_field('checkout_summary_notice', 'option') ?></p>
                    </div>
                </div>

                <div class="help-txt">
                    <?= get_field('checkout_below_summary', 'options') ?>
                </div>

            </section>

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
