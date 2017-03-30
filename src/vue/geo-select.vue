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

		<div v-if="location() !== null">
    		<input type="hidden" :name="prefix+'-id'" :value="location().id">
    		<input type="hidden" :name="prefix+'-name'" :value="location().name">
    		<input type="hidden" :name="prefix+'-long'" :value="location().long">
    		<input type="hidden" :name="prefix+'-lat'" :value="location().lat">
    		<input type="hidden" :name="prefix+'-country'" :value="country().name">
    		<input type="hidden" :name="prefix+'-country-code'" :value="location().country">
		</div>

		<div v-show="breadCrumb" class='geo-breadcrumb'>
			<div class="form-group">
				<label>Your Location:</label><br>
				<span class="geo-breadcrumb-item" v-for="item in path">{{item.name}}</span>				
				<a class="btn btn-xs btn-default pull-right" href="#" @click="breadCrumb = false">Change Location...</a>
				<div class="clearfix"></div>
			</div>
		</div>

		<div v-show="!breadCrumb">
			<div class="form-group">
				<label>Select your Location: </label>
				<p v-if="loadingIndex == 0"><i class="fa fa-cog fa-spin"></i> Loading Countries...</p>
				<select v-else class="form-control _select2" v-model="values[0]" @change="itemSelected(0)">
				  <option v-for="item in items[0]" :value="item.id">{{item.name}}</option>
				</select>
			</div>    

			<div v-for="i in count" class="form-group">
				<p v-if="loadingIndex == i"><i class="fa fa-cog fa-spin"></i> Loading...</p>
				<div v-else>
					<select v-if="hasItems(i)" class="form-control _select2" v-model="values[i]"  @change="itemSelected(i)">
					  <option v-for="item in items[i]" :value="item.id">{{item.name}}</option>
					</select>
					<div v-if="!hasItems(i) && enableBreadcrumb">
						<a class="btn btn-xs btn-default pull-right" href="#" @click="breadCrumb = true">Confirm Location</a>
						<div class="clearfix"></div>
					</div>
				</div>
			</div> 
		</div>

    </div>
</template>

<script>
    export default {
    	props: {

			apiRootUrl: {
				default: '/api',
			},
			prefix: {
				default: 'geo',
			},
			countries: {
				default: null,
			},
			enableBreadcrumb: {
				default: true,
			}
    	},
        data(){
            return {
            	count: 0,
                items: [],
                values: [],
                path: [],
                loadingIndex: null,
                breadCrumb: false,
            };
        },
		created: function () {
			this.getChildrenOf(null,0);
		},
        methods: {
			itemSelected(index) {
				var that = this;
				if(this.values[index]>0){
					this.path[index]=this.items[index].find(function(item){
						return item.id == that.values[index];
					});
					this.setIndex(index);
					this.getChildrenOf(this.values[index], index+1);
				}
			},
			setIndex(index){
				this.count = index+1;
				this.values.splice(index+1,10);
				this.path.splice(index+1,10);
			},
			hasItems(index){
				return (this.items[index] instanceof Array) && (this.items[index].length > 0);
			},
			location(){
				if (this.path.length==0)
					return null;

				return this.path[this.path.length-1];
			},
			country(){
				return this.path[0];
			},
			getChildrenOf: function(id, index){
				this.loadingIndex = index;

				var url = this.apiRootUrl;
				if(id==null){
					if(this.countries==null)
						url+='/geo/countries'
					else
						url+='/geo/items/'+this.countries;
				}
				else
					url+='/geo/children/'+id;

				axios.get(url, {
				}).then(response => {
					this.items[index] = response.data;
					this.breadCrumb = this.enableBreadcrumb && !this.hasItems(index);
					this.loadingIndex = null;
					this.$forceUpdate();
					if(this.items[index].length==1){
						this.values[index] = this.items[index][0].id;
						this.itemSelected(index);
					}
				})
				.catch(error => {
					alert('Error');
					console.log(error.response.data);
				});
			},
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
			content: "\f0da";
		}
    }
}
</style>