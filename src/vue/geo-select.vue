<!--

Creates the following inputs that will be submited:

	- geo-id
	- geo-name
	- geo-long
	- geo-lat
	- geo-country
	- geo-country-code

Example Usage:

	<form action="/submit/url" method="POST">
		<geo-select
		    v-model="model.geo_id"
		    @input="onSelectGeoYourMethod"
		    @apply="onLoadGeoDataByInitialIdOrChange"
		    ref="geoSelect"
			prefix="my-prefix"
			api-root-url="/api/geo"
			:enable-breadcrumb="true"
			:hide-empty="false"
			:countries="[390903,3175395]"
			:filterIds="[1,2,3]" />
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
		<div v-if="selectedLocation">
			<input type="hidden" :name="prefix+'-id'" v-model="value">
			<input type="hidden" :name="prefix+'-name'" :value="selectedLocation.name">
			<input type="hidden" :name="prefix+'-long'" :value="selectedLocation.long">
			<input type="hidden" :name="prefix+'-lat'" :value="selectedLocation.lat">
			<input type="hidden" :name="prefix+'-timezone'" :value="selectedLocation.timezone">
			<input type="hidden" :name="prefix+'-country-code'" :value="selectedLocation.country">
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
						<label class="col-sm-12 col-md-3 col-form-label" v-if="enableLabels">
							{{ ['Country', 'State/Province', 'City'][level] }}
						</label>
						<p v-if="loadingIndex"><i class="fa fa-cog fa-spin"></i> Loading...</p>
						<div class="col-sm-12 col-md-9">
                            <div class="select-wrapper"></div>
							<select v-if="!hideEmpty || locations.length" class="form-control"
									v-model="selectedByLevel[level]" @change="updateSelected(level)" placeholder="Test">
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
                geo: [], // All locations by level
                selectedByLevel: [],
                selectedLocation: null,
                loadingIndex: null,
                breadCrumb: false,
            };
        },
        watch: {
            value: function (newValue, oldValue) {
                if (!oldValue || !newValue) { this.renderLocationsByGeoId(newValue); }
            }
        },
        created: function() {
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
                    self.selectedByLevel[0] = null;
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
                        self.selectedByLevel[level] = null;

                        locations.forEach(function(location, i) {
							if (location.isSelected) {
                                self.selectedByLevel[level] = location;
                                self.selectedLocation = self.selectedByLevel[level];
							}
                        });
                    });

                    self.supplement();
                    self.$forceUpdate();

                    self.$emit('apply', self.selectedLocation.id, self.selectedLocation);
                });
            },
			supplement() {
                if (this.hideEmpty) return;

				for (let i = 0; i < 3; i++) {
                    if (!this.geo[i]) {
                        this.geo[i] = [];
                        this.selectedByLevel[i] = null;
					}
				}
			},
			resetSelects(level) {
                this.geo = this.geo.splice(0, level+1);
                this.selectedByLevel = this.selectedByLevel.splice(0, level+1);
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

                let selectedLocation = this.getSelectedByLevel(level);
                this.$emit('input', selectedLocation.id, selectedLocation); // @note Update v-model!
                this.renderLocationsByGeoId(selectedLocation.id);
			},
			getSelectedByLevel(level) {
                return this.selectedByLevel[level];
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
