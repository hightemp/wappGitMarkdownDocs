
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
                                <b-button variant="success" block>Push</b-button>
                            </div>
                        </div>
                    </div>
                    
                    <textarea class="page-content-textarea"></textarea>
                </div>
            </div>
            <div class="page-preview col-xl-4">
                <div class="container-fluid">
                    <div class="buttons-row row flex-xl-nowrap2">
                        <div class="col-xl-10">
                        </div>
                        <div class="col-xl-2">
                            <b-button variant="success" block>Resfresh</b-button>
                        </div>
                    </div>
                </div>
            
                <div>
                </div>
            </div>
        </div> 
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
            sActiveTag: "__all__",
            iActiveArticle: -1,
            sTagFilterString: "",
            sArticleFilterString: "",
            oSimpleMDE: null
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
            this.iActiveArticle = -1;
            this.sActiveTag = sTagName;
        },
        fnSelectArticle: function(iIndex)
        {
            this.iActiveArticle = iIndex;
            
            console.log(this.iActiveArticle, this.aArticles[this.iActiveArticle]);
            
            var oThis = this;
            
            setTimeout(
                function()
                {
                    oThis.oSimpleMDE.value(oThis.aArticles[oThis.iActiveArticle]);
                }, 
                100
            );
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
                        var cm = oEditor.codemirror;
                        var stat = oThis.fnGetState(cm);
                        var options = oEditor.options;
                        var url = "test";
                        
                        oThis.fnReplaceSelection(cm, stat.image, options.insertTexts.image, url);
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
