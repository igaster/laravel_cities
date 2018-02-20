<!--

Creates the following inputs that will be submited:

	- geo-id
	- geo-name
	- geo-long
	- geo-lat
	- geo-country
	- geo-country-code

Example Usage

	<form action="/submit/url" method="POST">
		<geo-select
		    v-model="model.geo_id"
			prefix = "my-prefix"
			api-root-url = "/api/geo"
			:enable-breadcrumb = "true"
			:countries = "[390903,3175395]"
			:filterIds="[1,2,3]"
		></geo-select>
		<input type="submit">
	</form>

	// OR

	<geo-select v-model="user.geo_id" :filterIds="[2921044, 2822542]" @input="yourGeoValidation()"
			:enable-labels="true" :enable-breadcrumb="false" :hide-empty="false">
	</geo-select>

	@see Example: https://i.imgur.com/O5ZlKEI.png
-->

<template>
	<div>
		<div v-if="location">
			<input type="hidden" :name="prefix+'-id'" v-model="value">
			<input type="hidden" :name="prefix+'-name'" :value="location.name">
			<input type="hidden" :name="prefix+'-long'" :value="location.long">
			<input type="hidden" :name="prefix+'-lat'" :value="location.lat">
			<input type="hidden" :name="prefix+'-timezone'" :value="location.timezone">
			<input type="hidden" :name="prefix+'-country-code'" :value="location.country">
		</div>

		<div>
			<div v-if="breadCrumb" class='geo-breadcrumb'>
				<div class="form-group">
					<label>Your Location:</label><br>
					<span class="geo-breadcrumb-item" v-for="item in path">{{item.name}}</span>
					<a class="btn btn-xs btn-default pull-right" href="#" @click="breadCrumb=false">Change Location...</a>
					<div class="clearfix"></div>
				</div>
			</div>
			<div v-else>
				<transition-group name="smoothing" tag="div">
					<div v-for="(locations, level) in geo" class="form-group row" :key="'level-'+level">
						<label class="col-2 col-form-label" v-if="enableLabels">
							{{ ['Country', 'State/Province', 'City'][level] }}
						</label>
						<p v-if="loadingIndex"><i class="fa fa-cog fa-spin"></i> Loading...</p>
						<div class="col-8">
							<select v-if="!hideEmpty || locations.length" class="form-control"
									v-model="selected[level]" @change="updateSelected(level)" placeholder="Test">
								<option :value="null" disabled>Select {{ ['country', 'state/province', 'city'][level] }}</option>
								<option v-for="item in locations" :value="item">{{item.name}}</option>
							</select>
						</div>
					</div>
				</transition-group>
			</div>
		</div>
	</div>
</template>

<script>
    import axios from 'axios'

    export default {
        name: 'geo-select',
        props: {
            apiRootUrl: {
                default: '/api/geo',
            },
            prefix: {
                default: 'geo',
            },
            enableBreadcrumb: {
                default: true,
            },
            filterIds: Array,
            value: null,
            enableLabels: {
                default: false
            },
            hideEmpty: {
                default: true
            }
        },
        data() {
            return {
                geoId: this.value,
                geo: [],
                selected: [],
                loadingIndex: null,
                breadCrumb: false,
            };
        },
        computed: {
            location: function() {
                return this.selected.length ? this.selected[this.selected.length - 1] : {};
            }
        },
        watch: {
            value: function (newValue, oldValue) {
                if (!oldValue) {
                    this.renderLocationsByGeoId(newValue);
                }
            }
        },
        created: function () {
            window.geo = this;

            if (this.geoId) {
                this.renderLocationsByGeoId(this.geoId);
            } else {
                this.renderCountries();
            }
        },
        methods: {
            renderCountries() {
                let self = this;
                let url = this.apiUrl('countries');

                axios.get(url).then(function(response) {
                    self.resetSelects(0);
                    self.geo[0] = response.data;
                    self.selected[0] = null;
                    self.supplement();
                    self.$forceUpdate();
                });
            },
            renderLocationsByGeoId: function(geoId) {
                let self = this;
                let url = this.apiUrl('ancestors/' + geoId);

                axios.get(url).then(function(response) {
                    response.data.forEach(function(locations, level) {
                        locations = self.applyFilter(locations);
                        self.geo[level] = locations;
                        self.selected[level] = null;

                        locations.forEach(function(location, i) {
                            if (location.isSelected) {
                                self.selected[level] = location;
                            }
                        });
                    });

                    self.supplement();
                    self.$forceUpdate();
                });
            },
            supplement() {
                if (this.hideEmpty) return;

                for (let i = 0; i < 3; i++) {
                    if (!this.geo[i]) {
                        this.geo[i] = [];
                        this.selected[i] = null;
                    }
                }
            },
            resetSelects(level) {
                this.geo = this.geo.splice(0, level+1);
                this.selected = this.selected.splice(0, level+1);
            },
            applyFilter(locations) {
                let self = this;

                if (!self.filterIds || !self.filterIds.length) {
                    return locations;
                }

                let filteredLocations = locations.filter(function (geo) {
                    return self.filterIds.includes(geo.id); //geo.id.match(/Foo/)
                });

                return filteredLocations;
            },
            apiUrl(path) {
                return this.apiRootUrl + '/' + path;
            },
            updateSelected(level) {
                this.resetSelects(level);

                let location = this.getSelectedByLevel(level);
                this.$emit('input', location.id);
                //this.$emit('change', location.id);

                this.renderLocationsByGeoId(location.id);
            },
            getSelectedByLevel(level) {
                return this.selected[level];
            }
        }
    }
</script>

<style lang="scss">
	.smoothing-leave-active { transition: height 1s; }

	.geo-breadcrumb{
		list-style: none;
		.geo-breadcrumb-item {
			display: inline;
			&+span:before {
				padding: 8px;
				color: black;
				font-family: FontAwesome;
				content: "»";
			}
		}
	}
</style>