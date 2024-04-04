<script>
    const gymList = {
        list: '<?= json_encode($gymList) ?>',
        filters: '<?= json_encode($gymFilters) ?>'
    };
    console.log(JSON.parse(gymList.list));

</script>

<div id="gym-list" class="gym-list">

    <div class="loader" v-show="lookupLoading">
        <div class="txt">
            <p><?= __('Hämtar din plats', 'webien') ?></p>
            <?= file_get_contents(WEBIEN_SITE_PLUGIN_PATH . '/assets/images/loader.svg') ?>

        </div>
    </div>

    <div class="inner" :class="[{ready:appReady},{loading:loading}]">

        <div class="sidebar">
            <div class="top">
                <span class="breadcrumb"><a href="/"><?= __('Member 24', 'webien') ?></a> <?= __('/ Våra gym', 'webien') ?></span>
                <h3 class="filter-title elementor-heading-title elementor-size-default haus-gradient-text">Filtrera och sortera</h3>
            </div>

            <div class="group">
                <span class="label"><?= __('Sortera', 'webien') ?></span>

                <button class="option radio" @click="sort('asc')" :class="{selected:sortList === 'asc'}">
                    <span class="toggle"></span>
                    <span class="txt"><?= __('Bokstavsordning', 'webien') ?></span>
                </button>

                <button class="option radio" @click="sort('geo')" :class="{selected:sortList === 'geo'}">
                    <span class="toggle"></span>
                    <span class="txt"><?= __('Närhet', 'webien') ?></span>
                </button>

            </div>


            <div class="group" v-for="filter in filters" :key="filter.key">
                <span class="label">{{filter.title}}</span>

                <button v-for="item in filter.items" class="option" @click="updateFilter(filter.key, item)" :class="{selected: activeFilters[filter.key] && activeFilters[filter.key].includes(item.id)}">
                    <span class="toggle"><i class="icon icon-Done"></i></span>
                    <span class="txt">{{item.name}}</span>
                </button>
            </div>

        </div>

        <div class="list">
            <div class="search">
                <input type="text" v-model="searchText" placeholder="<?= __('Sök gym', 'webien') ?>">
                <button class="filter" @click="openFilterOverlay()"><?= __('Filter', 'webien') ?></button>
            </div>

            <div class="items">
                <a class="item gym" :class="gym.type" :href="gym.url" v-for="gym in list" :key="gym.id">
                    <img :src="gym.img" :alt="gym.name">
                    <div class="distance"  v-show="gym.distance && gym.type === 'child'">
                        <span>{{gym.distance}} km</span>
                    </div>
                    <div class="txt">
                        <h3 class="title">{{gym.name}}</h3>
                        <p v-if="gym.type === 'parent'" class="gym-count">{{gym.gymCount}} <?= __('st gym', 'webien') ?></p>
                        <p v-if="gym.type === 'child'" class="address">{{gym.address}}</p>
                        <div class="tags" v-show="gym.activities.includes('group') || gym.activities.includes('pt')">
                            <span class="tag group" v-show="gym.activities.includes('group')"><?= __('Gruppträning', 'webien') ?></span>
                            <span class="tag pt" v-show="gym.activities.includes('pt')"><?= __('Personlig träning', 'webien') ?></span>
                        </div>
                    </div>
                </a>
            </div>

            <div class="no-results" v-show="!list.length">
                <h3 class="title"><?= __('Inga resultat', 'webien') ?></h3>
            </div>


        </div>

    </div>




    <div class="overlay" :class="{active:overlay.open}">
        <div class="bkg" @click="closeFilterOverlay()"></div>

        <div class="top">
            <button class="close" @click="closeFilterOverlay()"><i class="icon icon-Close"></i></button>
        </div>

        <div class="spacer"></div>


        <div class="content">
            <div class="inner">

                <h5 class="overlay-title">{{overlay.title}}</h5>

                <div class="filters">

                    <div class="group">
                        <span class="label"><?= __('Sortera', 'webien') ?></span>

                        <button class="option radio" @click="sort('asc')" :class="{selected:sortList === 'asc'}">
                            <span class="toggle"></span>
                            <span class="txt"><?= __('Bokstavsordning', 'webien') ?></span>
                        </button>

                        <button class="option radio" @click="sort('geo')" :class="{selected:sortList === 'geo'}">
                            <span class="toggle"></span>
                            <span class="txt"><?= __('Närhet', 'webien') ?></span>
                        </button>

                    </div>


                    <div class="group" v-for="filter in filters" :key="filter.key">
                        <span class="label">{{filter.title}}</span>

                        <button v-for="item in filter.items" class="option" @click="updateFilter(filter.key, item)" :class="{selected: activeFilters[filter.key] && activeFilters[filter.key].includes(item.id)}">
                            <span class="toggle"><i class="icon icon-Done"></i></span>
                            <span class="txt">{{item.name}}</span>
                        </button>
                    </div>

                    <div class="bottom-actions">
                        <button class="btn" @click="closeFilterOverlay()"><?= __('Filtrera', 'webien') ?></button>
                    </div>

                </div>


            </div>
        </div>


    </div>

</div>

<?php
