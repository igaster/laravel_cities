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
					<select v-if="locations.length" class="form-control" v-model="selected[level]" @change="updateSelected(level)">
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
                if (!oldValue || newValue != oldValue) {
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
            renderLocationsByGeoId: function(geoId) {
                let self = this;
                let url = this.apiUrl('ancestors/' + geoId);

                axios.get(url).then(function(response) {
                    console.log(response.data);
                    response.data.forEach(function(locations, level) {
                        locations.forEach(function(location, i) {
                            if (i == 0) {
                                self.selected[level] = location;
                            }

                            if (location.isSelected) {
                                self.selected[level] = location;
                            }
                        });
                    });

                    self.geo = response.data;
                    self.$forceUpdate();
                });
            },
            renderCountries() {
                let self = this;
                let url = this.apiUrl('countries');

                axios.get(url).then(function(response) {
                    self.geo[0] = response.data;
                    self.$forceUpdate();
                });
            },
            apiUrl(path) {
                return this.apiRootUrl + '/' + path;
            },
            updateSelected(level) {
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