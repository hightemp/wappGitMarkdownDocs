 
<template>
    <div class="application-container container-fluid">
        <div class="row1 row">
            <div class="container-fluid">
                <div class="row flex-xl-nowrap2">
                    <div class="col-xl-11">
                        <b-form-input 
                            placeholder="git@github.com:hightemp/wappGitMarkdownDocs.git"
                            ref="repository_url"
                        >
                        </b-form-input>
                    </div>
                    <div class="col-xl-1">
                        <b-button 
                            variant="success"
                            @click="fnAddRepository"
                            block
                        >Add</b-button>
                    </div>
                </div>
            </div>
        </div>
        <b-tabs class="row2">
            <b-tab 
                v-for="(oItem, iIndex) in aRepositories" 
                :active="iActiveTab == -1 && iIndex == 0 || iActiveTab == iIndex"
            >
                <template slot="title">
                    {{ oItem.sName }}
                    <b-link 
                        class="close-tab-button"
                        @click="fnCloseTab(iIndex)"
                    >&#10005;</b-link>
                </template>
                <repository-tab-content
                    :oRepository="oItem"
                >
                </repository-tab-content>
            </b-tab>
        </b-tabs>
        <vue-snotify/>
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
            aRepositories: [
            /*
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
                },
                {
                    sName: "test2",
                    sURL: "git@github.com:hightemp/wappGitMarkdownDocs.git",
                    oTags: {
                        "tag1": [
                            "article21"
                        ],
                        "tag2": [
                            "articles22"
                        ]
                    },
                    aArticles: [
                        "article21",
                        "articles22"
                    ]
                }
            */
            ]
        }
    },
  
    methods: {
        fnCloseTab: function(iIndex)
        {
            this
                .$http
                .post(
                    '',
                    {
                        action: 'remove_repository',
                        name: this.aRepositories[iIndex].sName
                    }
                ).then(function(oResponse)
                {
                    if (oResponse.body.status=='error') {
                        this.$snotify.error(oResponse.body.message, 'Error');
                        return;
                    }
                    
                    this.aRepositories.splice(iIndex, 1);
                });
        },
        fnGetRepositories: function()
        {
            this
                .$http
                .post(
                    '',
                    {
                        action: 'get_repositories'
                    }
                ).then(function(oResponse)
                {
                    if (oResponse.body.status=='error') {
                        this.$snotify.error(oResponse.body.message, 'Error');
                        return;
                    }
                    
                    console.log('fnGetRepositories', oResponse.body.data);
                    
                    this.aRepositories = oResponse.body.data;
                });
        },
        fnAddRepository: function()
        {
            this
                .$http
                .post(
                    '',
                    {
                        action: 'add_repository',
                        url: this.$refs.repository_url.$el.value
                    }
                ).then(function(oResponse)
                {
                    if (oResponse.body.status=='error') {
                        this.$snotify.error(oResponse.body.message, 'Error');
                        return;
                    }//git@github.com:hightemp/docLinux.git
                    
                    this.aRepositories.push(oResponse.body.data);
                    
                    console.log('fnAddRepository', oResponse.body.data, this.aRepositories);
                });
        }
    },
    
    mounted: function()
    {
        this.fnGetRepositories();
    }
});

</script>
