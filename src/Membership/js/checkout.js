import {createApp} from 'vue'
import axios from 'axios';
import Qs from 'qs';

const membershipCheckoutApp = createApp({
        name: "Membership checkout",
        data() {
            return {
                appReady: false,
                useLog: false,
                API: null,
                selectedGym: null,
                gyms: JSON.parse(membershipCheckout.gyms),
                memberships: JSON.parse(membershipCheckout.memberships),
                inputs: JSON.parse(membershipCheckout.inputs),
                widget: JSON.parse(membershipCheckout.widget),
                currentStep: 2,

                timeAtStart: Date.now(),
                now: membershipCheckout.now,
                personLookupError: null,

                ssn: '',
                form: {
                    email: '',
                    phoneMobile: '',
                    name1: '',
                    name2: '',
                    street: '',
                    zipCode: '',
                    city: '',
                    autogiro: true,
                    submitted: membershipCheckout.submitted,
                },

                // Customer ID and hashed e-mail.
                lookupCustomerId: null,
                lookupCustomerEmail: null,
                showCustomerInfo: false,
                //ajax: membershipCheckout.ajax,

                // App data.
                isSendingPersonLookup: false,
                showPersonInfo: true,
                nets: JSON.parse(membershipCheckout.nets),
                lookupLoading: false,

            }
        },
        mixins: [],
        computed: {
            availableGyms() {

                // Get the city based on selected gym
                let myGym = this.gyms.filter((gym) => {
                    return gym.id === this.selectedGym;
                });


                if (myGym.length > 0) {
                    return this.gyms.filter((gym) => {
                        return gym.city.id === myGym[0].city.id;
                    });
                }

            },
            membership() {
                return this.memberships[0];
            },
            hasValidBirthDate() {
                return this.validateBirthDate(this.ssn);
            },
            formatedPrice() {
                return this.form.autogiro ? this.membership.price_txt.monthly : '';
            },
            formatedOldPrice() {
                return this.form.autogiro ? this.membership.price_txt.monthly : '';
            },
            dateNow() {
                let nowParts = this.now.split(' ');
                let dateParts = nowParts[0].split('-');
                let timeParts = nowParts[1].split(':');
                return new Date(
                    parseInt(dateParts[0], 10),
                    parseInt(dateParts[1], 10) - 1,
                    parseInt(dateParts[2], 10),
                    parseInt(timeParts[0], 10),
                    parseInt(timeParts[1], 10),
                    parseInt(timeParts[2], 10)
                );
            },
            maxBirthDateForPurchase() {
                let appTime = Date.now() - this.timeAtStart;

                /* -----------------------------------------------------------
                | Don't add app time if it has been running for less than a day.
                | Be this thorough because people sign up straight away when the turn 18..
                |---------------------------------------------------------- */
                if (appTime < 86400000) {
                    appTime = 0;
                }

                return new Date(this.time18YearsAgo + appTime);
            },
            time18YearsAgo() {
                let date = new Date(this.dateNow.getTime());
                date.setFullYear(date.getFullYear() - 18);
                return date.getTime();
            },
            // Validate form
            validation() {
                let valid = true;
                let personInfoIsRequired = !this.showCustomerInfo;
                let nonRequiredInputs = ['subscription']; // Only sent to back end. JS uses selectedSubscriptionId.

                if (!personInfoIsRequired) {
                    nonRequiredInputs = ['subscription', 'name1', 'name2', 'ssn', 'email', 'phoneMobile', 'street', 'zipCode', 'city'];
                }

                const validation = {};

                for (let key in this.inputs) {
                    validation[key] = !this.form.submitted;
                }

                if (!this.form.submitted) {
                    return validation;
                }

                for (let key in this.inputs) {
                    let item = this.inputs[key];
                    let modelValue = this[key];
                    let isNonRequiredInput = nonRequiredInputs.indexOf(key) > -1;

                    /* -----------------------------------------------------------
                    | Required items must not be null or empty string.
                    |---------------------------------------------------------- */
                    if (item.required === true && !isNonRequiredInput) {
                        if (typeof modelValue === 'string') {
                            modelValue = modelValue.replace(/^\s\s*/, '').replace(/\s\s*$/, '');
                        }
                        validation[key] = (modelValue !== null && modelValue !== '');
                    } else {
                        validation[key] = true;
                    }

                    /* -----------------------------------------------------------
                    | Autogiro info required when payment type is autogiro.
                    |---------------------------------------------------------- */
                    if (key === 'autogiroBankClr' || key === 'autogiroBankAccount') {
                        if (this.paymentType === 'autogiro') {
                            validation[key] = (modelValue !== null && modelValue !== '');
                        } else {
                            validation[key] = true;
                        }
                    }

                    /* -----------------------------------------------------------
                    | E-mail.
                    |---------------------------------------------------------- */
                    else if (key === 'email' && !isNonRequiredInput) {
                        validation[key] = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/.test(modelValue);
                    }

                    /* -----------------------------------------------------------
                    | SSN.
                    |---------------------------------------------------------- */
                    else if (key === 'ssn') {
                        if (!/^\d{6}-\d{4}$/.test(modelValue)) {
                            validation[key] = false;
                        } else {
                            validation[key] = this.validateBirthDate(modelValue);
                        }
                    }

                    /* -----------------------------------------------------------
                    | Terms must be checked.
                    |---------------------------------------------------------- */
                    else if (key === 'terms') {
                        validation[key] = modelValue === true;
                    }

                    /* -----------------------------------------------------------
                    | Privacy must be checked.
                    |---------------------------------------------------------- */
                    else if (key === 'privacy') {
                        validation[key] = modelValue === true;
                    }
                }

                return validation;
            },
            isValid() {
                const validation = this.validation;
                return !Object.keys(validation).map(key => validation[key]).filter(item => !item).length;
            }

        },
        watch: {
            ssn: function (newValue, oldValue) {
                this.personLookupError = null;
                let ssn = this.formatSSN(newValue);
                if (ssn) {
                    var ssnTwoDigitYear = ssn.substring(2);
                    if (newValue !== ssnTwoDigitYear) {
                        this.ssn = ssnTwoDigitYear;
                    } else {
                        this.clickPersonLookupButton();
                    }
                } else {
                    ssn = newValue.replace(/[^\d-]/, '').substring(0, 13);
                    if (newValue !== ssn) {
                        this.ssn = ssn;
                    }
                }
            }
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

                this.log('MEMBERSHIP CHECKOUT');

                // Setup Axios
                this.API = axios.create({
                    baseURL: webienSite.api,
                    headers: {
                        'content-type': 'application/json',
                        'X-WP-Nonce': webienSite.nonce
                    }
                });

                this.updateFromLocalStorage();

                //this.log(this.gyms);

                this.appReady = true;

            },
            goToStep(step) {
                this.currentStep = step;

                this.$nextTick(() => {
                    // Scroll into position
                    let top = jQuery('.checkout form').offset().top;
                    jQuery("html, body").animate({scrollTop: top - 100 + 'px'});
                });

            },
            updateFromLocalStorage() {
                let formObject = JSON.parse(localStorage.getItem('membershipForm'));

                if (formObject) {
                    this.selectedGym = formObject.selectedGym;
                }

            },
            setGym(gym) {
                this.log(gym);
                this.selectedGym = gym.id;
            },
            /* -----------------------------------------------------------
            | Get person info from SSN.
            |---------------------------------------------------------- */
            clickPersonLookupButton() {
                let app = this;

                if (!this.validateBirthDate(this.ssn)) {
                    this.log('invalid birth date');
                    return;
                }

                this.showPersonInfo = false;
                this.personLookupError = null;

                this.lookupCustomerId = null;
                this.lookupCustomerEmail = null;
                this.showCustomerInfo = false;

                this.isSendingPersonLookup = true;
                let businessUnit = this.selectedGym;
                let ssn = this.ssn;

                this.lookupLoading = true;


                this.API.get(webienSite.api, {
                    params: {
                        action: 'person_lookup',
                        data: {
                            businessUnit: businessUnit,
                            ssn: ssn
                        }
                    }
                })
                    .then(function (response) {
                        app.log(response.data);
                        app.handleClickPersonLookupSuccess(response.data);

                    })
                    .catch(function (error) {
                        app.log(error);
                        app.handleClickPersonLookupError();

                    })
                    .finally(function () {
                        // always executed
                        app.lookupLoading = false;
                    });


            },
            handleClickPersonLookupSuccess: function (body) {
                this.isSendingPersonLookup = false;
                if (body.status !== 'FOUND') {
                    /*-----------------------------------------------------------
                    | User is already member. Show message.
                    |----------------------------------------------------------*/
                    if (body.id) {
                        this.lookupCustomerId = body.id;
                        this.lookupCustomerEmail = body.email;
                        this.showCustomerInfo = true;
                        return;
                    }

                    /*-----------------------------------------------------------
                    | Show error message.
                    |----------------------------------------------------------*/
                    this.personLookupError = this.getPersonLookupErrorMessage(body);
                    return;
                }
                this.showPersonInfo = true;
                let data = body.data;
                this.form.name1 = data.firstName;
                this.form.name2 = data.lastName;
                if (data.shippingAddress) {
                    this.form.street = data.shippingAddress.street;
                    this.form.zipCode = data.shippingAddress.postalCode;
                    this.form.city = data.shippingAddress.city;
                }
            },

            handleClickPersonLookupError() {
                this.isSendingPersonLookup = false;
                this.personLookupError = this.getPersonLookupErrorMessage();
            },

            getPersonLookupErrorMessage: function (body) {
                this.log('LOOKUP ERROR');
                this.log(body);

                if (!body) {
                    this.showPersonInfo = true;
                    return 'Kunde inte hämta adressupgifter.';
                }
                if (body.status === 'has_membership') {
                    return 'Ett fel har inträffat, om du redan har ett aktivt medlemskap hos oss var vänlig kontakta ditt närmaste gym.';
                }
                if (body.status === 'NOT_FOUND') {
                    this.showPersonInfo = true;
                    return 'Hittade ingen person med det personnumret.';
                }
                if (body.status === 'EXISTS_IN_BRP') {
                    return 'Personnumret finns redan registrerat i våra system. Kontakta receptionen på ditt lokala gym för mer hjälp.';
                }
                if (body.errorCode) {
                    if (body.errorCode === 'INVALID_INPUT') {
                        if (body.fieldErrors && body.fieldErrors.length) {
                            var message = '';
                            for (var i = 0; i < body.fieldErrors.length; i++) {
                                var fieldError = body.fieldErrors[i];
                                if (fieldError.errorCode === 'FIELD_MANDATORY' && fieldError.field === 'businessUnit') {
                                    message += 'Välj ett gym ovan.' + '\n';
                                } else {
                                    message += fieldError.field + ': ' + fieldError.errorCode + '\n';
                                }
                            }
                            return message;
                        }
                    }
                }
                this.showPersonInfo = true;
                return 'Kunde inte hämta adressupgifter.';
            },
            formatSSN(ssn) {
                let ssnYear;
                if (/^\d{8}-\d{4}$/.test(ssn)) {
                    return ssn;
                } else if (/^\d{6}-\d{4}$/.test(ssn)) {
                    ssnYear = this.getFullYearFromTwoDigitYear(parseInt(ssn.substr(0, 2), 10));
                    return ssnYear + ssn.substr(2);
                } else if (/^\d{10}$/.test(ssn)) {
                    ssnYear = this.getFullYearFromTwoDigitYear(parseInt(ssn.substr(0, 2), 10));
                    return ssnYear + ssn.substr(2, 4) + '-' + ssn.substr(6, 4);
                } else if (/^\d{12}$/.test(ssn)) {
                    return ssn.substr(0, 8) + '-' + ssn.substr(8, 4);
                }
                return null;
            },
            validateBirthDate(ssn) {
                let ssnFormatted = this.formatSSN(ssn);
                if (!ssnFormatted) {
                    return false;
                }
                let birthDate = new Date(
                    parseInt(ssnFormatted.substr(0, 4), 10),
                    parseInt(ssnFormatted.substr(4, 2), 10) - 1,
                    parseInt(ssnFormatted.substr(6, 2), 10)
                );
                this.log(birthDate.getTime() + ' > ' + this.maxBirthDateForPurchase.getTime());
                if (birthDate.getTime() > this.maxBirthDateForPurchase.getTime()) {
                    return false;
                }
                return true;
            },
            getFullYearFromTwoDigitYear: function (twoDigitYear) {
                var date = new Date(this.dateNow.getTime());
                var yearNow = date.getFullYear();
                var centuryNow = Math.floor(yearNow / 100) * 100;
                var year = centuryNow + twoDigitYear;
                if (year > yearNow) {
                    year -= 100;
                }
                return year;
            },
            inputBlur: function (event) {
                let key = event.target.name;
                if (key !== undefined) {
                    let item = this.inputs[key];
                    if (item !== undefined) {
                        let modelValue = this[key];
                        if (!item.touched) {
                            item.touched = true;
                        }
                    }
                }
            },
            /* -----------------------------------------------------------
            | Submit form. Touch all and validate.
            |---------------------------------------------------------- */
            submit: function (event) {
                this.form.submitted = true;

                if (!this.isValid) {
                    event.preventDefault();
                }
            },
            fixHtmlEntities(html) {
                let decodedString = document.createElement("div");
                decodedString.innerHTML = html;
                return decodedString.textContent;
            },
            netsPayment() {
                let app = this;

                this.API.get(webienSite.api, {
                    params: {
                        action: 'nets_payment',
                        data: {
                            "checkout": {
                                "integrationType": "EmbeddedCheckout",
                                "url": app.widget.checkoutUrl,
                                "termsUrl": app.widget.termsUrl,
                                "appearance": {
                                    "textOptions": {
                                        "completePaymentButtonText": "pay"
                                    },
                                    "displayOptions": {
                                        "showMerchantName": true, // Live mode only
                                        "showOrderSummary": false
                                    },
                                }
                            },
                            "order": {
                                "items": [
                                    {
                                        "reference": "ref42",
                                        "name": "Demo product",
                                        "quantity": 2,
                                        "unit": "pcs",
                                        "unitPrice": 80000,
                                        "grossTotalAmount": 160000,
                                        "netTotalAmount": 160000
                                    },
                                    {
                                        "reference": "discount",
                                        "name": "Demo discount",
                                        "quantity": 1,
                                        "unit": "pcs",
                                        "unitPrice": -20000,
                                        "grossTotalAmount": -20000,
                                        "netTotalAmount": -20000
                                    }
                                ],
                                "amount": 140000,
                                "currency": "SEK",
                                "reference": "Demo Order"
                            }
                        }
                    }
                })
                    .then(function (response) {

                        app.log(response.data);

                        if (!response.data.paymentId) {
                            app.log('Error: No paymentId');
                            return;
                        }

                        let paymentId = response.data.paymentId;

                        // Load form with JS SDK
                        const checkoutOptions = {
                            checkoutKey: app.nets.key,
                            paymentId: paymentId,
                            containerId: "nets-checkout-container",
                            language: "sv-SE",
                        };
                        const checkout = new Dibs.Checkout(checkoutOptions);
                        checkout.on('payment-completed', function (response) {
                            window.location = app.widget.successPage + '?paymentId=' + paymentId;
                        });


                    })
                    .catch(function (error) {
                        console.error(error);
                    })
                    .finally(function () {
                        // always executed
                    });

            },
        },
        mounted: function () {

            this.init();

            this.netsPayment();
        },
        created() {
        },
        components: {},
    })
;


// Mount app to DOM
if (document.getElementById("membership-checkout")) {

    /**
     * Prepare data with input models.
     */
    const data = {};
    Object.keys(membershipCheckout.inputs).forEach(key => {
        data[key] = membershipCheckout.inputs[key].value;
    });

    window.$membershipCheckout = membershipCheckoutApp.mount('#membership-checkout');
}

