
<template>
    <div class="container-fluid">
        <div class="row flex-xl-nowrap2">
            <div class="tags-sidebar col-xl-2">
                <div class="container-fluid">
                    <div class="filter-row row flex-xl-nowrap2">
                        <div class="filter-input-col">
                            <b-form-input 
                                placeholder="Filter"
                                v-model="sTagFilterString"
                            ></b-form-input>
                        </div>
                        <div class="filter-buttons-col">
                            <b-button
                                @click="fnShowRenameTagModal"
                                :disabled="sActiveTag=='__all__'"
                                block
                            >
                                <i class="fa fa-pencil"></i>
                            </b-button>
                        </div>
                        <div class="filter-buttons-col">
                            <b-button 
                                variant="success"
                                @click="fnShowNewTagModal"
                                block
                            >
                                <i class="fa fa-plus"></i>
                            </b-button>
                        </div>
                        <div class="filter-buttons-col">
                            <b-button 
                                variant="danger" 
                                @click="fnRemoveTag()"
                                :disabled="sActiveTag=='__all__'"
                                block
                            >
                                <i class="fa fa-trash"></i>
                            </b-button>
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
                        <div class="filter-input-col">
                            <b-form-input 
                                placeholder="Filter"
                                v-model="sArticleFilterString"
                                @input="fnSearchArticle"
                            ></b-form-input>
                        </div>
                        <div class="filter-buttons-col">
                            <b-button
                                @click="fnShowRenameArticleModal"
                                :disabled="!fnArticleExists()"
                                block
                            >
                                <i class="fa fa-pencil"></i>
                            </b-button>
                        </div>
                        <div class="filter-buttons-col">
                            <b-button 
                                variant="success"
                                @click="fnShowNewArticleModal" 
                                block
                            >
                                <i class="fa fa-plus"></i>
                            </b-button>
                        </div>
                        <div class="filter-buttons-col">
                            <b-button 
                                variant="danger" 
                                @click="fnRemoveArticle()"
                                :disabled="!aArticles.length"
                                block
                            >
                                <i class="fa fa-trash"></i>
                            </b-button>
                        </div>
                    </div>
                </div>
                <b-list-group>
                    <b-list-group-item 
                        v-for="(sItem, iIndex) in aArticles"
                        href="#"
                        :active="iActiveArticle==iIndex"
                        v-if="sArticleFilterString=='' 
                            || (
                                (sArticleFilterString!=''
                                    && sArticleFilterString[0]=='%'
                                    && aArticlesSearchResults.indexOf(sItem)!=-1
                                )
                                || (sArticleFilterString!='' 
                                    && sArticleFilterString[0]!='%' 
                                    && sItem.indexOf(sArticleFilterString)!=-1
                                )
                            )"
                        @click="fnSelectArticle(iIndex)"
                    >
                    {{ sItem }}
                    </b-list-group-item>
                </b-list-group>            
            </div>
            <div class="page-content col-xl-4">
                <div v-show="fnArticleExists()"> 
                    <div class="container-fluid">
                        <div class="buttons-row row flex-xl-nowrap2">
                            <div class="col-xl-10">
                            </div>
                            <div class="col-xl-2">
                                <b-button 
                                    variant="success" 
                                    @click="fnPushRepository(false)"
                                    block
                                >
                                    <b-spinner 
                                        v-if="bShowSaveButtonSpinner"
                                        small 
                                        type="grow"
                                    ></b-spinner>
                                    Save
                                </b-button>
                            </div>
                        </div>
                    </div>
                    
                    <textarea class="page-content-textarea"></textarea>
                    
                    <b-form-file 
                        ref="uploaded_images_input"
                        @change="fnUploadImages"
                        v-show="false"
                        multiple
                        plain
                    ></b-form-file>
                    
                    <b-form-file 
                        ref="uploaded_files_input"
                        @change="fnUploadFiles"
                        v-show="false"
                        multiple
                        plain
                    ></b-form-file>
                    
                    <div class="container-fluid">
                        <div class="filter-row row flex-xl-nowrap2">
                            <div class="tag-filter-input-col">
                                <b-form-input 
                                    placeholder="Filter"
                                    v-model="sCurrentArticleTagFilterString"
                                ></b-form-input>
                            </div>
                            <b-form-checkbox 
                                v-model="bCurrentArticleFilterSelectedTags" 
                                button
                                class="tag-filter-buttons-col"
                                button-variant="info"
                            >
                                <i class="fa fa-check-square"></i>
                            </b-form-checkbox>
                        </div>
                    </div>
                    <div class="current-article-tags-block">
                        <b-list-group>
                            <b-list-group-item 
                                v-for="(aItem, sKey) in oRepository.oTags"
                                v-if="(sCurrentArticleTagFilterString=='' 
                                        || (sCurrentArticleTagFilterString!='' 
                                            && sKey.indexOf(sCurrentArticleTagFilterString)!=-1)
                                      )
                                      && (!bCurrentArticleFilterSelectedTags 
                                            || (bCurrentArticleFilterSelectedTags 
                                                && fnFindArticleInTag(aArticles[iActiveArticle], sKey)!=-1)
                                      )"
                            >
                                <b-form-checkbox
                                    :checked="fnFindArticleInTag(aArticles[iActiveArticle], sKey)==-1 ? false : true"
                                    @change="$event ? fnAddArticleTag(aArticles[iActiveArticle], sKey) : fnRemoveArticleTag(aArticles[iActiveArticle], sKey)"
                                >
                                    {{ sKey }}
                                </b-form-checkbox>
                            </b-list-group-item>
                        </b-list-group>
                    </div> 
                </div>
            </div>
            <div class="article-view col-xl-4">
                <div class="container-fluid">
                    <div class="buttons-row row flex-xl-nowrap2">
                        <div class="col-xl-6">
                        </div>
                        <div class="col-xl-2">
                            <b-button 
                                @click="fnShowImagesModal"
                                block
                            >Images</b-button>
                        </div>
                        <div class="col-xl-2">
                            <b-button 
                                @click="fnShowFilesModal"
                                block
                            >Files</b-button>
                        </div>
                        <div class="col-xl-2">
                            <b-button 
                                variant="success" 
                                @click="fnRefreshArticleViewer"
                                block
                            >Resfresh</b-button>
                        </div>
                    </div>
                </div>
            
                <div
                    v-if="bShowArticleViewContentsSpinner"
                    class="article-view-contents-spinner d-flex justify-content-center align-items-center"
                >
                    <b-spinner 
                        variant="primary"
                        style="width: 100px; height: 100px;" 
                        type="grow"
                    ></b-spinner>
                </div>
                <div 
                    v-if="!bShowArticleViewContentsSpinner && fnArticleExists()"
                    class="article-view-contents markdown-body"
                    v-html="sArticleViewContents"
                >
                </div>
            </div>
        </div>

        <b-modal
            id="add-youtube-video-modal"
            ref="add_youtube_video_modal"
            title="Add youtube video"
            @ok="fnAddYoutubeVideo"
        >
            <form 
                ref="add_youtube_video_modal_form" 
                @submit.stop.prevent="fnAddYoutubeVideo"
            >
                <b-form-group
                    label="URL or hash"
                    label-for="youtube-video-url-input"
                >
                    <b-form-input
                        id="youtube-video-url-input"
                        v-model="sYoutubeVideoURL"
                        ref="add_youtube_video_modal_url_input"
                    ></b-form-input>
                </b-form-group>
                <hr 
                    v-if="sYoutubeVideoURL!=''"
                >
                <b-img
                    v-if="sYoutubeVideoURL!=''"
                    :src="'http://img.youtube.com/vi/' 
                    + (/http:/.test(sYoutubeVideoURL) ? fnGetYoutubeHash(sYoutubeVideoURL) : sYoutubeVideoURL) 
                    + '/0.jpg'"
                ></b-img>
            </form>
        </b-modal>

        <b-modal
            id="add-new-tag-modal"
            ref="add_new_tag_modal"
            title=""
            @ok="fnNewTagFormSubmit"
        >
            <form 
                ref="add_new_tag_modal_form" 
                @submit.stop.prevent="fnNewTagFormSubmit"
            >
                <b-form-group
                    :state="sNewTagFieldState"
                    label="Tag"
                    label-for="tag-input"
                    :invalid-feedback="sNewTagInvalidFeedback"
                >
                    <b-form-input
                        id="tag-input"
                        v-model="sNewTag"
                        :state="sNewTagFieldState"
                        ref="add_new_tag_modal_tag_input"
                        required
                    ></b-form-input>
                </b-form-group>
            </form>
        </b-modal>
        
        <b-modal
            id="add-new-article-modal"
            ref="add_new_article_modal"
            title=""
            @ok="fnNewArticleFormSubmit"
        >
            <form 
                ref="add_new_article_modal_form" 
                @submit.stop.prevent="fnNewArticleFormSubmit"
            >
                <b-form-group
                    :state="sNewArticleFieldState"
                    label="Article"
                    label-for="article-name-input"
                    :invalid-feedback="sNewArticleInvalidFeedback"
                >
                    <b-form-input
                        id="article-name-input"
                        v-model="sNewArticle"
                        :state="sNewArticleFieldState"
                        ref="add_new_article_modal_article_name_input"
                        required
                    ></b-form-input>
                </b-form-group>
            </form>
        </b-modal>
        
        <b-modal
            id="images-modal"
            ref="images_modal"
            title="Images"
            @show="fnResetImagesModal"
            @ok="fnInsertImageFromModal"
        >
            <div class="container-fluid">
                <div class="images-modal-filter-row row flex-xl-nowrap2">
                    <div class="col-xl-10">
                        <b-form-input 
                            placeholder="Filter"
                            v-model="sImagesFilterString"
                        ></b-form-input>
                    </div>
                    <div class="col-xl-1">
                        <b-button 
                            variant="success"
                            @click="fnAddImage"
                            block
                        >
                            <i class="fa fa-plus"></i>
                        </b-button>
                    </div>
                    <div class="col-xl-1">
                        <b-button 
                            variant="danger" 
                            @click="fnRemoveImage"
                            block
                        >
                            <i class="fa fa-trash"></i>
                        </b-button>
                    </div>
                </div>
            </div>
            <div class="images-modal-list d-flex flex-wrap">
                <div 
                    v-for="(sImage, iIndex) in aImagesModalFiles"
                    class="images-modal-list-item img-thumbnail d-flex"
                    v-bind:class="{ active: aImagesModalSelectedFiles.indexOf(sImage)!=-1 }"
                    @click="fnToggleImageSelection(sImage)"
                    v-if="sImagesFilterString=='' 
                            || (sImagesFilterString!='' 
                                && sImage.indexOf(sImagesFilterString)!=-1)"
                >
                    <b-img 
                        :src="'/repositories/'+oRepository.sName+'/images/'+sImage" 
                        :alt="sImage"
                        class="align-self-center"
                        :hint="sImage"
                    ></b-img>
                </div>
            </div>
        </b-modal>
        
        <b-modal
            id="files-modal"
            ref="files_modal"
            title="Files"
            @show="fnResetFilesModal"
            @ok="fnInsertFileFromModal"
        >
            <div class="container-fluid">
                <div class="files-modal-filter-row row flex-xl-nowrap2">
                    <div class="col-xl-10">
                        <b-form-input 
                            placeholder="Filter"
                            v-model="sFilesFilterString"
                        ></b-form-input>
                    </div>
                    <div class="col-xl-1">
                        <b-button 
                            variant="success"
                            @click="fnAddFile"
                            block
                        >
                            <i class="fa fa-plus"></i>
                        </b-button>
                    </div>
                    <div class="col-xl-1">
                        <b-button 
                            variant="danger" 
                            @click="fnRemoveFile"
                            block
                        >
                            <i class="fa fa-trash"></i>
                        </b-button>
                    </div>
                </div>
            </div>
            <div class="files-modal-list">
                <div 
                    v-for="(sFile, iIndex) in aFilesModalFiles"
                    class="files-modal-list-item"
                    v-bind:class="{ active: aFilesModalSelectedFiles.indexOf(sFile)!=-1 }"
                    @click="fnToggleFileSelection(sFile)"
                    v-if="sFilesFilterString=='' 
                            || (sFilesFilterString!='' 
                                && sFile.indexOf(sFilesFilterString)!=-1)"
                >
                    {{ sFile }}
                </div>
            </div>
        </b-modal>
    </div>
