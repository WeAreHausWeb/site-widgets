import {createApp} from 'vue';

import turfDistance from '@turf/distance';
import * as turfHelpers from "@turf/helpers";

const gymApp = createApp({
        name: "Gyms",
        data() {
            return {
                appReady: false,
                useLog: false,
                gyms: JSON.parse(gymList.list),
                filters: JSON.parse(gymList.filters),
                searchText: '',
                activeFilters: [],
                filterList: [],
                sortList: 'asc',
                reloader: 0,
                currentPos: null,
                loading: false,
                overlay: {
                    open: false,
                    title: '',
                }
            }
        },
        mixins: [],
        computed: {

            list() {
                let app = this;
                let arr = this.gyms;

                // Filter gyms from search
                if (this.searchText.length > 1) {
                    // Clear filters since search will overrule
                    this.activeFilters = [];

                    return this.gyms.filter(function (gym) {
                        return gym.name.toLowerCase().indexOf(app.searchText.toLowerCase()) > -1;
                    });

                }

                // ------- Filter gyms from active filters

                // City
                if (this.activeFilters?.city?.length) {
                    arr = arr.filter(function (gym) {
                        return app.activeFilters.city.includes(gym.city.id);
                    });
                }

                // Activities
                if (this.activeFilters?.activities?.length) {
                    arr = arr.filter(function (gym) {
                        console.log(gym);
                        return gym.activities.filter(value => app.activeFilters.activities.includes(value)).length;
                    });
                }

                if(this.sortList === 'geo') {

                    arr = arr.filter(function (gym) {
                        return gym.type === 'child';
                    });

                    arr = arr.sort((a, b) => {
                        return a.distance - b.distance;
                    });

                }

                return arr;

            },
        },
        watch: {},
        methods: {
            log(str) {
                if (this.useLog) console.log(str);
            },
            init() {

                // Dev
                if (window.location.href.indexOf(".test") > -1) {
                    this.useLog = true;
                }

                this.generateFilterList();

                this.log(this.gyms);

                this.appReady = true;

            },
            sort(type) {
                this.sortList = type;

                if (type === 'geo') {
                    this.getLocation();
                }
            },
            getLocation() {
                if (this.currentPos) return;

                if (navigator.geolocation) {
                    let locationOptions = {
                        enableHighAccuracy: true,
                        timeout: 10000, // Allow 20 sec on first location
                        maximumAge: 0
                    };
                    this.loading = true;
                    navigator.geolocation.getCurrentPosition(this.locationSuccess, this.locationError, locationOptions)
                }
            },

            locationSuccess(pos) {
                let app = this;

                this.currentPos = pos.coords;

                // calulate distance for each gym
                for (let city in this.gyms) {
                    this.gyms.forEach(function (gym) {

                        let from = turfHelpers.point([app.currentPos.longitude, app.currentPos.latitude]);
                        let to = turfHelpers.point([gym.lng, gym.lat]);
                        let options = {units: 'kilometers'};

                        gym.distance = Math.round(turfDistance(from, to, options));
                    });
                }

                // Force list reload
                this.reloader++;
                this.loading = false;

            },
            locationError(err) {
                console.log('Location error..');
                console.log(err.code);
                console.log(err);

                if (err.code == err.PERMISSION_DENIED) {
                    console.log('User denied geolocation permission..');
                    console.log(err.code);
                } else {
                    console.log(err.code);
                }

            },
            openFilterOverlay() {
                // Trigger height calculation
                //window.appHeight();

                this.overlay.open = true;
                this.overlay.title = 'Filtrera och sortera';

                // Add class to body
                this.$nextTick(() => {
                    document.body.classList.add('webien-overlay-open');

                    // Always start on top
                    let content = document.querySelector('#gyms-list .overlay .inner');
                    content.scrollTo({top: 0});
                });
            },
            closeFilterOverlay() {
                this.overlay.open = false;
            },
            clearFilters() {
                this.activeFilters = [];
                this.closeFilterOverlay();
            },
            updateFilter(key, item) {

                if (this.activeFilters[key] === undefined) {
                    this.activeFilters[key] = [];
                }

                let index = this.activeFilters[key].indexOf(item.id);

                if (index > -1) {
                    this.activeFilters[key].splice(index, 1);
                } else {
                    this.activeFilters[key].push(item.id);
                }

                console.log(this.activeFilters);

                this.reloader++;
            },
            generateFilterList() {
                let app = this;

                Object.values(this.filters).forEach(function (filter) {

                    Object.values(filter.items).forEach(function (item) {
                        if (filter.key === 'city') {
                            let count = 0;
                            Object.values(app.gyms).forEach(function (gym) {
                                if(gym.city.id === item.id && gym.type === 'child' ) {
                                    count ++;
                                }
                            });
                            item.name = item.name + ' (' + count + ')';
                        }

                        if (filter.key === 'activities') {
                            let count = 0;
                            Object.values(app.gyms).forEach(function (gym) {
                                count += Object.values(gym.activities).filter(function (activity) {
                                    return activity.includes(item.id)
                                }).length;
                            });
                           item.name = item.name + ' (' + count + ')';
                        }
                    });

                });

                return this.filters;
            },

        },
        mounted: function () {

            this.init();

        },
        created() {
        },
        components: {},
    })
;


// Mount app to DOM
if (document.getElementById("gym-list")) {
    window.$gymApp = gymApp.mount('#gym-list');
}

