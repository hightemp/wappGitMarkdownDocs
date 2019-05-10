
<template>
    <div class="container-fluid">
        <div class="row flex-xl-nowrap2">
            <div class="tags-sidebar col-xl-2">
                <div class="container-fluid">
                    <div class="filter-row row flex-xl-nowrap2">
                        <div class="col-xl-8">
                            <b-form-input 
                                placeholder="Filter"
                                v-model="sTagFilterString"
                            ></b-form-input>
                        </div>
                        <div class="col-xl-2">
                            <b-button 
                                variant="success"
                                v-b-modal.add-new-tag-modal
                                block
                            >
                                <i class="fa fa-plus"></i>
                            </b-button>
                        </div>
                        <div class="col-xl-2">
                            <b-button 
                                variant="danger" 
                                @click="fnRemoveTag"
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
                        <div class="col-xl-8">
                            <b-form-input 
                                placeholder="Filter"
                                v-model="sArticleFilterString"
                            ></b-form-input>
                        </div>
                        <div class="col-xl-2">
                            <b-button 
                                variant="success"
                                v-b-modal.add-new-article-modal 
                                block
                            >
                                <i class="fa fa-plus"></i>
                            </b-button>
                        </div>
                        <div class="col-xl-2">
                            <b-button 
                                variant="danger" 
                                @click="fnRemoveArticle"
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
                            || (sArticleFilterString!='' 
                                && sItem.indexOf(sArticleFilterString)!=-1)"
                        @click="fnSelectArticle(iIndex)"
                    >
                    {{ sItem }}
                    </b-list-group-item>
                </b-list-group>            
            </div>
            <div class="page-content col-xl-4">
                <div v-show="iActiveArticle!=-1"> 
                    <div class="container-fluid">
                        <div class="buttons-row row flex-xl-nowrap2">
                            <div class="col-xl-10">
                            </div>
                            <div class="col-xl-2">
                                <b-button 
                                    variant="success" 
                                    @click="fnPushRepository"
                                    block
                                >Save</b-button>
                            </div>
                        </div>
                    </div>
                    
                    <textarea class="page-content-textarea"></textarea>
                    
                    <b-form-file 
                        ref="uploaded_file_input"
                        v-model="oUploadedFile" 
                        @change="fnUploadFile"
                        plain
                    ></b-form-file>
                    
                    <div class="container-fluid">
                        <div class="filter-row row flex-xl-nowrap2">
                            <div class="col-xl-11">
                                <b-form-input 
                                    placeholder="Filter"
                                    v-model="sCurrentArticleTagFilterString"
                                ></b-form-input>
                            </div>
                            <b-form-checkbox 
                                v-model="bCurrentArticleFilterSelectedTags" 
                                button
                                class="col-xl-1"
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
                        <div class="col-xl-10">
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
                    class="article-view-contents"
                    v-html="sArticleViewContents"
                >
                </div>
            </div>
        </div>
        
        <b-modal
            id="add-new-tag-modal"
            ref="add_new_tag_modal"
            title="Add new tag"
            @show="fnResetNewTagModal"
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
            title="Add new article"
            @show="fnResetNewArticleModal"
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
    </div>
</template>

<script>

import SimpleMDE from 'simplemde';

