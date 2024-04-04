import {createApp} from 'vue'

const instances = document.querySelectorAll('.membership-form');

instances.forEach((instance) => {

    let $app = createApp({
        name: "Membership form" + instance.dataset.unique,
        data() {
            return {
                appReady: false,
                useLog: false,
                gyms: [],
                memberships: [],
                settings: [],
                selectedGym: 0,

            }
        },
        mixins: [],
        computed: {
            membershipsFiltered() {
                let app = this;
                this.log(this.selectedGym);
                // Return the ones that has current gym available
                return this.memberships.filter(function (membership) {

                    let valid = false;
                    membership.brp_data.businessUnits.forEach(function (item) {
                        if (item.id === app.selectedGym) valid = true;
                    });
                    return valid;
                });
            }
        },
        watch: {
            selectedGym: {
                handler(newVal, oldVal) {
                    this.updateLocalStorage('selectedGym', newVal);
                },
            },
        },
        methods: {
            log(str) {
                if (this.useLog) console.log(str);
            },

            init() {

                // Dev
                if (window.location.href.indexOf(".test") > -1) {
                    this.useLog = true;
                }

                this.gyms = JSON.parse(membershipForm.gyms);
                this.memberships = JSON.parse(membershipForm.memberships);
                this.settings = JSON.parse(window['widget_' + instance.dataset.unique].settings);

                this.log(this.settings);

                this.appReady = true;

                // Attach listener for localhost updates
                window.addEventListener('membershipFormUpdated', (e) => {
                    this.updateFromLocalStorage();
                })

                this.log(this.membershipsFiltered);

            },

            updateFromLocalStorage() {
                let formObject = JSON.parse(localStorage.getItem('membershipForm'));

                if (formObject) {
                    this.selectedGym = formObject.selectedGym;
                }

            },
            getTicket(item) {
                let ticket = 'ticket-yellow.png';
                if(item.campaign){
                    ticket = 'ticket-purple.png';
                }
                return '/wp-content/plugins/webien-site-widgets/assets/images/' + ticket;
            },

            getMembershipUrl(item) {
                let id = item.brp_data.id;
                return '/bli-medlem/slutfor-kop?mid=' + id + '&gr=' + '1,2,3'; // Replace with person group ids
            },
            formatPrice(item) {
              return item.price + ' kr/mån';
            },
            updateLocalStorage(key, val) {

                let formObject = JSON.parse(localStorage.getItem('membershipForm'));

                // Create if not exists
                if (!formObject) {
                    formObject = {
                        selectedGym: 0,
                        membership: 0,
                        checkout: {}
                    };
                }

                formObject[key] = val;

                localStorage.setItem('membershipForm', JSON.stringify(formObject));

                // Trigger event to let other apps know that the form has been updated
                window.dispatchEvent(new Event('membershipFormUpdated'));
            },
            fixHtmlEntities(html) {

                this.log(html);

                let decodedString = document.createElement("div");

                decodedString.innerHTML = html;

                return decodedString.textContent;
            },


        },
        mounted: function () {
            this.init();
        },
        created() {
        },
        components: {},
    });


// Mount app to DOM
    if (document.getElementById("membership-form-" + instance.dataset.unique)) {
        $app.mount('#membership-form-' + instance.dataset.unique);
    }


});