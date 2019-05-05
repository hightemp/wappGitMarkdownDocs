 
<template>
    <div class="application-container container-fluid">
        <div class="row1 row">
            <div class="container-fluid">
                <div class="row flex-xl-nowrap2">
                    <div class="col-xl-11">
                        <b-form-input placeholder="git@github.com:hightemp/wappGitMarkdownDocs.git">
                        </b-form-input>
                    </div>
                    <div class="col-xl-1">
                        <b-button variant="success" block>Add</b-button>
                    </div>
                </div>
            </div>
        </div>
        <b-tabs class="row2">
            <b-tab 
                v-for="(oItem, iIndex) in oRepositories" 
                :active="iActiveTab == -1 && iIndex == 0 || iActiveTab == iIndex"
            >
                <template slot="title">
                    {{ oItem.sName }}
                    <b-link class="close-tab-button">&#10005;</b-link>
                </template>
                <repository-tab-content
                    :oRepository="oItem"
                >
                </repository-tab-content>
            </b-tab>
        </b-tabs>
    </div>
</template>

<script>

import RepositoryTabContent from '../js/RepositoryTabContent.vue'

import Vue, { VueConstructor } from 'vue'

export default Vue.extend({
    name: 'Application',
    
    components: {
        'repository-tab-content' : RepositoryTabContent,
    },
    
    props: {
        
    },
    
    data: function()
    {
        return {
            iActiveTab: -1,
            oRepositories: [
                {
                    sName: "test",
                    sURL: "git@github.com:hightemp/wappGitMarkdownDocs.git",
                    oTags: {
                        "tag1": [
                            "article1"
                        ],
                        "tag2": [
                            "articles2"
                        ]
                    },
                    aArticles: [
                        "article1",
                        "articles2"
                    ]
                }
            ]
        }
    },
  
    methods: {
        
    },
    
    mounted: function()
    {
        console.log(this.oRepositories);
    }
});

</script>