import 'simplemde/dist/simplemde.min.css';

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
            oEditor: null,
            oSimpleMDE: null,
            oUploadedFile: null,
            
            sNewTag: '',
            sNewTagFieldState: '',
            sNewTagInvalidFeedback: '',
            sNewArticle: '',
            sNewArticleFieldState: '',
            sNewArticleInvalidFeedback: '',
            
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
            if (this.sActiveTag=="__all__") {
                return this.oRepository.aArticles;
            }
            
            return this.oRepository.oTags[this.sActiveTag];
        },
    },
    
    methods: {
        fnPushRepository: function()
        {            
            this
                .$http
                .post(
                    '',
                    {
                        action: 'push_repository',
                        repository: this.oRepository.sName,
                        article: this.aArticles[this.iActiveArticle],
                        data: this.oSimpleMDE.value()
                    }
                ).then(function(oResponse)
                {
                    if (oResponse.body.status=='error') {
                        this.$snotify.error(oResponse.body.message, 'Error');
                        return;
                    }
                    
                    this.$snotify.success("Repository successfully saved");
                    
                    this.fnRefreshArticleViewer();
                });            
        },
        fnCheckNewTagForm: function()
        {
            console.log('fnCheckNewTagForm');
            var bValid = this.$refs.add_new_tag_modal_form.checkValidity()
            
            if (this.oRepository.oTags[this.sNewTag]) {
                this.sNewTagInvalidFeedback = "Tag already exists";
                console.log(this.sNewTagInvalidFeedback);
                return false;
            }
            
            this.sNewTagInvalidFeedback = "Tag is required";
            console.log(this.sNewTagInvalidFeedback);
            
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
            
            this.fnAddTag();
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
        fnAddTag: function()
        {
            console.log('fnAddNewTag', this.sNewTag);
            
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
                    if (oResponse.body.status=='error') {
                        this.$snotify.error(oResponse.body.message, 'Error');
                        return;
                    }
                    
                    Vue.set(this.oRepository.oTags, this.sNewTag, []);
                    
                    //this.fnSelectTag(this.sNewTag);
                });
        },
        fnRemoveTag: function()
        {
            if (this.sActiveTag=="__all__") {
                return false;
            }

            if (!confirm("Delete tag '"+this.sActiveTag+"'?")) {
                return;
            }
            
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
                    if (oResponse.body.status=='error') {
                        this.$snotify.error(oResponse.body.message, 'Error');
                        return;
                    }
                    
                    var sActiveTag = this.sActiveTag;
                    var sArticle = this.oRepository.oTags[sActiveTag][this.iActiveArticle];
                        
                    this.fnSelectTag('__all__');
                    this.fnSelectArticleWithName(sArticle);
                    
                    delete this.oRepository.oTags[sActiveTag];
                });
        },
        
        fnCheckNewArticleForm: function()
        {
            console.log('fnCheckNewArticleForm');
            var bValid = this.$refs.add_new_article_modal_form.checkValidity()
            
            if (this.oRepository.aArticles.indexOf(this.sNewArticle)!=-1) {
                this.sNewArticleInvalidFeedback = "Article already exists";
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
                return;
            }

            this.$nextTick(function() {
                this.$refs.add_new_article_modal.hide();
            })
            
            this.fnAddArticle();
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
        fnAddArticle: function()
        {
            console.log('fnAddArticle');
            
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
                });
        },
        fnRemoveArticle: function()
        {
            if (!confirm("Delete article '"+this.aArticles[this.iActiveArticle]+"'?")) {
                return;
            }
                
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
                    if (oResponse.body.status=='error') {
                        this.$snotify.error(oResponse.body.message, 'Error');
                        return;
                    }
                    
                    var iActiveArticle = this.iActiveArticle;
                    
                    if (this.iActiveArticle>0) {
                        this.iActiveArticle--;
                    }
                    
                    var sArticle = this.aArticles[iActiveArticle];
                    
                    var iIndex = this.oRepository.aArticles.indexOf(sArticle);
                    this.oRepository.aArticles.splice(iIndex, 1);
                    
                    for (var sTag in this.oRepository.oTags) {
                        var iIndex = this.oRepository.oTags[sTag].indexOf(sArticle);
                        if (iIndex>-1) {
                            this.oRepository.oTags[sTag].splice(iIndex, 1);
                        }
                    }
                });
        },
        fnAddArticleTag(sArticle, sTag)
        {
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
        fnFindArticleInTag(sArticle, sTag)
        {
            console.log('fnFindArticleInTag', sArticle, sTag);
            return this.oRepository.oTags[sTag].indexOf(sArticle);
        },
        fnSelectTag: function(sTagName)
        {
            this.iActiveArticle = -1;
            this.sActiveTag = sTagName;
        },
        fnSelectArticleWithName: function(sName)
        {
            this.fnSelectArticle(this.aArticles.indexOf(sName));
        },
        fnSelectArticle: function(iIndex)
        {
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
        fnRefreshArticleViewer()
        {
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
                    
                    this.sArticleViewContents = oResponse.body.data;
                });
        },
        fnUploadFile: function()
        {
            var oFormData = new FormData();

            oFormData.append('action', 'upload_file');
            oFormData.append('repository', this.oRepository.sName);
            //oFormData.append('file', this.oUploadedFile);
            oFormData.append('file', this.$refs.uploaded_file_input.$el.files[0]);
            
            this
                .$http
                .post(
                    '',
                    oFormData
                ).then(function(oResponse)
                {
                    if (oResponse.body.status=='error') {
                        this.$snotify.error(oResponse.body.message, 'Error');
                        return;
                    }
                    
                    var cm = this.oEditor.codemirror;
                    var stat = this.fnGetState(cm);
                    var options = this.oEditor.options;
                    var url = oResponse.body.data;
                    
                    this.fnReplaceSelection(cm, stat.image, options.insertTexts.image, url);
                });
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
        fnReplaceSelection: function (cm, active, startEnd, url) 
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
            cm.focus();
        }
    },
    
    mounted: function()
    {
        console.log('mounted', this.oRepository, this.bActive);
        
        var oThis = this;
        
        this.oSimpleMDE = new SimpleMDE({ 
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
                        oThis.$refs.uploaded_file_input.$el.click();
                    },
                    className: "fa fa-picture-o",
                    title: "Insert local picture",
                },
                
            ]
        });
    },
    
    created: function()
    {
        console.log('repositories tab created');
    }
};

</script>
