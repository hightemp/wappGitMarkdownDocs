 
<template>
    <div class="application-container container-fluid">
        <b-tabs 
            class="tabs-block"
            @input="fnSelectTab"
        >
            <b-tab 
                v-for="(oItem, iIndex) in aRepositories" 
                :active="iActiveTab == -1 && iIndex == 0 || iActiveTab == iIndex"
            >
                <template slot="title">
                    {{ oItem.sName }}
                    <b-link 
                        class="close-tab-button"
                        @click="fnCloseTab(iIndex)"
                        ><b>&#10005;</b></b-link>
                </template>
                <repository-tab-content
                    :oRepository="oItem"
                    :iIndex="iIndex"
                >
                </repository-tab-content>
            </b-tab>
            
            <template slot="tabs">
                <b-nav-item  
                    v-if="!bShowAddRepositoryButtonSpinner"
                    v-b-modal.add-new-repository-modal
                    href="#"
                >
                    <i class="fa fa-plus"></i>
                </b-nav-item>
                <b-nav-item  
                    href="#"
                >
                    <b-spinner 
                        v-if="bShowAddRepositoryButtonSpinner"
                        small 
                        type="grow"
                    ></b-spinner>
                </b-nav-item>
            </template>
        </b-tabs>
        
        <b-modal
            id="add-new-repository-modal"
            ref="add_new_repository_modal"
            title="Add new repository"
            @show="fnResetNewRepositoryModal"
            @ok="fnNewRepositoryFormSubmit"
        >
            <form 
                ref="add_new_repository_modal_form" 
                @submit.stop.prevent="fnNewRepositoryFormSubmit"
            >
                <b-form-group
                    :state="sNewRepositoryFieldState"
                    label="Repository URL"
                    label-for="repository-input"
                    :invalid-feedback="sNewRepositoryInvalidFeedback"
                >
                    <b-form-input 
                        placeholder="git@github.com:hightemp/wappGitMarkdownDocs.git"
                        id="repository-input"
                        ref="repository_url"
                        v-model="sRepositoryURL"
                    >
                    </b-form-input>
                </b-form-group>
            </form>
        </b-modal>
        
        <vue-snotify/>
        
        <div 
            v-show="bShowLoadingScreen"
        >
            <div class="loading-screen d-flex justify-content-center align-items-center">
                <b-spinner 
                    variant="primary"
                    style="width: 100px; height: 100px;" 
                ></b-spinner>
            </div>
        </div>
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
            sNewRepositoryFieldState: '',
            sNewRepositoryInvalidFeedback: '',
            sRepositoryURL: '',
            
            bShowAddRepositoryButtonSpinner: false,
            
            bShowLoadingScreen: false,
            
            iActiveTab: -1,
            aRepositories: [
            /*
                {
                    sName: "test",
                    sURL: "git@github.com:hightemp/wappGitMarkdownDocs.git",
                    sUser: '',
                    sPath: '',
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
            */
            ]
        }
    },
  
    methods: {
        fnCloseTab: function(iIndex)
        {
            console.log('fnCloseTab');
            this
                .$http
                .post(
                    '',
                    {
                        action: 'remove_repository',
                        repository: this.aRepositories[iIndex].sName
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
            console.log('fnGetRepositories');
            this.bShowLoadingScreen = true;
            
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
                    
                    this.aRepositories = oResponse.body.data;
                    
                    this.bShowLoadingScreen = false;
                });
        },
        fnAddRepository: function()
        {
            console.log('fnAddRepository');
            this.bShowAddRepositoryButtonSpinner = true;
            
            this
                .$http
                .post(
                    '',
                    {
                        action: 'add_repository',
                        url: this.sRepositoryURL
                    }
                ).then(function(oResponse)
                {
                    if (oResponse.body.status=='error') {
                        this.$snotify.error(oResponse.body.message, 'Error');
                        return;
                    }
                    //git@github.com:hightemp/docLinux.git
                    
                    this.bShowAddRepositoryButtonSpinner = false;

                    this.aRepositories.push(oResponse.body.data);
                    
                    this.sRepositoryURL = '';                     
                });
        },
        fnResetNewRepositoryModal: function()
        {
            this.sNewRepositoryFieldState = '';
            this.sNewRepositoryInvalidFeedback = '';
            this.sRepositoryURL = '';
            
            var oThis = this;
            
            setTimeout(function() {
                oThis.$refs.repository_url.$el.focus();
            }, 300);
        },
        fnCheckNewRepositoryForm: function()
        {
            console.log('fnCheckNewRepositoryForm');
            var bValid = this.$refs.add_new_repository_modal_form.checkValidity()
            
            for (var iIndex in this.aRepositories) {
                if (this.aRepositories[iIndex].sURL==this.sRepositoryURL) {
                    this.sNewRepositoryInvalidFeedback = "Repository already exists";
                    this.sNewRepositoryFieldState = 'invalid';
                    return false;
                }
            }

            this.sNewRepositoryInvalidFeedback = "Repository URL is required";
            
            this.sNewRepositoryFieldState = bValid ? 'valid' : 'invalid'
            
            return bValid
        },
        fnNewRepositoryFormSubmit: function(oEvent)
        {
            oEvent.preventDefault();
            
            if (!this.fnCheckNewRepositoryForm()) {
                return;
            }

            this.$nextTick(function() {
                this.$refs.add_new_repository_modal.hide();
            })
            
            this.fnAddRepository();
        },
        fnSelectTab: function(iIndex)
        {
            this.iActiveTab = iIndex;
            localStorage.setItem('iActiveTab', iIndex);
        }
    },
    
    created: function()
    {
        window.oApplication = this;
    },
    
    mounted: function()
    {
        this.iActiveTab = localStorage.getItem('iActiveTab');
        this.fnGetRepositories();
    }
});

</script>