</template>

<script>

import SimpleMDE from 'simplemde';

import 'simplemde/dist/simplemde.min.css';

import Vue, { VueConstructor } from 'vue'

export default {
    name: 'RepositoryTabContent',
    
    props: {
        iIndex: {
            type: Number
        },
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
            bShowSaveButtonSpinner: false,
            bShowArticleViewContentsSpinner: false,
            
            oEditor: null,
            oSimpleMDE: null,
            oUploadedFile: null,
            
            sYoutubeVideoURL: '',
            
            aImagesModalFiles: [],
            aImagesModalSelectedFiles: [],
            sImagesFilterString: '',
            sUploadImagesMode: '',
            
            aFilesModalFiles: [],           
            aFilesModalSelectedFiles: [],
            sFilesFilterString: '',
            sUploadFilesMode: '',
            
            aArticlesSearchResults: [],
            
            sNewTag: '',
            sNewTagFieldState: '',
            sNewTagInvalidFeedback: '',
            sNewTagModalMode: '',
            
            sNewArticle: '',
            sNewArticleFieldState: '',
            sNewArticleInvalidFeedback: '',
            sNewArticleModalMode: '',
            
            sArticleViewContents: '',
            
            sActiveTag: "__all__",
            iActiveArticle: -1,
            sTagFilterString: "",
            sArticleFilterString: "",
            sCurrentArticleTagFilterString: "",
            bCurrentArticleFilterSelectedTags: false
        };
    },
    
    computed: {
        aArticles: function() 
        {
            console.log('aArticles', this.oRepository.aArticles, this.oRepository.oTags[this.sActiveTag]);
            if (this.sActiveTag=="__all__") {
                return this.oRepository.aArticles;
            }
            
            if (typeof this.oRepository.oTags[this.sActiveTag] == 'undefined') {
                return [];
            }
            
            return this.oRepository.oTags[this.sActiveTag];
        },
    },
    
    methods: {
        fnPushRepository: function(bPushOnly)
        {
            var oData = {
                action: 'push_repository',
                repository: this.oRepository.sName,
            };
            
            if (!bPushOnly) {
                this.bShowSaveButtonSpinner = true;
                oData['article'] = this.aArticles[this.iActiveArticle];
                oData['tags'] = this.fnFindTagsWithArticle(this.aArticles[this.iActiveArticle]);
                oData['data'] = this.oSimpleMDE.value();
            }
            
            this
                .$http
                .post(
                    '',
                    oData
                ).then(function(oResponse)
                {
                    if (oResponse.body.status=='error') {
                        this.$snotify.error(oResponse.body.message, 'Error');
                        return;
                    }
                    
                    this.$snotify.success("Repository successfully saved");
                    
                    if (!bPushOnly) {
                        this.bShowSaveButtonSpinner = false;
                    }
                    
                    this.fnRefreshArticleViewer();
                });            
        },
        fnCheckNewTagForm: function()
        {
            console.log('fnCheckNewTagForm');
            var bValid = this.$refs.add_new_tag_modal_form.checkValidity()
            
            if (this.oRepository.oTags[this.sNewTag]) {
                this.sNewTagInvalidFeedback = "Tag already exists";
                this.sNewTagFieldState = 'invalid';
                return false;
            }
            
            this.sNewTagInvalidFeedback = "Tag is required";
            
            this.sNewTagFieldState = bValid ? 'valid' : 'invalid'
            
            return bValid
        },
        fnNewTagFormSubmit: function(oEvent)
        {
            console.log('fnNewTagFormSubmit');
            
            oEvent.preventDefault();
            
            if (!this.fnCheckNewTagForm()) {
                return;
            }

            this.$nextTick(function() {
                this.$refs.add_new_tag_modal.hide();
            })
            
            if (this.sNewTagModalMode == 'add') {
                this.fnAddTag();
            }
            if (this.sNewTagModalMode == 'rename') {
                this.fnRenameTag();
            }
        },
        fnShowNewTagModal: function()
        {
            this.sNewTagModalMode = 'add';
            console.log(this.$refs.add_new_tag_modal);
            this.$refs.add_new_tag_modal.show();

            var oThis = this;
            
            setTimeout(function() {
                oThis.$refs.add_new_tag_modal.title = 'Add new tag';
            }, 300);
            
            console.log(this.$refs.add_new_tag_modal);
            this.fnResetNewTagModal();
        },
        fnShowRenameTagModal: function()
        {
            this.sNewTagModalMode = 'rename';
            this.$refs.add_new_tag_modal.show();
            
            var oThis = this;
            
            setTimeout(function() {
                oThis.$refs.add_new_tag_modal.title = 'Rename tag';
            }, 300);
            
            this.fnResetNewTagModal();
            this.sNewTag = this.sActiveTag;
        },
        fnResetNewTagModal: function ()
        {
            console.log('fnResetNewTagModal');
            this.sNewTag = '';
            this.sNewTagFieldState = '';
            
            var oThis = this;
            
            setTimeout(function() {
                oThis.$refs.add_new_tag_modal_tag_input.$el.focus();
            }, 300);
        },
        fnRenameTag: function(fnCallback)
        {
            console.log('fnRenameTag', this.sNewTag);
            
            window.oApplication.bShowLoadingScreen = true;
            
            this
                .$http
                .post(
                    '',
                    {
                        action: 'rename_tag',
                        repository: this.oRepository.sName,
                        articles: this.oRepository.oTags[this.sActiveTag],
                        from_tag: this.sActiveTag,
                        to_tag: this.sNewTag
                    }
                ).then(function(oResponse)
                {
                    window.oApplication.bShowLoadingScreen = false;
                    
                    if (oResponse.body.status=='error') {
                        this.$snotify.error(oResponse.body.message, 'Error');
                        return;
                    }
                    
                    var oTags = {};
                    
                    for (var sTag in this.oRepository.oTags) {
                        if (sTag==this.sActiveTag) {
                            oTags[this.sNewTag] = this.oRepository.oTags[sTag];
                        } else {
                            oTags[sTag] = this.oRepository.oTags[sTag];
                        }
                    }
                    
                    Vue.set(this.oRepository, 'oTags', oTags);
                    
                    //this.fnPushRepository(true);
                    
                    var iActiveArticle = this.iActiveArticle;
                    this.fnSelectTag(this.sNewTag);
                    this.fnSelectArticle(iActiveArticle);
                    
                    if (fnCallback) fnCallback.call(this);
                });            
        },
        fnAddTag: function(fnCallback)
        {
            console.log('fnAddTag', this.sNewTag);
            
            window.oApplication.bShowLoadingScreen = true;
            
            this
                .$http
                .post(
                    '',
                    {
                        action: 'create_tag',
                        repository: this.oRepository.sName,
                        tag: this.sNewTag
                    }
                ).then(function(oResponse)
                {
                    window.oApplication.bShowLoadingScreen = false;
                    
                    if (oResponse.body.status=='error') {
                        this.$snotify.error(oResponse.body.message, 'Error');
                        return;
                    }
                    
                    Vue.set(this.oRepository.oTags, this.sNewTag, []);
                    
                    //this.fnSelectTag(this.sNewTag);
                    
                    if (fnCallback) fnCallback.call(this);
                });
        },
        fnRemoveTag: function(fnCallback)
        {
            if (this.sActiveTag=="__all__") {
                return false;
            }

            if (!confirm("Delete tag '"+this.sActiveTag+"'?")) {
                return;
            }
            
            window.oApplication.bShowLoadingScreen = true;
            
            this
                .$http
                .post(
                    '',
                    {
                        action: 'remove_tag',
                        repository: this.oRepository.sName,
                        tag: this.sActiveTag
                    }
                ).then(function(oResponse)
                {
                    window.oApplication.bShowLoadingScreen = false;
                    
                    if (oResponse.body.status=='error') {
                        this.$snotify.error(oResponse.body.message, 'Error');
                        return;
                    }
                    
                    var sActiveTag = this.sActiveTag;
                    var sArticle = this.oRepository.oTags[sActiveTag][this.iActiveArticle];
                        
                    this.fnSelectTag('__all__');
                    this.fnSelectArticleWithName(sArticle);
                    
                    delete this.oRepository.oTags[sActiveTag];
                    
                    if (fnCallback) fnCallback.call(this);
                });
        },
        
        fnCheckNewArticleForm: function()
        {
            console.log('fnCheckNewArticleForm');
            var bValid = this.$refs.add_new_article_modal_form.checkValidity()
            
            if (this.oRepository.aArticles.indexOf(this.sNewArticle)!=-1) {
                this.sNewArticleInvalidFeedback = "Article already exists";
                this.sNewArticleFieldState = 'invalid';
                return false;
            }

            this.sNewArticleInvalidFeedback = "Article name is required";
            
            this.sNewArticleFieldState = bValid ? 'valid' : 'invalid'
            
            return bValid
        },
        fnNewArticleFormSubmit: function(oEvent)
        {
            console.log('fnNewTagFormSubmit');
            oEvent.preventDefault();
            
            if (!this.fnCheckNewArticleForm()) {
                console.log('fnCheckNewArticleForm', this.fnCheckNewArticleForm());
                return;
            }

            this.$nextTick(function() {
                this.$refs.add_new_article_modal.hide();
            })
            
            if (this.sNewArticleModalMode == 'add') {
                this.fnAddArticle();
            }
            if (this.sNewArticleModalMode == 'rename') {
                this.fnRenameArticle();
            }            
        },
        fnResetNewArticleModal: function ()
        {
            console.log('fnResetNewTagModal');
            this.sNewArticle = '';
            this.sNewArticleFieldState = '';
            
            var oThis = this;
            
            setTimeout(function() {
                oThis.$refs.add_new_article_modal_article_name_input.$el.focus();
            }, 300);
        },   
        fnShowNewArticleModal: function()
        {
            console.log('fnShowNewArticleModal');
            this.sNewArticleModalMode = 'add';
            this.$refs.add_new_article_modal.show();

            var oThis = this;
            
            setTimeout(function() {
                oThis.$refs.add_new_article_modal.title = 'Add new article';
            }, 300);

            this.fnResetNewArticleModal();
        },
        fnShowRenameArticleModal: function()
        {
            console.log('fnShowRenameArticleModal');
            this.sNewArticleModalMode = 'rename';
            this.$refs.add_new_article_modal.show();

            var oThis = this;
            
            setTimeout(function() {
                oThis.$refs.add_new_article_modal.title = 'Rename article';
            }, 300);

            this.fnResetNewArticleModal();
            this.sNewArticle = this.aArticles[this.iActiveArticle];
        },
        fnRenameArticle: function(fnCallback)
        {
            console.log('fnRenameArticle', this.sNewTag);
            
            window.oApplication.bShowLoadingScreen = true;
            
            var sArticle = this.aArticles[this.iActiveArticle];
            
            this
                .$http
                .post(
                    '',
                    {
                        action: 'rename_article',
                        repository: this.oRepository.sName,
                        tags: this.fnFindTagsWithArticle(this.aArticles[this.iActiveArticle]),
                        from_article: sArticle,
                        to_article: this.sNewArticle
                    }
                ).then(function(oResponse)
                {
                    window.oApplication.bShowLoadingScreen = false;
                    
                    if (oResponse.body.status=='error') {
                        this.$snotify.error(oResponse.body.message, 'Error');
                        return;
                    }
                    
                    for (var sTag in this.oRepository.oTags) {
                        var iIndex = this.oRepository.oTags[sTag].indexOf(sArticle);
                        
                        if (iIndex>-1) {
                            this.oRepository.oTags[sTag].splice(iIndex, 1, this.sNewArticle);
                        }
                    }
                    
                    var iIndex = this.oRepository.aArticles.indexOf(sArticle);
                    
                    if (iIndex>-1) {
                        this.oRepository.aArticles.splice(iIndex, 1, this.sNewArticle);
                    }
                    
                    if (fnCallback) fnCallback.call(this);
                    
                    //this.fnPushRepository(true);
                });            
        },        
        fnAddArticle: function(fnCallback)
        {
            console.log('fnAddArticle');
            
            window.oApplication.bShowLoadingScreen = true;
            
            this
                .$http
                .post(
                    '',
                    {
                        action: 'create_article',
                        repository: this.oRepository.sName,
                        tag: this.sActiveTag,
                        article: this.sNewArticle
                    }
                ).then(function(oResponse)
                {
                    window.oApplication.bShowLoadingScreen = false;
                    
                    if (oResponse.body.status=='error') {
                        this.$snotify.error(oResponse.body.message, 'Error');
                        return;
                    }
                    
                    this.oRepository.aArticles.push(this.sNewArticle);
                    
                    if (this.sActiveTag!='__all__') {
                        this.oRepository.oTags[this.sActiveTag].push(this.sNewArticle);
                        this.fnSelectArticle(this.oRepository.oTags[this.sActiveTag].length-1);
                    } else {
                        this.fnSelectArticle(this.oRepository.aArticles.length-1);
                    }
                    
                    if (fnCallback) fnCallback.call(this);
                });
        },
        fnRemoveArticle: function(fnCallback)
        {
            if (!confirm("Delete article '"+this.aArticles[this.iActiveArticle]+"'?")) {
                return;
            }
            
            window.oApplication.bShowLoadingScreen = true;
            
            this
                .$http
                .post(
                    '',
                    {
                        action: 'remove_article',
                        repository: this.oRepository.sName,
                        article: this.aArticles[this.iActiveArticle]
                    }
                ).then(function(oResponse)
                {
                    window.oApplication.bShowLoadingScreen = false;
                    
                    if (oResponse.body.status=='error') {
                        this.$snotify.error(oResponse.body.message, 'Error');
                        return;
                    }
                    
                    var iActiveArticle = this.iActiveArticle;
                    var iNewiActiveArticle = this.iActiveArticle;
                    
                    if (iNewiActiveArticle>0) {
                        iNewiActiveArticle--;
                    }
                    
                    this.fnSelectArticle(iNewiActiveArticle);
                    
                    var sArticle = this.aArticles[iActiveArticle];
                    
                    var iIndex = this.oRepository.aArticles.indexOf(sArticle);
                    this.oRepository.aArticles.splice(iIndex, 1);
                    
                    for (var sTag in this.oRepository.oTags) {
                        var iIndex = this.oRepository.oTags[sTag].indexOf(sArticle);
                        if (iIndex>-1) {
                            this.oRepository.oTags[sTag].splice(iIndex, 1);
                        }
                    }
                    
                    if (fnCallback) fnCallback.call(this);
                });
        },
        fnAddArticleTag(sArticle, sTag)
        {
            window.oApplication.bShowLoadingScreen = true;
            
            this
                .$http
                .post(
                    '',
                    {
                        action: 'add_tag_to_article',
                        repository: this.oRepository.sName,
                        article: sArticle,
                        tag: sTag
                    }
                ).then(function(oResponse)
                {                    
                    window.oApplication.bShowLoadingScreen = false;
                    
                    if (oResponse.body.status=='error') {
                        this.$snotify.error(oResponse.body.message, 'Error');
                        return;
                    }
                    
                    this.oRepository.oTags[sTag].push(sArticle);
                    
                    this.fnSelectArticle(this.iActiveArticle);
                });
        },
        fnRemoveArticleTag(sArticle, sTag)
        {
            window.oApplication.bShowLoadingScreen = true;
            
            this
                .$http
                .post(
                    '',
                    {
                        action: 'remove_tag_from_article',
                        repository: this.oRepository.sName,
                        article: sArticle,
                        tag: sTag
                    }
                ).then(function(oResponse)
                {
                    window.oApplication.bShowLoadingScreen = false;
                    
                    if (oResponse.body.status=='error') {
                        this.$snotify.error(oResponse.body.message, 'Error');
                        return;
                    }
                    
                    var iIndex = this.fnFindArticleInTag(sArticle, sTag);
                    if (this.iActiveArticle == iIndex) {
                        var sArticle = this.oRepository.oTags[sTag][iIndex];
                        
                        this.fnSelectTag('__all__');
                        this.fnSelectArticleWithName(sArticle);
                    }
                    this.oRepository.oTags[sTag].splice(iIndex, 1);
                    
                    this.fnSelectArticle(this.iActiveArticle);                    
                });
        },
        fnFindTagsWithArticle(sArticle)
        {
            var aResult = [];
            
            for (var sTag in this.oRepository.oTags) {
                var iIndex = this.oRepository.oTags[sTag].indexOf(sArticle);
                
                if (iIndex>-1) {
                    aResult.push(sTag);
                }
            }
            
            return aResult;
        },
        fnFindArticleInTags(sArticle)
        {
            var aResult = [];
            
            for (var sTag in this.oRepository.oTags) {
                aResult.push(this.oRepository.oTags[sTag].indexOf(sArticle));
            }
            
            return aResult;
        },
        fnFindArticleInTag(sArticle, sTag)
        {
            console.log('fnFindArticleInTag', sArticle, sTag);
            return this.oRepository.oTags[sTag].indexOf(sArticle);
        },
        fnSelectTag: function(sTagName)
        {
            this.iActiveArticle = -1;
            this.sActiveTag = sTagName;
            localStorage.setItem(this.oRepository.sName+'_sActiveTag', sTagName);
        },
        fnArticleExists: function(iIndex)
        {
            console.log('fnArticleExists', iIndex);
            
            if (typeof iIndex == 'undefined') {
                iIndex = this.iActiveArticle;
            }
            
            return typeof this.aArticles[iIndex] !== 'undefined';
        },
        fnSelectArticleWithName: function(sName)
        {
            this.fnSelectArticle(this.aArticles.indexOf(sName));
        },
        fnSelectArticle: function(iIndex)
        {
            if (typeof this.aArticles[iIndex] == 'undefined') {
                return;
            }
            
            this
                .$http
                .post(
                    '',
                    {
                        action: 'load_article',
                        repository: this.oRepository.sName,
                        article: this.aArticles[iIndex]
                    }
                ).then(function(oResponse)
                {
                    if (oResponse.body.status=='error') {
                        this.$snotify.error(oResponse.body.message, 'Error');
                        return;
                    }
                    
                    this.iActiveArticle = iIndex;
                    localStorage.setItem(this.oRepository.sName+'_iActiveArticle', iIndex);
            
                    var oThis = this;
                    
                    setTimeout(
                        function()
                        {
                            oThis.oSimpleMDE.value(oResponse.body.data);
                        }, 
                        100
                    );
                    
                    this.fnRefreshArticleViewer();
                });
        },
        fnRefreshArticleViewer: function()
        {
            this.bShowArticleViewContentsSpinner = true;
            
            this
                .$http
                .post(
                    '',
                    {
                        action: 'get_article_page',
                        repository: this.oRepository.sName,
                        article: this.aArticles[this.iActiveArticle]
                    }
                ).then(function(oResponse)
                {
                    if (oResponse.body.status=='error') {
                        this.$snotify.error(oResponse.body.message, 'Error');
                        return;
                    }
                    
                    this.bShowArticleViewContentsSpinner = false;
                    
                    this.sArticleViewContents = oResponse.body.data;
                });
        },
        fnSearchArticle: function()
        {
            if (this.sArticleFilterString[0]!='%') {
                return;
            }
            
            this.aArticlesSearchResults = [];
            
            this
                .$http
                .post(
                    '',
                    {
                        action: 'search_article',
                        repository: this.oRepository.sName,
                        tag: this.sActiveTag,
                        search_text: this.sArticleFilterString.replace(/^%/, '')
                    }
                ).then(function(oResponse)
                {
                    if (oResponse.body.status=='error') {
                        this.$snotify.error(oResponse.body.message, 'Error');
                        return;
                    }
                    
                    this.aArticlesSearchResults = oResponse.body.data;
                });
        },
        
        fnInsertImage: function(sURL, bCursorToEnd)
        {
            var cm = this.oEditor.codemirror;
            var stat = this.fnGetState(cm);
            var options = this.oEditor.options;
            var url = sURL;
            
            this.fnReplaceSelection(cm, stat.image, options.insertTexts.image, url, bCursorToEnd);
        },
        fnUploadImages: function()
        {
            var oFiles = this.$refs.uploaded_images_input.$el.files;

            if (!oFiles.length) {
                return;
            }

            window.oApplication.bShowLoadingScreen = true;
            
            var oFormData = new FormData();

            oFormData.append('action', 'upload_images');
            oFormData.append('repository', this.oRepository.sName);
            
            for (var iIndex=0; iIndex<oFiles.length; iIndex++) {
                oFormData.append('files[]', oFiles[iIndex]);
            }
                        
            this
                .$http
                .post(
                    '',
                    oFormData
                ).then(function(oResponse)
                {                    
                    window.oApplication.bShowLoadingScreen = false;
                    
                    if (oResponse.body.status=='error') {
                        this.$snotify.error(oResponse.body.message, 'Error');
                        this.fnUpdateImagesList();
                        return;
                    }
                    
                    if (this.sUploadImagesMode=='update-modal') {
                        for (var iIndex=0; iIndex<oResponse.body.data.length; iIndex++) {
                            var sImage = oResponse.body.data[iIndex];
                            var iIndex = this.aImagesModalFiles.indexOf(sImage);
                            
                            if (iIndex>-1) {
                                this.aImagesModalFiles.splice(iIndex, 1, sImage);
                            } else {
                                this.aImagesModalFiles.push(sImage);
                            }
                        }
                    } else if (this.sUploadImagesMode=='insert') {
                        for (var iIndex=0; iIndex<oResponse.body.data.length; iIndex++) {
                            this.fnInsertImage('/images/'+oResponse.body.data[iIndex]);
                        }
                    }
                });
        },
        fnShowImagesModal: function()
        {
            this.sUploadImagesMode = 'update-modal';
            this.$refs.images_modal.hideFooter = true;
            this.$refs.images_modal.show();
        },
        fnUpdateImagesList: function()
        {
            this
                .$http
                .post(
                    '',
                    {
                        action: 'get_images',
                        repository: this.oRepository.sName
                    }
                ).then(function(oResponse)
                {
                    if (oResponse.body.status=='error') {
                        this.$snotify.error(oResponse.body.message, 'Error');
                        return;
                    }
                    
                    this.aImagesModalFiles = oResponse.body.data;
                });
        },
        fnResetImagesModal: function()
        {
            this.aImagesModalFiles = [];
            this.aImagesModalSelectedFiles = [];
            this.fnUpdateImagesList();
        },
        fnToggleImageSelection: function(sImage)
        {
            var iIndex = this.aImagesModalSelectedFiles.indexOf(sImage);
            
            if (iIndex==-1) {
                this.aImagesModalSelectedFiles.push(sImage);
            } else {
                this.aImagesModalSelectedFiles.splice(iIndex, 1);
            }
        },
        fnInsertImageFromModal: function()
        {
            for (var iIndex=0; iIndex<this.aImagesModalSelectedFiles.length; iIndex++) {
                this.fnInsertImage('/images/'+this.aImagesModalSelectedFiles[iIndex], true);
            }
            this.$refs.images_modal.hide();
        },
        fnAddImage: function()
        {
            this.$refs.uploaded_images_input.$el.click();
        },
        fnRemoveImage: function()
        {
            window.oApplication.bShowLoadingScreen = true;
            
            this
                .$http
                .post(
                    '',
                    {
                        action: 'remove_images',
                        repository: this.oRepository.sName,
                        files: this.aImagesModalSelectedFiles
                    }
                ).then(function(oResponse)
                {
                    window.oApplication.bShowLoadingScreen = false;
                    
                    if (oResponse.body.status=='error') {
                        this.$snotify.error(oResponse.body.message, 'Error');
                        return;
                    }
                    
                    for (var iIndex=0; iIndex<this.aImagesModalSelectedFiles.length; iIndex++) {
                        var iImageIndex = this.aImagesModalFiles.indexOf(this.aImagesModalSelectedFiles[iIndex]);
                        if (iImageIndex>-1) {
                            this.aImagesModalFiles.splice(iImageIndex, 1);
                        }
                    }
                    
                    this.aImagesModalSelectedFiles = [];
                });
        },
        
        fnInsertFile: function(sURL, bCursorToEnd)
        {
            var cm = this.oEditor.codemirror;
            var stat = this.fnGetState(cm);
            var options = this.oEditor.options;
            var url = sURL;
            
            this.fnReplaceSelection(cm, stat.link, options.insertTexts.link, url, bCursorToEnd);
        },
        fnUploadFiles: function()
        {
            var oFiles = this.$refs.uploaded_files_input.$el.files;

            if (!oFiles.length) {
                return;
            }
            
            window.oApplication.bShowLoadingScreen = true;

            var oFormData = new FormData();

            oFormData.append('action', 'upload_files');
            oFormData.append('repository', this.oRepository.sName);
            
            for (var iIndex=0; iIndex<oFiles.length; iIndex++) {
                oFormData.append('files[]', oFiles[iIndex]);
            }
                        
            this
                .$http
                .post(
                    '',
                    oFormData
                ).then(function(oResponse)
                {
                    window.oApplication.bShowLoadingScreen = false;
                    
                    if (oResponse.body.status=='error') {
                        this.$snotify.error(oResponse.body.message, 'Error');
                        this.fnUpdateFilesList();
                        return;
                    }
                    
                    if (this.sUploadFilesMode=='update-modal') {
                        for (var iIndex=0; iIndex<oResponse.body.data.length; iIndex++) {
                            var sFile = oResponse.body.data[iIndex];
                            var iIndex = this.aFilesModalFiles.indexOf(sFile);
                            
                            if (iIndex>-1) {
                                
                            } else {
                                this.aFilesModalFiles.push(sFile);
                            }
                        }
                    } else if (this.sUploadFilesMode=='insert') {
                        for (var iIndex=0; iIndex<oResponse.body.data.length; iIndex++) {
                            this.fnInsertFile('/files/'+oResponse.body.data[iIndex]);
                        }
                    }
                });
        },
        fnShowFilesModal: function()
        {
            this.sUploadFilesMode = 'update-modal';
            this.$refs.files_modal.hideFooter = true;
            this.$refs.files_modal.show();
        },
        fnUpdateFilesList: function()
        {
            this
                .$http
                .post(
                    '',
                    {
                        action: 'get_files',
                        repository: this.oRepository.sName
                    }
                ).then(function(oResponse)
                {
                    if (oResponse.body.status=='error') {
                        this.$snotify.error(oResponse.body.message, 'Error');
                        return;
                    }
                    
                    this.aFilesModalFiles = oResponse.body.data;
                });
        },
        fnResetFilesModal: function()
        {
            this.aFilesModalFiles = [];
            this.aFilesModalSelectedFiles = [];
            this.fnUpdateFilesList();
        },
        fnToggleFileSelection: function(sFile)
        {
            var iIndex = this.aFilesModalSelectedFiles.indexOf(sFile);
            
            if (iIndex==-1) {
                this.aFilesModalSelectedFiles.push(sFile);
            } else {
                this.aFilesModalSelectedFiles.splice(iIndex, 1);
            }
        },
        fnInsertFileFromModal: function()
        {
            for (var iIndex=0; iIndex<this.aFilesModalSelectedFiles.length; iIndex++) {
                this.fnInsertFile('/files/'+this.aFilesModalSelectedFiles[iIndex], true);
            }
            this.$refs.files_modal.hide();
        },
        fnAddFile: function()
        {
            this.$refs.uploaded_files_input.$el.click();
        },
        fnRemoveFile: function()
        {
            window.oApplication.bShowLoadingScreen = true;
            
            this
                .$http
                .post(
                    '',
                    {
                        action: 'remove_files',
                        repository: this.oRepository.sName,
                        files: this.aFilesModalSelectedFiles
                    }
                ).then(function(oResponse)
                {
                    window.oApplication.bShowLoadingScreen = false;
                    
                    if (oResponse.body.status=='error') {
                        this.$snotify.error(oResponse.body.message, 'Error');
                        return;
                    }
                    
                    for (var iIndex=0; iIndex<this.aFilesModalSelectedFiles.length; iIndex++) {
                        var iFileIndex = this.aFilesModalFiles.indexOf(this.aFilesModalSelectedFiles[iIndex]);
                        if (iFileIndex>-1) {
                            this.aFilesModalFiles.splice(iFileIndex, 1);
                        }
                    }
                    
                    this.aFilesModalSelectedFiles = [];
                });
        },
        
        fnAddYoutubeVideo: function()
        {
            var sHash = /http:/.test(this.sYoutubeVideoURL) ? this.fnGetYoutubeHash(this.sYoutubeVideoURL) : this.sYoutubeVideoURL;
            
            this.fnInsertFile("http://www.youtube.com/watch?v="+sHash);
            this.fnInsertImage("http://img.youtube.com/vi/"+sHash+"/0.jpg");
        },
        fnGetYoutubeHash: function(sURL)
        {
            var aResult = sURL.match(/http:\/\/www\.youtube\.com\/watch\?v=(\w+)/);
            
            return aResult[1];
        },
        
        fnGetState: function (cm, pos) 
        {
            pos = pos || cm.getCursor("start");
            var stat = cm.getTokenAt(pos);
            if(!stat.type) return {};

            var types = stat.type.split(" ");

            var ret = {},
                data, text;
            for(var i = 0; i < types.length; i++) {
                data = types[i];
                if(data === "strong") {
                    ret.bold = true;
                } else if(data === "variable-2") {
                    text = cm.getLine(pos.line);
                    if(/^\s*\d+\.\s/.test(text)) {
                        ret["ordered-list"] = true;
                    } else {
                        ret["unordered-list"] = true;
                    }
                } else if(data === "atom") {
                    ret.quote = true;
                } else if(data === "em") {
                    ret.italic = true;
                } else if(data === "quote") {
                    ret.quote = true;
                } else if(data === "strikethrough") {
                    ret.strikethrough = true;
                } else if(data === "comment") {
                    ret.code = true;
                } else if(data === "link") {
                    ret.link = true;
                } else if(data === "tag") {
                    ret.image = true;
                } else if(data.match(/^header(\-[1-6])?$/)) {
                    ret[data.replace("header", "heading")] = true;
                }
            }
            return ret;
        },
        fnReplaceSelection: function (cm, active, startEnd, url, bCursorToEnd) 
        {
            if(/editor-preview-active/.test(cm.getWrapperElement().lastChild.className))
                return;

            var text;
            var start = startEnd[0];
            var end = startEnd[1];
            var startPoint = cm.getCursor("start");
            var endPoint = cm.getCursor("end");
            if(url) {
                end = end.replace("#url#", url);
            }
            if(active) {
                text = cm.getLine(startPoint.line);
                start = text.slice(0, startPoint.ch);
                end = text.slice(startPoint.ch);
                cm.replaceRange(start + end, {
                    line: startPoint.line,
                    ch: 0
                });
            } else {
                text = cm.getSelection();
                cm.replaceSelection(start + text + end);

                startPoint.ch += start.length;
                if(startPoint !== endPoint) {
                    endPoint.ch += start.length;
                }
            }
            cm.setSelection(startPoint, endPoint);
            
            if (bCursorToEnd) {
                var oCursorPosition = cm.getCursor(); 
                oCursorPosition.ch += url.length+end.length;
                cm.setCursor(oCursorPosition);
                cm.focus();
            }
        }
    },
    
    mounted: function()
    {
        console.log('tab mounted', this.oRepository, this.bActive);
        
        var oThis = this;
        
        this.oSimpleMDE = new SimpleMDE({ 
            autoDownloadFontAwesome: false,
            element: this.$el.querySelector('.page-content-textarea'),
            toolbar: [
                "bold",
                "italic",
                "strikethrough",
                "heading",
                "heading-smaller",
                "heading-bigger",
                "heading-1",
                "heading-2",
                "heading-3",
                "code",
                "quote",
                "unordered-list",
                "ordered-list",
                "clean-block",
                "link",
                "image",
                "table",
                "horizontal-rule",
                "preview",
                "side-by-side",
                "fullscreen",
                {
                    name: "insert-picture",
                    action: function customFunction(oEditor)
                    {
                        oThis.oEditor = oEditor;
                        oThis.sUploadImagesMode = 'insert';
                        oThis.$refs.uploaded_images_input.$el.click();
                    },
                    className: "fa fa-file-image-o",
                    title: "Insert local picture",
                },
                {
                    name: "insert-picture-from-collection",
                    action: function(oEditor)
                    {
                        oThis.oEditor = oEditor;
                        oThis.sUploadImagesMode = 'update-modal';
                        oThis.$refs.images_modal.hideFooter = false;
                        oThis.$refs.images_modal.show();
                    },
                    className: "fa fa-picture-o",
                    title: "Insert local picture",
                },
                {
                    name: "insert-files-from-collection",
                    action: function(oEditor)
                    {
                        oThis.oEditor = oEditor;
                        oThis.sUploadFilesMode = 'update-modal';
                        oThis.$refs.files_modal.hideFooter = false;
                        oThis.$refs.files_modal.show();
                    },
                    className: "fa fa-file-o",
                    title: "Insert file from collection",
                },
                {
                    name: "insert-youtube-video",
                    action: function(oEditor)
                    {
                        oThis.oEditor = oEditor;
                        
                        oThis.sYoutubeVideoURL = '';
                        oThis.$refs.add_youtube_video_modal.hideFooter = false;
                        oThis.$refs.add_youtube_video_modal.show();
                    },
                    className: "fa fa-youtube-play",
                    title: "Insert youtube video",
                },
            ]
        });
        
        oThis.fnSelectTag(localStorage.getItem(this.oRepository.sName+'_sActiveTag'));
        oThis.fnSelectArticle(localStorage.getItem(this.oRepository.sName+'_iActiveArticle'));
    },
    
    created: function()
    {
        console.log('repositories tab created');
    }
};

</script>
