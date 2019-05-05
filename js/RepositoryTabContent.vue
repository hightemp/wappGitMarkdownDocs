
<template>
    <div class="container-fluid">
        <div class="row flex-xl-nowrap2">
            <div class="tags-sidebar col-xl-2">
                <div class="container-fluid">
                    <div class="filter-row row flex-xl-nowrap2">
                        <div class="col-xl-10">
                            <b-form-input 
                                placeholder="Filter"
                                v-model="sTagFilterString"
                            ></b-form-input>
                        </div>
                        <div class="col-xl-2">
                            <b-button variant="success">Add</b-button>
                        </div>
                    </div>
                </div>
                <b-list-group>
                    <b-list-group-item
                        href="#"
                        :active="sActiveTag=='__all__'"
                        @click="fnSelectTag('__all__')"
                    >
                        All
                    </b-list-group-item>
                    <b-list-group-item 
                        v-for="(aItem, sKey) in oRepository.oTags"
                        href="#"
                        :active="sActiveTag==sKey"
                        v-if="sTagFilterString=='' 
                            || sTagFilterString!='' 
                            && sKey.indexOf(sTagFilterString)!=-1"
                        @click="fnSelectTag(sKey)"
                    >
                        {{ sKey }}
                    </b-list-group-item>
                </b-list-group>
            </div>
            <div class="articles-sidebar col-xl-2">
                <div class="container-fluid">
                    <div class="filter-row row flex-xl-nowrap2">
                        <div class="col-xl-10">
                            <b-form-input 
                                placeholder="Filter"
                                v-model="sArticleFilterString"
                            ></b-form-input>
                        </div>
                        <div class="col-xl-2">
                            <b-button variant="success">Add</b-button>
                        </div>
                    </div>
                </div>
                <b-list-group>
                    <b-list-group-item 
                        v-for="(sItem, iIndex) in aArticles"
                        href="#"
                        :active="iActiveArticle==iIndex"
                        v-if="sArticleFilterString=='' 
                            || sArticleFilterString!='' 
                            && sItem.indexOf(sArticleFilterString)!=-1"
                    >
                    {{ sItem }}
                    </b-list-group-item>
                </b-list-group>            
            </div>
            <div class="page-content col-xl-4">
            
            </div>
            <div class="page-preview col-xl-4">
            
            </div>
        </div> 
    </div>
</template>

<script>

import Vue, { VueConstructor } from 'vue'

export default {
    name: 'RepositoryTabContent',
    
    props: {
        oRepository: {
            type: Object,
            required: true
        },
        bActive: {
            type: Boolean,        
            required: true
        }
    },
    
    data: function()
    {
        return {
            sActiveTag: "__all__",
            iActiveArticle: -1,
            sTagFilterString: "",
            sArticleFilterString: ""
        };
    },
    
    computed: {
        aArticles: function() 
        {
            if (this.sActiveTag=="__all__") {
                return this.oRepository.aArticles;
            }
            
            return this.oRepository.oTags[this.sActiveTag];
        }
    },
    
    methods: {
        fnSelectTag: function(sTagName)
        {
            this.sActiveTag = sTagName;
            
        }
    },
    
    mounted: function()
    {
        console.log(this.oRepository, this.bActive);
    },
    
    created: function()
    {
        console.log('repositories tab created');
    }
};

</script>
