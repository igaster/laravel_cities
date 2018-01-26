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
			prefix = "my-prefix"
			api-root-url = "\xxx\yyy"
			:enable-breadcrumb = "true"
			:countries = "[390903,3175395]"
		></geo-select>
		<input type="submit">
	</form>

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

		<div v-if="breadCrumb" class='geo-breadcrumb'>
			<div class="form-group">
				<label>Your Location:</label><br>
				<span class="geo-breadcrumb-item" v-for="item in path">{{item.name}}</span>
				<a class="btn btn-xs btn-default pull-right" href="#" @click="breadCrumb=false">Change Location...</a>
				<div class="clearfix"></div>
			</div>
		</div>
		<div v-else>
			<div v-for="(locations, level) in geo" class="form-group">
				<p v-if="loadingIndex"><i class="fa fa-cog fa-spin"></i> Loading...</p>
				<div>
					<select v-if="locations.length" class="form-control"
							v-model="selected[level]" @change="updateSelected(level)">
						<option :value="null"><!--Please choice location..--></option>
						<option v-for="item in locations" :value="item">{{item.name}}</option>
					</select>
				</div>
			</div>
		</div>

	</div>
</template>

<script>
    import axios from 'axios'

    export default {
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
            value: null
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
                //this.getChildrenOf(null, 0);
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

                    //self.geo = response.data;
                    self.$forceUpdate();
                });
            },
            resetSelects(level) {
                this.geo = this.geo.splice(0, level+1);
                this.selected = this.selected.splice(0, level+1);
            },
            applyFilter(locations) {
                let self = this;
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

                this.renderLocationsByGeoId(location.id);
            },
            getSelectedByLevel(level) {
                return this.selected[level];
            }
        }
    }
</script>

<style lang="scss">
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