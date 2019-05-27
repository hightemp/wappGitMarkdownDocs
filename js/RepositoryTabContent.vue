
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
                <b-list-group class="tags-list-group">
                    <b-list-group-item
                        href="#"
                        class="d-flex justify-content-between align-items-center"
                        :active="sActiveTag=='__all__'"
                        @click="fnSelectTag('__all__')"
                    >
                        All
                        <b-badge 
                            :variant="sActiveTag=='__all__' ? 'light' : 'primary'" 
                            pill
                        >{{ fnGetArticlesCountByTagName('__all__') }}</b-badge>
                    </b-list-group-item>
                    <b-list-group-item 
                        v-for="(aItem, sKey) in oRepository.oTags"
                        href="#"
                        class="d-flex justify-content-between align-items-center"
                        :active="sActiveTag==sKey"
                        v-if="sTagFilterString=='' 
                            || sTagFilterString!='' 
                            && sKey.indexOf(sTagFilterString)!=-1"
                        @click="fnSelectTag(sKey)"
                    >
                        {{ sKey }}
                        <b-badge 
                            :variant="sActiveTag==sKey ? 'light' : 'primary'" 
                            pill
                        >{{ fnGetArticlesCountByTagName(sKey) }}</b-badge>
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
                <b-list-group class="articles-list-group">
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
            <div 
                class="page-content col-xl-4" 
                :class="{
                    'replacement-open': bShowReplacementBlock,
                    'translation-open': bShowTranslationBlock
                }"
            >
                <div v-show="fnArticleExists()"> 
                    <div class="container-fluid">
                        <div class="buttons-row row flex-xl-nowrap2">
                            <div class="page-content-space">
                            </div>
                            <div class="page-content-button">
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
                                    <i
                                        v-if="!bShowSaveButtonSpinner"
                                        class="fa fa-save"
                                    ></i>
                                </b-button>
                            </div>
                        </div>
                    </div>
                    
                    <textarea class="page-content-textarea"></textarea>
                    
                    <div 
                        v-if="bShowTranslationBlock"
                        class="translation-block d-flex flex-wrap"
                    >
                        <b-form-select 
                            class="translation-block-select"
                            :options="aTranslationProviders"
                            v-model="sTranslationProvider"
                            ref="translation_provider"
                            @change="fnTranslationProviderChange"
                        ></b-form-select>
                        <b-form-select 
                            class="translation-block-select"
                            :options="aTranslationFromLanguage"
                            v-model="sTranslationFromLanguage"
                            ref="translation_from_language"
                            @change="fnTranslationFromLanguageChange"
                        ></b-form-select>
                        <b-form-select 
                            class="translation-block-select"
                            :options="aTranslationToLanguage"
                            v-model="sTranslationToLanguage"
                            ref="translation_to_language"
                            @change="fnTranslationToLanguageChange"
                        ></b-form-select> 
                        <b-button 
                            class="translation-block-button"
                            @click="fnTranslate"
                        >
                            Translate
                        </b-button>
                    </div>
                    
                    <div 
                        v-if="bShowReplacementBlock"
                        class="replacement-block d-flex flex-wrap"
                    >
                        <b-form-input 
                            class="replacable-text-input"
                            placeholder=""
                            v-model="sSearchQuery"
                            ref="replacable_text_input"
                        ></b-form-input>
                        <b-button 
                            class="replacement-block-buttons-col"
                            @click="fnFindPrevious"
                        >
                            Previous
                        </b-button>
                        <b-button
                            class="replacement-block-buttons-col"
                            @click="fnFindNext"
                        >
                            Next
                        </b-button>
                        <b-form-checkbox 
                            v-model="bUseRegularExpression"
                            button
                            class="replacement-block-toggle-buttons-col"
                            button-variant="info"
                        >
                            <i class="fa fa-registered"></i>
                        </b-form-checkbox>
                        <b-form-checkbox 
                            v-model="bUseCaseSensetive"
                            button
                            class="replacement-block-toggle-buttons-col"
                            button-variant="info"
                        >
                            <i class="fa fa-font"></i>
                        </b-form-checkbox>
                        <b-form-input 
                            class="replacement-text-input"
                            placeholder=""
                            v-model="sSearchQueryText"
                        ></b-form-input>
                        <b-button 
                            class="replacement-block-buttons-col"
                            @click="fnReplace"
                        >
                            Replace
                        </b-button>
                        <b-button
                            class="replacement-block-buttons-col replace-all-button"
                            @click="fnReplaceAll"
                        >
                            Replace All
                        </b-button>
                        <b-button 
                            class="replacement-block-toggle-buttons-col"
                            button-variant="secondary"
                            @click="fnShowRegExpHelp"
                        >
                            <i class="fa fa-question"></i>
                        </b-button>
                    </div>
                    
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
                        <div class="article-view-space">
                        </div>
                        <div class="article-view-button">
                            <b-button 
                                @click="fnShowArticleGithubPage"
                                block
                            ><i class="fa fa-link"></i></b-button>
                        </div>
                        <div class="article-view-button">
                            <b-button 
                                @click="fnShowImagesModal"
                                block
                            ><i class="fa fa-picture-o"></i></b-button>
                        </div>
                        <div class="article-view-button">
                            <b-button 
                                @click="fnShowFilesModal"
                                block
                            ><i class="fa fa-file-o"></i></b-button>
                        </div>
                        <div class="article-view-button">
                            <b-button 
                                variant="success" 
                                @click="fnRefreshArticleViewer"
                                block
                            ><i class="fa fa-refresh"></i></b-button>
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
                    ref="article_view_contents"
                    @scroll="fnArticleViewScroll"
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
                    <div class="col-xl-9">
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
                            variant="success"
                            @click="fnAddImageFromLink"
                            block
                        >
                            <i class="fa fa-link"></i>
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
            <div 
                class="images-modal-list d-flex flex-wrap"
                ref="images_modal_list"
                @scroll="fnImagesModalScroll"
            >
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
                    <div class="col-xl-9">
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
                            variant="success"
                            @click="fnAddFileFromLink"
                            block
                        >
                            <i class="fa fa-link"></i>
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
            <div 
                class="files-modal-list"
                ref="files_modal_list"
                @scroll="fnFilesModalScroll"
            >
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
        
        <b-modal
            id="help-modal"
            ref="help_modal"
            title="Help"
            :hide-footer="true"
            size="xl"
        >
            <b-tabs pills vertical>
                <b-tab title="Regular expressions" active>
                    <p>
                        To match a specific Unicode code point, use \uFFFF where 
                        FFFF is the hexadecimal number of the code point you want 
                        to match. You must always specify 4 hexadecimal digits E.g. 
                        \u00E0 matches à, but only when encoded as a single 
                        code point U+00E0.
                    </p>
                    <h3>Unicode Categories</h3>
                    <ul> <li><tt class="code"><span class="regexspecial">\p{L}</span></tt> or <tt class="code"><span class="regexspecial">\p{Letter}</span></tt>: any kind of letter from any language. <ul> <li><tt class="code"><span class="regexspecial">\p{Ll}</span></tt> or <tt class="code"><span class="regexspecial">\p{Lowercase_Letter}</span></tt>: a lowercase letter that has an uppercase variant. </li><li><tt class="code"><span class="regexspecial">\p{Lu}</span></tt> or <tt class="code"><span class="regexspecial">\p{Uppercase_Letter}</span></tt>: an uppercase letter that has a lowercase variant. </li><li><tt class="code"><span class="regexspecial">\p{Lt}</span></tt> or <tt class="code"><span class="regexspecial">\p{Titlecase_Letter}</span></tt>: a letter that appears at the start of a word when only the first letter of the word is capitalized. </li><li><tt class="code"><span class="regexspecial">\p{L&amp;}</span></tt> or <tt class="code"><span class="regexspecial">\p{Cased_Letter}</span></tt>: a letter that exists in lowercase and uppercase variants (combination of Ll, Lu and Lt). </li><li><tt class="code"><span class="regexspecial">\p{Lm}</span></tt> or <tt class="code"><span class="regexspecial">\p{Modifier_Letter}</span></tt>: a special character that is used like a letter. </li><li><tt class="code"><span class="regexspecial">\p{Lo}</span></tt> or <tt class="code"><span class="regexspecial">\p{Other_Letter}</span></tt>: a letter or ideograph that does not have lowercase and uppercase variants. </li></ul> </li><li><tt class="code"><span class="regexspecial">\p{M}</span></tt> or <tt class="code"><span class="regexspecial">\p{Mark}</span></tt>: a character intended to be combined with another character (e.g. accents, umlauts, enclosing boxes, etc.). <ul> <li><tt class="code"><span class="regexspecial">\p{Mn}</span></tt> or <tt class="code"><span class="regexspecial">\p{Non_Spacing_Mark}</span></tt>: a character intended to be combined with another character without taking up extra space (e.g. accents, umlauts, etc.). </li><li><tt class="code"><span class="regexspecial">\p{Mc}</span></tt> or <tt class="code"><span class="regexspecial">\p{Spacing_Combining_Mark}</span></tt>: a character intended to be combined with another character that takes up extra space (vowel signs in many Eastern languages). </li><li><tt class="code"><span class="regexspecial">\p{Me}</span></tt> or <tt class="code"><span class="regexspecial">\p{Enclosing_Mark}</span></tt>: a character that encloses the character is is combined with (circle, square, keycap, etc.). </li></ul> </li><li><tt class="code"><span class="regexspecial">\p{Z}</span></tt> or <tt class="code"><span class="regexspecial">\p{Separator}</span></tt>: any kind of whitespace or invisible separator. <ul> <li><tt class="code"><span class="regexspecial">\p{Zs}</span></tt> or <tt class="code"><span class="regexspecial">\p{Space_Separator}</span></tt>: a whitespace character that is invisible, but does take up space. </li><li><tt class="code"><span class="regexspecial">\p{Zl}</span></tt> or <tt class="code"><span class="regexspecial">\p{Line_Separator}</span></tt>: line separator character U+2028. </li><li><tt class="code"><span class="regexspecial">\p{Zp}</span></tt> or <tt class="code"><span class="regexspecial">\p{Paragraph_Separator}</span></tt>: paragraph separator character U+2029. </li></ul> </li><li><tt class="code"><span class="regexspecial">\p{S}</span></tt> or <tt class="code"><span class="regexspecial">\p{Symbol}</span></tt>: math symbols, currency signs, dingbats, box-drawing characters, etc. <ul> <li><tt class="code"><span class="regexspecial">\p{Sm}</span></tt> or <tt class="code"><span class="regexspecial">\p{Math_Symbol}</span></tt>: any mathematical symbol. </li><li><tt class="code"><span class="regexspecial">\p{Sc}</span></tt> or <tt class="code"><span class="regexspecial">\p{Currency_Symbol}</span></tt>: any currency sign. </li><li><tt class="code"><span class="regexspecial">\p{Sk}</span></tt> or <tt class="code"><span class="regexspecial">\p{Modifier_Symbol}</span></tt>: a combining character (mark) as a full character on its own. </li><li><tt class="code"><span class="regexspecial">\p{So}</span></tt> or <tt class="code"><span class="regexspecial">\p{Other_Symbol}</span></tt>: various symbols that are not math symbols, currency signs, or combining characters. </li></ul> </li><li><tt class="code"><span class="regexspecial">\p{N}</span></tt> or <tt class="code"><span class="regexspecial">\p{Number}</span></tt>: any kind of numeric character in any script. <ul> <li><tt class="code"><span class="regexspecial">\p{Nd}</span></tt> or <tt class="code"><span class="regexspecial">\p{Decimal_Digit_Number}</span></tt>: a digit zero through nine in any script except ideographic scripts. </li><li><tt class="code"><span class="regexspecial">\p{Nl}</span></tt> or <tt class="code"><span class="regexspecial">\p{Letter_Number}</span></tt>: a number that looks like a letter, such as a Roman numeral. </li><li><tt class="code"><span class="regexspecial">\p{No}</span></tt> or <tt class="code"><span class="regexspecial">\p{Other_Number}</span></tt>: a superscript or subscript digit, or a number that is not a digit 0–9 (excluding numbers from ideographic scripts). </li></ul> </li><li><tt class="code"><span class="regexspecial">\p{P}</span></tt> or <tt class="code"><span class="regexspecial">\p{Punctuation}</span></tt>: any kind of punctuation character. <ul> <li><tt class="code"><span class="regexspecial">\p{Pd}</span></tt> or <tt class="code"><span class="regexspecial">\p{Dash_Punctuation}</span></tt>: any kind of hyphen or dash. </li><li><tt class="code"><span class="regexspecial">\p{Ps}</span></tt> or <tt class="code"><span class="regexspecial">\p{Open_Punctuation}</span></tt>: any kind of opening bracket. </li><li><tt class="code"><span class="regexspecial">\p{Pe}</span></tt> or <tt class="code"><span class="regexspecial">\p{Close_Punctuation}</span></tt>: any kind of closing bracket. </li><li><tt class="code"><span class="regexspecial">\p{Pi}</span></tt> or <tt class="code"><span class="regexspecial">\p{Initial_Punctuation}</span></tt>: any kind of opening quote. </li><li><tt class="code"><span class="regexspecial">\p{Pf}</span></tt> or <tt class="code"><span class="regexspecial">\p{Final_Punctuation}</span></tt>: any kind of closing quote. </li><li><tt class="code"><span class="regexspecial">\p{Pc}</span></tt> or <tt class="code"><span class="regexspecial">\p{Connector_Punctuation}</span></tt>: a punctuation character such as an underscore that connects words. </li><li><tt class="code"><span class="regexspecial">\p{Po}</span></tt> or <tt class="code"><span class="regexspecial">\p{Other_Punctuation}</span></tt>: any kind of punctuation character that is not a dash, bracket, quote or connector. </li></ul> </li><li><tt class="code"><span class="regexspecial">\p{C}</span></tt> or <tt class="code"><span class="regexspecial">\p{Other}</span></tt>: invisible control characters and unused code points. <ul> <li><tt class="code"><span class="regexspecial">\p{Cc}</span></tt> or <tt class="code"><span class="regexspecial">\p{Control}</span></tt>: an ASCII or Latin-1 control character: 0x00–0x1F and 0x7F–0x9F. </li><li><tt class="code"><span class="regexspecial">\p{Cf}</span></tt> or <tt class="code"><span class="regexspecial">\p{Format}</span></tt>: invisible formatting indicator. </li><li><tt class="code"><span class="regexspecial">\p{Co}</span></tt> or <tt class="code"><span class="regexspecial">\p{Private_Use}</span></tt>: any code point reserved for private use. </li><li><tt class="code"><span class="regexspecial">\p{Cs}</span></tt> or <tt class="code"><span class="regexspecial">\p{Surrogate}</span></tt>: one half of a surrogate pair in UTF-16 encoding. </li><li><tt class="code"><span class="regexspecial">\p{Cn}</span></tt> or <tt class="code"><span class="regexspecial">\p{Unassigned}</span></tt>: any code point to which no character has been assigned. </li></ul> </li></ul>
                    <h3>Unicode Scripts</h3>
                    <ol> <li><tt class="code"><span class="regexspecial">\p{Common}</span></tt> </li><li><tt class="code"><span class="regexspecial">\p{Arabic}</span></tt> </li><li><tt class="code"><span class="regexspecial">\p{Armenian}</span></tt> </li><li><tt class="code"><span class="regexspecial">\p{Bengali}</span></tt> </li><li><tt class="code"><span class="regexspecial">\p{Bopomofo}</span></tt> </li><li><tt class="code"><span class="regexspecial">\p{Braille}</span></tt> </li><li><tt class="code"><span class="regexspecial">\p{Buhid}</span></tt> </li><li><tt class="code"><span class="regexspecial">\p{Canadian_Aboriginal}</span></tt> </li><li><tt class="code"><span class="regexspecial">\p{Cherokee}</span></tt> </li><li><tt class="code"><span class="regexspecial">\p{Cyrillic}</span></tt> </li><li><tt class="code"><span class="regexspecial">\p{Devanagari}</span></tt> </li><li><tt class="code"><span class="regexspecial">\p{Ethiopic}</span></tt> </li><li><tt class="code"><span class="regexspecial">\p{Georgian}</span></tt> </li><li><tt class="code"><span class="regexspecial">\p{Greek}</span></tt> </li><li><tt class="code"><span class="regexspecial">\p{Gujarati}</span></tt> </li><li><tt class="code"><span class="regexspecial">\p{Gurmukhi}</span></tt> </li><li><tt class="code"><span class="regexspecial">\p{Han}</span></tt> </li><li><tt class="code"><span class="regexspecial">\p{Hangul}</span></tt> </li><li><tt class="code"><span class="regexspecial">\p{Hanunoo}</span></tt> </li><li><tt class="code"><span class="regexspecial">\p{Hebrew}</span></tt> </li><li><tt class="code"><span class="regexspecial">\p{Hiragana}</span></tt> </li><li><tt class="code"><span class="regexspecial">\p{Inherited}</span></tt> </li><li><tt class="code"><span class="regexspecial">\p{Kannada}</span></tt> </li><li><tt class="code"><span class="regexspecial">\p{Katakana}</span></tt> </li><li><tt class="code"><span class="regexspecial">\p{Khmer}</span></tt> </li><li><tt class="code"><span class="regexspecial">\p{Lao}</span></tt> </li><li><tt class="code"><span class="regexspecial">\p{Latin}</span></tt> </li><li><tt class="code"><span class="regexspecial">\p{Limbu}</span></tt> </li><li><tt class="code"><span class="regexspecial">\p{Malayalam}</span></tt> </li><li><tt class="code"><span class="regexspecial">\p{Mongolian}</span></tt> </li><li><tt class="code"><span class="regexspecial">\p{Myanmar}</span></tt> </li><li><tt class="code"><span class="regexspecial">\p{Ogham}</span></tt> </li><li><tt class="code"><span class="regexspecial">\p{Oriya}</span></tt> </li><li><tt class="code"><span class="regexspecial">\p{Runic}</span></tt> </li><li><tt class="code"><span class="regexspecial">\p{Sinhala}</span></tt> </li><li><tt class="code"><span class="regexspecial">\p{Syriac}</span></tt> </li><li><tt class="code"><span class="regexspecial">\p{Tagalog}</span></tt> </li><li><tt class="code"><span class="regexspecial">\p{Tagbanwa}</span></tt> </li><li><tt class="code"><span class="regexspecial">\p{TaiLe}</span></tt> </li><li><tt class="code"><span class="regexspecial">\p{Tamil}</span></tt> </li><li><tt class="code"><span class="regexspecial">\p{Telugu}</span></tt> </li><li><tt class="code"><span class="regexspecial">\p{Thaana}</span></tt> </li><li><tt class="code"><span class="regexspecial">\p{Thai}</span></tt> </li><li><tt class="code"><span class="regexspecial">\p{Tibetan}</span></tt> </li><li><tt class="code"><span class="regexspecial">\p{Yi}</span></tt> </li></ol>
                    <h3>Unicode Blocks</h3>
                    <ol> <li><tt class="code"><span class="regexspecial">\p{InBasic_Latin}</span></tt>: U+0000–U+007F </li><li><tt class="code"><span class="regexspecial">\p{InLatin-1_Supplement}</span></tt>: U+0080–U+00FF </li><li><tt class="code"><span class="regexspecial">\p{InLatin_Extended-A}</span></tt>: U+0100–U+017F </li><li><tt class="code"><span class="regexspecial">\p{InLatin_Extended-B}</span></tt>: U+0180–U+024F </li><li><tt class="code"><span class="regexspecial">\p{InIPA_Extensions}</span></tt>: U+0250–U+02AF </li><li><tt class="code"><span class="regexspecial">\p{InSpacing_Modifier_Letters}</span></tt>: U+02B0–U+02FF </li><li><tt class="code"><span class="regexspecial">\p{InCombining_Diacritical_Marks}</span></tt>: U+0300–U+036F </li><li><tt class="code"><span class="regexspecial">\p{InGreek_and_Coptic}</span></tt>: U+0370–U+03FF </li><li><tt class="code"><span class="regexspecial">\p{InCyrillic}</span></tt>: U+0400–U+04FF </li><li><tt class="code"><span class="regexspecial">\p{InCyrillic_Supplementary}</span></tt>: U+0500–U+052F </li><li><tt class="code"><span class="regexspecial">\p{InArmenian}</span></tt>: U+0530–U+058F </li><li><tt class="code"><span class="regexspecial">\p{InHebrew}</span></tt>: U+0590–U+05FF </li><li><tt class="code"><span class="regexspecial">\p{InArabic}</span></tt>: U+0600–U+06FF </li><li><tt class="code"><span class="regexspecial">\p{InSyriac}</span></tt>: U+0700–U+074F </li><li><tt class="code"><span class="regexspecial">\p{InThaana}</span></tt>: U+0780–U+07BF </li><li><tt class="code"><span class="regexspecial">\p{InDevanagari}</span></tt>: U+0900–U+097F </li><li><tt class="code"><span class="regexspecial">\p{InBengali}</span></tt>: U+0980–U+09FF </li><li><tt class="code"><span class="regexspecial">\p{InGurmukhi}</span></tt>: U+0A00–U+0A7F </li><li><tt class="code"><span class="regexspecial">\p{InGujarati}</span></tt>: U+0A80–U+0AFF </li><li><tt class="code"><span class="regexspecial">\p{InOriya}</span></tt>: U+0B00–U+0B7F </li><li><tt class="code"><span class="regexspecial">\p{InTamil}</span></tt>: U+0B80–U+0BFF </li><li><tt class="code"><span class="regexspecial">\p{InTelugu}</span></tt>: U+0C00–U+0C7F </li><li><tt class="code"><span class="regexspecial">\p{InKannada}</span></tt>: U+0C80–U+0CFF </li><li><tt class="code"><span class="regexspecial">\p{InMalayalam}</span></tt>: U+0D00–U+0D7F </li><li><tt class="code"><span class="regexspecial">\p{InSinhala}</span></tt>: U+0D80–U+0DFF </li><li><tt class="code"><span class="regexspecial">\p{InThai}</span></tt>: U+0E00–U+0E7F </li><li><tt class="code"><span class="regexspecial">\p{InLao}</span></tt>: U+0E80–U+0EFF </li><li><tt class="code"><span class="regexspecial">\p{InTibetan}</span></tt>: U+0F00–U+0FFF </li><li><tt class="code"><span class="regexspecial">\p{InMyanmar}</span></tt>: U+1000–U+109F </li><li><tt class="code"><span class="regexspecial">\p{InGeorgian}</span></tt>: U+10A0–U+10FF </li><li><tt class="code"><span class="regexspecial">\p{InHangul_Jamo}</span></tt>: U+1100–U+11FF </li><li><tt class="code"><span class="regexspecial">\p{InEthiopic}</span></tt>: U+1200–U+137F </li><li><tt class="code"><span class="regexspecial">\p{InCherokee}</span></tt>: U+13A0–U+13FF </li><li><tt class="code"><span class="regexspecial">\p{InUnified_Canadian_Aboriginal_Syllabics}</span></tt>: U+1400–U+167F </li><li><tt class="code"><span class="regexspecial">\p{InOgham}</span></tt>: U+1680–U+169F </li><li><tt class="code"><span class="regexspecial">\p{InRunic}</span></tt>: U+16A0–U+16FF </li><li><tt class="code"><span class="regexspecial">\p{InTagalog}</span></tt>: U+1700–U+171F </li><li><tt class="code"><span class="regexspecial">\p{InHanunoo}</span></tt>: U+1720–U+173F </li><li><tt class="code"><span class="regexspecial">\p{InBuhid}</span></tt>: U+1740–U+175F </li><li><tt class="code"><span class="regexspecial">\p{InTagbanwa}</span></tt>: U+1760–U+177F </li><li><tt class="code"><span class="regexspecial">\p{InKhmer}</span></tt>: U+1780–U+17FF </li><li><tt class="code"><span class="regexspecial">\p{InMongolian}</span></tt>: U+1800–U+18AF </li><li><tt class="code"><span class="regexspecial">\p{InLimbu}</span></tt>: U+1900–U+194F </li><li><tt class="code"><span class="regexspecial">\p{InTai_Le}</span></tt>: U+1950–U+197F </li><li><tt class="code"><span class="regexspecial">\p{InKhmer_Symbols}</span></tt>: U+19E0–U+19FF </li><li><tt class="code"><span class="regexspecial">\p{InPhonetic_Extensions}</span></tt>: U+1D00–U+1D7F </li><li><tt class="code"><span class="regexspecial">\p{InLatin_Extended_Additional}</span></tt>: U+1E00–U+1EFF </li><li><tt class="code"><span class="regexspecial">\p{InGreek_Extended}</span></tt>: U+1F00–U+1FFF </li><li><tt class="code"><span class="regexspecial">\p{InGeneral_Punctuation}</span></tt>: U+2000–U+206F </li><li><tt class="code"><span class="regexspecial">\p{InSuperscripts_and_Subscripts}</span></tt>: U+2070–U+209F </li><li><tt class="code"><span class="regexspecial">\p{InCurrency_Symbols}</span></tt>: U+20A0–U+20CF </li><li><tt class="code"><span class="regexspecial">\p{InCombining_Diacritical_Marks_for_Symbols}</span></tt>: U+20D0–U+20FF </li><li><tt class="code"><span class="regexspecial">\p{InLetterlike_Symbols}</span></tt>: U+2100–U+214F </li><li><tt class="code"><span class="regexspecial">\p{InNumber_Forms}</span></tt>: U+2150–U+218F </li><li><tt class="code"><span class="regexspecial">\p{InArrows}</span></tt>: U+2190–U+21FF </li><li><tt class="code"><span class="regexspecial">\p{InMathematical_Operators}</span></tt>: U+2200–U+22FF </li><li><tt class="code"><span class="regexspecial">\p{InMiscellaneous_Technical}</span></tt>: U+2300–U+23FF </li><li><tt class="code"><span class="regexspecial">\p{InControl_Pictures}</span></tt>: U+2400–U+243F </li><li><tt class="code"><span class="regexspecial">\p{InOptical_Character_Recognition}</span></tt>: U+2440–U+245F </li><li><tt class="code"><span class="regexspecial">\p{InEnclosed_Alphanumerics}</span></tt>: U+2460–U+24FF </li><li><tt class="code"><span class="regexspecial">\p{InBox_Drawing}</span></tt>: U+2500–U+257F </li><li><tt class="code"><span class="regexspecial">\p{InBlock_Elements}</span></tt>: U+2580–U+259F </li><li><tt class="code"><span class="regexspecial">\p{InGeometric_Shapes}</span></tt>: U+25A0–U+25FF </li><li><tt class="code"><span class="regexspecial">\p{InMiscellaneous_Symbols}</span></tt>: U+2600–U+26FF </li><li><tt class="code"><span class="regexspecial">\p{InDingbats}</span></tt>: U+2700–U+27BF </li><li><tt class="code"><span class="regexspecial">\p{InMiscellaneous_Mathematical_Symbols-A}</span></tt>: U+27C0–U+27EF </li><li><tt class="code"><span class="regexspecial">\p{InSupplemental_Arrows-A}</span></tt>: U+27F0–U+27FF </li><li><tt class="code"><span class="regexspecial">\p{InBraille_Patterns}</span></tt>: U+2800–U+28FF </li><li><tt class="code"><span class="regexspecial">\p{InSupplemental_Arrows-B}</span></tt>: U+2900–U+297F </li><li><tt class="code"><span class="regexspecial">\p{InMiscellaneous_Mathematical_Symbols-B}</span></tt>: U+2980–U+29FF </li><li><tt class="code"><span class="regexspecial">\p{InSupplemental_Mathematical_Operators}</span></tt>: U+2A00–U+2AFF </li><li><tt class="code"><span class="regexspecial">\p{InMiscellaneous_Symbols_and_Arrows}</span></tt>: U+2B00–U+2BFF </li><li><tt class="code"><span class="regexspecial">\p{InCJK_Radicals_Supplement}</span></tt>: U+2E80–U+2EFF </li><li><tt class="code"><span class="regexspecial">\p{InKangxi_Radicals}</span></tt>: U+2F00–U+2FDF </li><li><tt class="code"><span class="regexspecial">\p{InIdeographic_Description_Characters}</span></tt>: U+2FF0–U+2FFF </li><li><tt class="code"><span class="regexspecial">\p{InCJK_Symbols_and_Punctuation}</span></tt>: U+3000–U+303F </li><li><tt class="code"><span class="regexspecial">\p{InHiragana}</span></tt>: U+3040–U+309F </li><li><tt class="code"><span class="regexspecial">\p{InKatakana}</span></tt>: U+30A0–U+30FF </li><li><tt class="code"><span class="regexspecial">\p{InBopomofo}</span></tt>: U+3100–U+312F </li><li><tt class="code"><span class="regexspecial">\p{InHangul_Compatibility_Jamo}</span></tt>: U+3130–U+318F </li><li><tt class="code"><span class="regexspecial">\p{InKanbun}</span></tt>: U+3190–U+319F </li><li><tt class="code"><span class="regexspecial">\p{InBopomofo_Extended}</span></tt>: U+31A0–U+31BF </li><li><tt class="code"><span class="regexspecial">\p{InKatakana_Phonetic_Extensions}</span></tt>: U+31F0–U+31FF </li><li><tt class="code"><span class="regexspecial">\p{InEnclosed_CJK_Letters_and_Months}</span></tt>: U+3200–U+32FF </li><li><tt class="code"><span class="regexspecial">\p{InCJK_Compatibility}</span></tt>: U+3300–U+33FF </li><li><tt class="code"><span class="regexspecial">\p{InCJK_Unified_Ideographs_Extension_A}</span></tt>: U+3400–U+4DBF </li><li><tt class="code"><span class="regexspecial">\p{InYijing_Hexagram_Symbols}</span></tt>: U+4DC0–U+4DFF </li><li><tt class="code"><span class="regexspecial">\p{InCJK_Unified_Ideographs}</span></tt>: U+4E00–U+9FFF </li><li><tt class="code"><span class="regexspecial">\p{InYi_Syllables}</span></tt>: U+A000–U+A48F </li><li><tt class="code"><span class="regexspecial">\p{InYi_Radicals}</span></tt>: U+A490–U+A4CF </li><li><tt class="code"><span class="regexspecial">\p{InHangul_Syllables}</span></tt>: U+AC00–U+D7AF </li><li><tt class="code"><span class="regexspecial">\p{InHigh_Surrogates}</span></tt>: U+D800–U+DB7F </li><li><tt class="code"><span class="regexspecial">\p{InHigh_Private_Use_Surrogates}</span></tt>: U+DB80–U+DBFF </li><li><tt class="code"><span class="regexspecial">\p{InLow_Surrogates}</span></tt>: U+DC00–U+DFFF </li><li><tt class="code"><span class="regexspecial">\p{InPrivate_Use_Area}</span></tt>: U+E000–U+F8FF </li><li><tt class="code"><span class="regexspecial">\p{InCJK_Compatibility_Ideographs}</span></tt>: U+F900–U+FAFF </li><li><tt class="code"><span class="regexspecial">\p{InAlphabetic_Presentation_Forms}</span></tt>: U+FB00–U+FB4F </li><li><tt class="code"><span class="regexspecial">\p{InArabic_Presentation_Forms-A}</span></tt>: U+FB50–U+FDFF </li><li><tt class="code"><span class="regexspecial">\p{InVariation_Selectors}</span></tt>: U+FE00–U+FE0F </li><li><tt class="code"><span class="regexspecial">\p{InCombining_Half_Marks}</span></tt>: U+FE20–U+FE2F </li><li><tt class="code"><span class="regexspecial">\p{InCJK_Compatibility_Forms}</span></tt>: U+FE30–U+FE4F </li><li><tt class="code"><span class="regexspecial">\p{InSmall_Form_Variants}</span></tt>: U+FE50–U+FE6F </li><li><tt class="code"><span class="regexspecial">\p{InArabic_Presentation_Forms-B}</span></tt>: U+FE70–U+FEFF </li><li><tt class="code"><span class="regexspecial">\p{InHalfwidth_and_Fullwidth_Forms}</span></tt>: U+FF00–U+FFEF </li><li><tt class="code"><span class="regexspecial">\p{InSpecials}</span></tt>: U+FFF0–U+FFFF </li></ol>
                </b-tab>
            </b-tabs>
        </b-modal>
    </div>
</template>

<script>

import SimpleMDE from '../lib/simplemde-markdown-editor/src/js/simplemde.js';

import '../lib/simplemde-markdown-editor/dist/simplemde.min.css';

import Vue, { VueConstructor } from 'vue'

import TurndownService from 'turndown'

window.oTurndownService = new TurndownService();

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
            
            bEditorDirty: false,
            
            oSimpleMDE: null,
            oUploadedFile: null,
            
            bShowReplacementBlock: false,
            bShowTranslationBlock: false,
            
            bUseRegularExpression: false,
            bUseCaseSensetive: false,
            iSearchPosFrom: null,
            iSearchPosTo: null,
            sSearchLastQuery: null,
            sSearchQuery: '',
            sSearchQueryText: '',
            oSearchOverlay: null,
            oSearchAnnotate: null,
            
            aTranslationProviders: [
                { value: 'google', text: 'Google' },
                { value: 'yandex', text: 'Yandex' }
            ],
            sTranslationProvider: 'google',
            aTranslationFromLanguage: [
                { value: 'auto', text: 'Auto' },
                { value: 'en', text: 'English' },
                { value: 'ru', text: 'Russian' }
            ],
            sTranslationFromLanguage: 'auto',
            aTranslationToLanguage: [
                { value: 'en', text: 'English' },
                { value: 'ru', text: 'Russian' }
            ],
            sTranslationToLanguage: 'ru',
            
            sYoutubeVideoURL: '',
            
            aImagesModalFiles: [],
            aImagesModalSelectedFiles: [],
            sImagesFilterString: '',
            sUploadImagesMode: '',
            iImagesModalScrollPosition: 0,
            
            aFilesModalFiles: [],           
            aFilesModalSelectedFiles: [],
            sFilesFilterString: '',
            sUploadFilesMode: '',
            iFilesModalScrollPosition: 0,
            
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
            iArticleViewScrollPosition: 0,
            
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
            
            if (typeof this.oRepository.oTags[this.sActiveTag] === 'undefined') {
                return [];
            }
            
            return this.oRepository.oTags[this.sActiveTag];
        },
        sActiveArticle: function()
        {
            return this.aArticles[this.iActiveArticle];
        }
    },
    
    methods: {
        fnPushRepository: function(bPushOnly, fnCallback)
        {
            var oData = {
                action: 'push_repository',
                repository: this.oRepository.sName
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
                    this.bEditorDirty = false;
                    
                    if (oResponse.body.status=='error') {
                        this.$snotify.error(oResponse.body.message, 'Error');
                        return;
                    }
                    
                    this.$snotify.success("Repository successfully saved");
                    
                    if (!bPushOnly) {
                        this.bShowSaveButtonSpinner = false;
                    }
                    
                    this.fnRefreshArticleViewer();
                    
                    if (fnCallback) fnCallback();
                })
                .catch(function(sError)
                {
                    this.$snotify.error(sError);
                });            
        },
        fnCheckNewTagForm: function()
        {
            console.log('fnCheckNewTagForm');
            var bValid = this.$refs.add_new_tag_modal_form.checkValidity();
            
            if (this.oRepository.oTags[this.sNewTag]) {
                this.sNewTagInvalidFeedback = "Tag already exists";
                this.sNewTagFieldState = 'invalid';
                return false;
            }
            
            this.sNewTagInvalidFeedback = "Tag is required";
            
            this.sNewTagFieldState = bValid ? 'valid' : 'invalid';
            
            return bValid;
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
            });
            
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
            
            this.$refs.add_new_tag_modal.show();

            var oThis = this;
            
            setTimeout(function() {
                oThis.$refs.add_new_tag_modal.title = 'Add new tag';
            }, 300);
            
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
                })
                .catch(function(sError)
                {
                    this.$snotify.error(sError);
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
                })
                .catch(function(sError)
                {
                    this.$snotify.error(sError);
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
                })
                .catch(function(sError)
                {
                    this.$snotify.error(sError);
                });
        },
        
        fnCheckNewArticleForm: function()
        {
            console.log('fnCheckNewArticleForm');
            var bValid = this.$refs.add_new_article_modal_form.checkValidity();
            
            if (this.oRepository.aArticles.indexOf(this.sNewArticle)!=-1) {
                this.sNewArticleInvalidFeedback = "Article already exists";
                this.sNewArticleFieldState = 'invalid';
                return false;
            }

            this.sNewArticleInvalidFeedback = "Article name is required";
            
            this.sNewArticleFieldState = bValid ? 'valid' : 'invalid';
            
            return bValid;
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
            });
            
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
                })
                .catch(function(sError)
                {
                    this.$snotify.error(sError);
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
                })
                .catch(function(sError)
                {
                    this.$snotify.error(sError);
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
                    } else {
                        iNewiActiveArticle = 0;
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
                    
                    this.fnSelectArticle(iNewiActiveArticle);
                    
                    if (fnCallback) fnCallback.call(this);
                })
                .catch(function(sError)
                {
                    this.$snotify.error(sError);
                });
        },
        fnAddArticleTag: function(sArticle, sTag)
        {
            window.oApplication.bShowLoadingScreen = true;
            
            var oThis = this;
            
            this.fnPushRepository(
                false, 
                function()
                {
                    oThis
                        .$http
                        .post(
                            '',
                            {
                                action: 'add_tag_to_article',
                                repository: oThis.oRepository.sName,
                                article: sArticle,
                                tag: sTag
                            }
                        ).then(function(oResponse)
                        {                    
                            window.oApplication.bShowLoadingScreen = false;
                            
                            if (oResponse.body.status=='error') {
                                oThis.$snotify.error(oResponse.body.message, 'Error');
                                return;
                            }
                            
                            oThis.oRepository.oTags[sTag].push(sArticle);
                            
                            oThis.fnSelectArticle(oThis.iActiveArticle);
                        })
                        .catch(function(sError)
                        {
                            oThis.$snotify.error(sError);
                        });                
                }
            );
        },
        fnRemoveArticleTag: function(sArticle, sTag)
        {
            window.oApplication.bShowLoadingScreen = true;
            
            var oThis = this;
            
            this.fnPushRepository(
                false, 
                function()
                {
                    oThis
                        .$http
                        .post(
                            '',
                            {
                                action: 'remove_tag_from_article',
                                repository: oThis.oRepository.sName,
                                article: sArticle,
                                tag: sTag
                            }
                        ).then(function(oResponse)
                        {
                            window.oApplication.bShowLoadingScreen = false;
                            
                            if (oResponse.body.status=='error') {
                                oThis.$snotify.error(oResponse.body.message, 'Error');
                                return;
                            }
                            
                            var iIndex = oThis.fnFindArticleInTag(sArticle, sTag);
                            if (oThis.iActiveArticle == iIndex) {
                                var sArticle = oThis.oRepository.oTags[sTag][iIndex];
                                
                                oThis.fnSelectTag('__all__');
                                oThis.fnSelectArticleWithName(sArticle);
                            }
                            oThis.oRepository.oTags[sTag].splice(iIndex, 1);
                            
                            oThis.fnSelectArticle(oThis.iActiveArticle);                    
                        })
                        .catch(function(sError)
                        {
                            oThis.$snotify.error(sError);
                        });
                }
            );
        },
        fnFindTagsWithArticle: function(sArticle)
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
        fnFindArticleInTags: function(sArticle)
        {
            var aResult = [];
            
            for (var sTag in this.oRepository.oTags) {
                aResult.push(this.oRepository.oTags[sTag].indexOf(sArticle));
            }
            
            return aResult;
        },
        fnFindArticleInTag: function(sArticle, sTag)
        {
            console.log('fnFindArticleInTag', sArticle, sTag);
            return this.oRepository.oTags[sTag].indexOf(sArticle);
        },
        fnGetArticlesCountByTagName: function(sTag)
        {
            if (sTag=='__all__') {
                return this.oRepository.aArticles.length;
            }
            
            return this.oRepository.oTags[sTag].length;
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
            console.log('fnSelectArticleWithName', sName);
            
            this.fnSelectArticle(this.aArticles.indexOf(sName));
        },
        fnSelectArticle: function(iIndex)
        {
            console.log('fnSelectArticle', iIndex);
            
            if (typeof this.aArticles[iIndex] == 'undefined') {
                return;
            }
            
            this.iArticleViewScrollPosition = 0;
            
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
                    localStorage.setItem(this.oRepository.sName+'_iActiveArticle', this.aArticles[iIndex]);
            
                    var oThis = this;
                    
                    setTimeout(
                        function()
                        {
                            oThis.oSimpleMDE.value(oResponse.body.data);
                        }, 
                        100
                    );
                    
                    this.fnRefreshArticleViewer();
                })
                .catch(function(sError)
                {
                    this.$snotify.error(sError);
                });
        },
        fnArticleViewScroll: function()
        {
            console.log("fnArticleViewScroll", this.$refs.article_view_contents.scrollTop);
            this.iArticleViewScrollPosition = this.$refs.article_view_contents.scrollTop;
        },
        fnRefreshArticleViewer: function()
        {
            this.bShowArticleViewContentsSpinner = true;
            
            var oThis = this;
            
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
                        oThis.$snotify.error(oResponse.body.message, 'Error');
                        return;
                    }
                    
                    oThis.bShowArticleViewContentsSpinner = false;
                    
                    oThis.sArticleViewContents = oResponse.body.data;
            
                    setTimeout(
                        function()
                        {
                            oThis.$refs.article_view_contents.scrollTop = oThis.iArticleViewScrollPosition;
                        }, 
                        100
                    );
                })
                .catch(function(sError)
                {
                    this.$snotify.error(sError);
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
                })
                .catch(function(sError)
                {
                    this.$snotify.error(sError);
                });
        },
        
        fnInsertImage: function(sURL, bCursorToEnd)
        {
            var cm = this.oSimpleMDE.codemirror;
            var stat = this.fnGetState(cm);
            var options = this.oSimpleMDE.options;
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
                                this.aImagesModalFiles.splice(iIndex, 1);
                                this.aImagesModalFiles.push(sImage);
                            } else {
                                this.aImagesModalFiles.push(sImage);
                            }
                        }
                        
                        var iScrollTop = this.$refs.images_modal_list.scrollHeight - this.$refs.images_modal_list.clientHeight;
                        this.$refs.images_modal_list.scrollTop = iScrollTop
                        this.iImagesModalScrollPosition = this.$refs.images_modal_list.scrollTop;

                    } else if (this.sUploadImagesMode=='insert') {
                        for (var iIndex=0; iIndex<oResponse.body.data.length; iIndex++) {
                            this.fnInsertImage('/images/'+oResponse.body.data[iIndex]);
                        }
                    }
                })
                .catch(function(sError)
                {
                    this.$snotify.error(sError);
                });
        },
        fnShowArticleGithubPage: function()
        {
            var sUser = this.oRepository.sUser;
            var sRepository = this.oRepository.sName;
            var sArticle = this.sActiveArticle;
            var sURL = "https://github.com/"+sUser+"/"+sRepository+"/blob/master/articles/"+sArticle+".md";
            
            var oWindow = window.open(sURL, '_blank');
            oWindow.focus();
        },
        fnShowImagesModal: function()
        {
            this.sUploadImagesMode = 'update-modal';
            this.$refs.images_modal.hideFooter = true;
            this.$refs.images_modal.show();
        },
        fnUpdateImagesList: function()
        {
            var oThis = this;
            
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
                        oThis.$snotify.error(oResponse.body.message, 'Error');
                        return;
                    }
                    
                    oThis.aImagesModalFiles = oResponse.body.data;
                    
                    setTimeout(
                        function()
                        {
                            oThis.$refs.images_modal_list.scrollTop = oThis.iImagesModalScrollPosition;
                        },
                        100
                    );
                })
                .catch(function(sError)
                {
                    this.$snotify.error(sError);
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
        fnAddImageFromLink: function()
        {
            var sURL = null;
            
            if ((sURL = prompt("Enter URL")) !== null) {
                window.oApplication.bShowLoadingScreen = true;
                
                this
                    .$http
                    .post(
                        '',
                        {
                            action: 'add_images_from_urls',
                            repository: this.oRepository.sName,
                            urls: [sURL]
                        }
                    ).then(function(oResponse)
                    {
                        window.oApplication.bShowLoadingScreen = false;
                        
                        if (oResponse.body.status=='error') {
                            this.$snotify.error(oResponse.body.message, 'Error');
                            return;
                        }

                        var sImage = oResponse.body.data[0];
                        var iIndex = this.aImagesModalFiles.indexOf(sImage);
                        
                        if (iIndex>-1) {
                            this.aImagesModalFiles.splice(iIndex, 1);
                            this.aImagesModalFiles.push(sImage);
                        } else {
                            this.aImagesModalFiles.push(sImage);
                        }
                        
                        var iScrollTop = this.$refs.images_modal_list.scrollHeight - this.$refs.images_modal_list.clientHeight;
                        this.$refs.images_modal_list.scrollTop = iScrollTop
                        this.iImagesModalScrollPosition = this.$refs.images_modal_list.scrollTop;
                    })
                    .catch(function(sError)
                    {
                        this.$snotify.error(sError);
                    });
            }
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
                })
                .catch(function(sError)
                {
                    this.$snotify.error(sError);
                });
        },
        fnImagesModalScroll: function()
        {
            this.iImagesModalScrollPosition = this.$refs.images_modal_list.scrollTop;
        },
        
        fnInsertFile: function(sURL, bCursorToEnd)
        {
            var cm = this.oSimpleMDE.codemirror;
            var stat = this.fnGetState(cm);
            var options = this.oSimpleMDE.options;
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
                                this.aFilesModalFiles.splice(iIndex, 1);
                                this.aFilesModalFiles.push(sFile);
                            } else {
                                this.aFilesModalFiles.push(sFile);
                            }                            
                        }
                        
                        var iScrollTop = this.$refs.files_modal_list.scrollHeight - this.$refs.files_modal_list.clientHeight;
                        this.$refs.files_modal_list.scrollTop = iScrollTop
                        this.iFilesModalScrollPosition = this.$refs.files_modal_list.scrollTop;
                        
                    } else if (this.sUploadFilesMode=='insert') {
                        for (var iIndex=0; iIndex<oResponse.body.data.length; iIndex++) {
                            this.fnInsertFile('/files/'+oResponse.body.data[iIndex]);
                        }
                    }
                })
                .catch(function(sError)
                {
                    this.$snotify.error(sError);
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

                    setTimeout(
                        function()
                        {
                            oThis.$refs.files_modal_list.scrollTop = oThis.iFilesModalScrollPosition;
                        },
                        100
                    );
                })
                .catch(function(sError)
                {
                    this.$snotify.error(sError);
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
        fnAddFileFromLink: function()
        {
            var sURL = null;
            
            if ((sURL = prompt("Enter URL")) !== null) {
                window.oApplication.bShowLoadingScreen = true;
                
                this
                    .$http
                    .post(
                        '',
                        {
                            action: 'add_files_from_urls',
                            repository: this.oRepository.sName,
                            urls: [sURL]
                        }
                    ).then(function(oResponse)
                    {
                        window.oApplication.bShowLoadingScreen = false;
                        
                        if (oResponse.body.status=='error') {
                            this.$snotify.error(oResponse.body.message, 'Error');
                            return;
                        }

                        var sFile = oResponse.body.data[0];
                        var iIndex = this.aFilesModalFiles.indexOf(sFile);
                        
                        if (iIndex>-1) {
                            this.aFilesModalFiles.splice(iIndex, 1);
                            this.aFilesModalFiles.push(sFile);
                        } else {
                            this.aFilesModalFiles.push(sFile);
                        }
                        
                        var iScrollTop = this.$refs.files_modal_list.scrollHeight - this.$refs.files_modal_list.clientHeight;
                        this.$refs.files_modal_list.scrollTop = iScrollTop
                        this.iFilesModalScrollPosition = this.$refs.files_modal_list.scrollTop;
                    })
                    .catch(function(sError)
                    {
                        this.$snotify.error(sError);
                    });
            }            
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
                })
                .catch(function(sError)
                {
                    this.$snotify.error(sError);
                });
        },
        fnFilesModalScroll: function()
        {
            this.iFilesModalScrollPosition = this.$refs.files_modal_list.scrollTop;
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
        
        fnClearSearch: function()
        {
            var oCodeMirror = this.oSimpleMDE.codemirror;
            var oThis = this;
            
            oCodeMirror.operation(function() 
            {
                oThis.sSearchLastQuery = oThis.sSearchQuery;
                if (!oThis.sSearchQuery) return;
                
                oCodeMirror.removeOverlay(oThis.oSearchOverlay);
                
                if (oThis.oSearchAnnotate) { 
                    oThis.oSearchAnnotate.clear(); 
                    oThis.oSearchAnnotate = null; 
                }
            });
        },
        fnQueryCaseInsensitive: function(sQuery) {
            return typeof sQuery == "string" && sQuery == sQuery.toLowerCase();
        },
        fnGetSearchCursor: function(oCodeMirror, sQuery, iPos) {
            return oCodeMirror.getSearchCursor(sQuery, iPos, {caseFold: this.fnQueryCaseInsensitive(sQuery), multiline: true});
        },
        fnFindNext: function()
        {
            this.fnEditorDoSearch();
        },
        fnFindPrevious: function()
        {
            this.fnEditorDoSearch(true);
        },
        fnEditorDoSearch: function(bRev, persistent, immediate) 
        {
            if (this.sSearchQuery) 
                return this.fnEditorFindNext(bRev);
        },
        fnEditorFindNext: function(bRev, fnCallback)
        {
            var oThis = this;
            
            var CodeMirror = this.oSimpleMDE.CodeMirror;
            var oCodeMirror = this.oSimpleMDE.codemirror;
            
            oCodeMirror.operation(function() 
            {
                var oCursor = oThis.fnGetSearchCursor(
                    oCodeMirror, 
                    oThis.sSearchQuery, 
                    bRev ? oThis.iSearchPosFrom : oThis.iSearchPosTo
                );
                
                if (!oCursor.find(bRev)) {
                    oCursor = oThis.fnGetSearchCursor(
                        oCodeMirror, 
                        oThis.sSearchQuery, 
                        bRev ? CodeMirror.Pos(oCodeMirror.lastLine()) : CodeMirror.Pos(oCodeMirror.firstLine(), 0)
                    );
                    if (!oCursor.find(bRev)) return;
                }
                
                oCodeMirror.setSelection(oCursor.from(), oCursor.to());
                oCodeMirror.scrollIntoView({from: oCursor.from(), to: oCursor.to()}, 20);
                
                oThis.iSearchPosFrom = oCursor.from(); 
                oThis.iSearchPosTo = oCursor.to();
                
                if (fnCallback) fnCallback(oCursor.from(), oCursor.to())
            });            
        },
        fnPrepareQuery: function(sQuery)
        {
            if (!this.bUseRegularExpression) {
                sQuery = sQuery.replace(/(\.|\[|\]|\(|\)|\*|\?|\\|\+|\{|\})/g, "\\$1");
            }
            
            var oRegExp;
            
            try {
                oRegExp = new RegExp(sQuery, this.bUseCaseSensetive ? "u" : "ui");
            } catch(oException) {
                this.$snotify.error('Wrong regular expression');
                return "";
            }
            
            return oRegExp;
        },
        fnReplace: function()
        {
            this.fnEditorReplace();            
        },
        fnReplaceAll: function()
        {
            this.fnEditorReplace(true);
        },
        fnEditorReplaceAll: function(mQuery, sText)
        {
            var oCodeMirror = this.oSimpleMDE.codemirror;
            var oThis = this;
            
            oCodeMirror.operation(function() 
            {
                for (var oCursor = oThis.fnGetSearchCursor(oCodeMirror, mQuery); oCursor.findNext();) {
                    if (typeof mQuery != "string") {
                        var oMatch = oCodeMirror.getRange(oCursor.from(), oCursor.to()).match(mQuery);
                        oCursor.replace(sText.replace(/\$(\d)/g, function(_, i) {return oMatch[i];}));
                    } else 
                        oCursor.replace(sText);
                }
            });
        },
        fnEditorReplace: function(bAll)
        {
            var oCodeMirror = this.oSimpleMDE.codemirror;
            var oThis = this;
            
            if (oCodeMirror.getOption("readOnly")) return;
            
            //var query = oCodeMirror.getSelection() || getSearchState(cm).lastQuery;
            var mQuery = this.fnPrepareQuery(this.sSearchQuery);

            if (bAll) {
                this.fnEditorReplaceAll(mQuery, this.sSearchQueryText);
            } else {
                this.fnClearSearch();
                
                var oCursor = this.fnGetSearchCursor(oCodeMirror, mQuery, oCodeMirror.getCursor("from"));
                
                var fnAdvance = function() 
                {
                    var oStart = oCursor.from(), oMatch;
                    
                    if (!(oMatch = oCursor.findNext())) {
                        oCursor = oThis.fnGetSearchCursor(oCodeMirror, mQuery);
                        if (!(oMatch = oCursor.findNext()) 
                            || (oStart 
                                && oCursor.from().line == oStart.line 
                                && oCursor.from().ch == oStart.ch
                               )
                           ) 
                            return;
                    }
                    
                    oCodeMirror.setSelection(oCursor.from(), oCursor.to());
                    oCodeMirror.scrollIntoView({from: oCursor.from(), to: oCursor.to()});
                    
                    //this.replaceAll(mQuery, this.sSearchQueryText);
                    oCursor.replace(
                        typeof mQuery == "string" ? 
                        oThis.sSearchQueryText :
                        oThis.sSearchQueryText.replace(/\$(\d)/g, function(_, i) {return oMatch[i];})
                    );
                    //fnAdvance();
                };
                fnAdvance();
            }
        },
        fnShowRegExpHelp: function()
        {
            this.$refs.help_modal.show();
        },
        
        fnTranslate: function()
        {
            var oCodeMirror = this.oSimpleMDE.codemirror;
            var oThis = this;
            
            if (oCodeMirror.getOption("readOnly")) return;
            
            this
                .$http
                .post(
                    '',
                    {
                        action: 'translate_text',
                        provider: this.sTranslationProvider,
                        text: oCodeMirror.getSelection(),
                        from_laguage: this.sTranslationFromLanguage,
                        to_language: this.sTranslationToLanguage
                    }
                ).then(function(oResponse)
                {
                    if (oResponse.body.status=='error') {
                        this.$snotify.error(oResponse.body.message, 'Error');
                        return;
                    }
                    
                    oCodeMirror.replaceSelection(oResponse.body.data);
                }).catch(function(sError)
                {
                    this.$snotify.error(sError);
                });
        },
        fnTranslationProviderChange: function(sValue)
        {
            console.log(sValue);
            localStorage.setItem(this.oRepository.sName+'_sTranslationProvider', sValue);
        },
        fnTranslationFromLanguageChange: function(sValue)
        {
            console.log(sValue);
            localStorage.setItem(this.oRepository.sName+'_sTranslationFromLanguage', sValue);
        },
        fnTranslationToLanguageChange: function(sValue)
        {
            console.log(sValue);
            localStorage.setItem(this.oRepository.sName+'_sTranslationToLanguage', sValue);
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
                        oThis.sUploadImagesMode = 'insert';
                        oThis.$refs.uploaded_images_input.$el.click();
                    },
                    className: "fa fa-file-image-o",
                    title: "Insert local picture"
                },
                {
                    name: "insert-picture-from-collection",
                    action: function(oEditor)
                    {
                        oThis.sUploadImagesMode = 'update-modal';
                        oThis.$refs.images_modal.hideFooter = false;
                        oThis.$refs.images_modal.show();
                    },
                    className: "fa fa-picture-o",
                    title: "Insert local picture"
                },
                {
                    name: "insert-files-from-collection",
                    action: function(oEditor)
                    {
                        oThis.sUploadFilesMode = 'update-modal';
                        oThis.$refs.files_modal.hideFooter = false;
                        oThis.$refs.files_modal.show();
                    },
                    className: "fa fa-file-o",
                    title: "Insert file from collection"
                },
                {
                    name: "insert-youtube-video",
                    action: function(oEditor)
                    {
                        oThis.sYoutubeVideoURL = '';
                        oThis.$refs.add_youtube_video_modal.hideFooter = false;
                        oThis.$refs.add_youtube_video_modal.show();
                    },
                    className: "fa fa-youtube-play",
                    title: "Insert youtube video"
                },
                {
                    name: "replace-text",
                    action: function(oEditor)
                    {
                        var oCodeMirror = oThis.oSimpleMDE.codemirror;
                        
                        if (!oThis.bShowReplacementBlock) {
                            oThis.sSearchQuery = oCodeMirror.getSelection();
                        }
                        
                        oThis.bShowTranslationBlock = false;
                        oThis.bShowReplacementBlock = !oThis.bShowReplacementBlock;
                        
                        setTimeout(
                            function()
                            {
                                if (oThis.$refs.replacable_text_input.$el) {
                                    oThis.$refs.replacable_text_input.$el.focus();
                                }
                            }, 
                            300
                        );
                    },
                    className: "fa fa-refresh",
                    title: "Replace text"
                },
                {
                    name: "translate-text",
                    action: function(oEditor)
                    {
                        oThis.bShowReplacementBlock = false;
                        oThis.bShowTranslationBlock = !oThis.bShowTranslationBlock;
                    },
                    className: "fa fa-language",
                    title: "Translate text"
                }
            ]
        });
        
        this.oSimpleMDE.codemirror.on('change', function(oCodeMirror) {
            console.log('codemirror - onchange');
            oThis.bEditorDirty = true;
        });
        
        this.oSimpleMDE.codemirror.on('paste', function(oCodeMirror, oEvent) {
            console.log('codemirror - paste');
            
            var oClipboardData = (oEvent.clipboardData || window.clipboardData);
            var sText = oTurndownService.turndown(oClipboardData.getData('text/html'));
            
            var oLinksMatch;
            var oURLMatch;
            
            if ((oLinksMatch = sText.match(/!\[[^\]]*\]\([^)]*\)/gu)) !== null) {
                var aURLs = [];
                
                for (var iIndex=0; iIndex<oLinksMatch.length; iIndex++) {
                    if ((oURLMatch = oLinksMatch[iIndex].match(/\((https?:.*?)\)/u)) !== null) {
                        aURLs.push(oURLMatch[1]);
                    }
                }
                
                window.oApplication.bShowLoadingScreen = true;
                
                oThis
                    .$http
                    .post(
                        '',
                        {
                            action: 'add_images_from_urls',
                            repository: oThis.oRepository.sName,
                            urls: aURLs
                        }
                    ).then(function(oResponse)
                    {
                        window.oApplication.bShowLoadingScreen = false;
                        
                        if (oResponse.body.status=='error') {
                            oThis.$snotify.error(oResponse.body.message, 'Error');
                            return;
                        }

                        for (var iDataIndex=0; iDataIndex<oResponse.body.data.length; iDataIndex++) {
                            for (var iIndex=0; iIndex<oLinksMatch.length; iIndex++) {
                                if (oLinksMatch[iIndex].indexOf(oResponse.body.data[iDataIndex]) !== -1) {
                                    var sNewLink = oLinksMatch[iIndex].replace(/\(https?:.*?\)/u, "(/images/"+oResponse.body.data[iDataIndex]+")");
                                    sText = sText.replace(oLinksMatch[iIndex], sNewLink);
                                    break;
                                }
                            }
                        }
                        
                        oCodeMirror.replaceSelection(sText);
                    })
                    .catch(function(sError)
                    {
                        oThis.$snotify.error(sError);
                    });
            }
            
            oThis.bEditorDirty = true;
            oEvent.preventDefault();
        });
        
        oThis.fnSelectTag(localStorage.getItem(this.oRepository.sName+'_sActiveTag'));
        oThis.fnSelectArticleWithName(localStorage.getItem(this.oRepository.sName+'_iActiveArticle'));
        
        var sTranslationProvider = localStorage.getItem(this.oRepository.sName+'_sTranslationProvider');
        if (sTranslationProvider) 
            this.sTranslationProvider = sTranslationProvider;
        var sTranslationFromLanguage = localStorage.getItem(this.oRepository.sName+'_sTranslationFromLanguage');
        if (sTranslationFromLanguage) 
            this.sTranslationFromLanguage = sTranslationFromLanguage;
        var sTranslationToLanguage = localStorage.getItem(this.oRepository.sName+'_sTranslationToLanguage');
        if (sTranslationToLanguage) 
            this.sTranslationToLanguage = sTranslationToLanguage;
        
        (function(CodeMirror) 
        {
            var Pos = CodeMirror.Pos;
                
              function regexpFlags(regexp) {
                var flags = regexp.flags
                return flags != null ? flags : (regexp.ignoreCase ? "i" : "")
                  + (regexp.global ? "g" : "")
                  + (regexp.multiline ? "m" : "")
              }

              function ensureFlags(regexp, flags) {
                var current = regexpFlags(regexp), target = current
                for (var i = 0; i < flags.length; i++) if (target.indexOf(flags.charAt(i)) == -1)
                  target += flags.charAt(i)
                return current == target ? regexp : new RegExp(regexp.source, target)
              }

              function maybeMultiline(regexp) {
                return /\\s|\\n|\n|\\W|\\D|\[\^/.test(regexp.source)
              }

              function searchRegexpForward(doc, regexp, start) {
                regexp = ensureFlags(regexp, "g")
                for (var line = start.line, ch = start.ch, last = doc.lastLine(); line <= last; line++, ch = 0) {
                  regexp.lastIndex = ch
                  var string = doc.getLine(line), match = regexp.exec(string)
                  if (match)
                    return {from: Pos(line, match.index),
                            to: Pos(line, match.index + match[0].length),
                            match: match}
                }
              }

              function searchRegexpForwardMultiline(doc, regexp, start) {
                if (!maybeMultiline(regexp)) return searchRegexpForward(doc, regexp, start)

                regexp = ensureFlags(regexp, "gm")
                var string, chunk = 1
                for (var line = start.line, last = doc.lastLine(); line <= last;) {
                  // This grows the search buffer in exponentially-sized chunks
                  // between matches, so that nearby matches are fast and don't
                  // require concatenating the whole document (in case we're
                  // searching for something that has tons of matches), but at the
                  // same time, the amount of retries is limited.
                  for (var i = 0; i < chunk; i++) {
                    if (line > last) break
                    var curLine = doc.getLine(line++)
                    string = string == null ? curLine : string + "\n" + curLine
                  }
                  chunk = chunk * 2
                  regexp.lastIndex = start.ch
                  var match = regexp.exec(string)
                  if (match) {
                    var before = string.slice(0, match.index).split("\n"), inside = match[0].split("\n")
                    var startLine = start.line + before.length - 1, startCh = before[before.length - 1].length
                    return {from: Pos(startLine, startCh),
                            to: Pos(startLine + inside.length - 1,
                                    inside.length == 1 ? startCh + inside[0].length : inside[inside.length - 1].length),
                            match: match}
                  }
                }
              }

              function lastMatchIn(string, regexp) {
                var cutOff = 0, match
                for (;;) {
                  regexp.lastIndex = cutOff
                  var newMatch = regexp.exec(string)
                  if (!newMatch) return match
                  match = newMatch
                  cutOff = match.index + (match[0].length || 1)
                  if (cutOff == string.length) return match
                }
              }

              function searchRegexpBackward(doc, regexp, start) {
                regexp = ensureFlags(regexp, "g")
                for (var line = start.line, ch = start.ch, first = doc.firstLine(); line >= first; line--, ch = -1) {
                  var string = doc.getLine(line)
                  if (ch > -1) string = string.slice(0, ch)
                  var match = lastMatchIn(string, regexp)
                  if (match)
                    return {from: Pos(line, match.index),
                            to: Pos(line, match.index + match[0].length),
                            match: match}
                }
              }

              function searchRegexpBackwardMultiline(doc, regexp, start) {
                regexp = ensureFlags(regexp, "gm")
                var string, chunk = 1
                for (var line = start.line, first = doc.firstLine(); line >= first;) {
                  for (var i = 0; i < chunk; i++) {
                    var curLine = doc.getLine(line--)
                    string = string == null ? curLine.slice(0, start.ch) : curLine + "\n" + string
                  }
                  chunk *= 2

                  var match = lastMatchIn(string, regexp)
                  if (match) {
                    var before = string.slice(0, match.index).split("\n"), inside = match[0].split("\n")
                    var startLine = line + before.length, startCh = before[before.length - 1].length
                    return {from: Pos(startLine, startCh),
                            to: Pos(startLine + inside.length - 1,
                                    inside.length == 1 ? startCh + inside[0].length : inside[inside.length - 1].length),
                            match: match}
                  }
                }
              }

              var doFold, noFold
              if (String.prototype.normalize) {
                doFold = function(str) { return str.normalize("NFD").toLowerCase() }
                noFold = function(str) { return str.normalize("NFD") }
              } else {
                doFold = function(str) { return str.toLowerCase() }
                noFold = function(str) { return str }
              }

              // Maps a position in a case-folded line back to a position in the original line
              // (compensating for codepoints increasing in number during folding)
              function adjustPos(orig, folded, pos, foldFunc) {
                if (orig.length == folded.length) return pos
                for (var min = 0, max = pos + Math.max(0, orig.length - folded.length);;) {
                  if (min == max) return min
                  var mid = (min + max) >> 1
                  var len = foldFunc(orig.slice(0, mid)).length
                  if (len == pos) return mid
                  else if (len > pos) max = mid
                  else min = mid + 1
                }
              }

              function searchStringForward(doc, query, start, caseFold) {
                // Empty string would match anything and never progress, so we
                // define it to match nothing instead.
                if (!query.length) return null
                var fold = caseFold ? doFold : noFold
                var lines = fold(query).split(/\r|\n\r?/)

                search: for (var line = start.line, ch = start.ch, last = doc.lastLine() + 1 - lines.length; line <= last; line++, ch = 0) {
                  var orig = doc.getLine(line).slice(ch), string = fold(orig)
                  if (lines.length == 1) {
                    var found = string.indexOf(lines[0])
                    if (found == -1) continue search
                    var start = adjustPos(orig, string, found, fold) + ch
                    return {from: Pos(line, adjustPos(orig, string, found, fold) + ch),
                            to: Pos(line, adjustPos(orig, string, found + lines[0].length, fold) + ch)}
                  } else {
                    var cutFrom = string.length - lines[0].length
                    if (string.slice(cutFrom) != lines[0]) continue search
                    for (var i = 1; i < lines.length - 1; i++)
                      if (fold(doc.getLine(line + i)) != lines[i]) continue search
                    var end = doc.getLine(line + lines.length - 1), endString = fold(end), lastLine = lines[lines.length - 1]
                    if (endString.slice(0, lastLine.length) != lastLine) continue search
                    return {from: Pos(line, adjustPos(orig, string, cutFrom, fold) + ch),
                            to: Pos(line + lines.length - 1, adjustPos(end, endString, lastLine.length, fold))}
                  }
                }
              }

              function searchStringBackward(doc, query, start, caseFold) {
                if (!query.length) return null
                var fold = caseFold ? doFold : noFold
                var lines = fold(query).split(/\r|\n\r?/)

                search: for (var line = start.line, ch = start.ch, first = doc.firstLine() - 1 + lines.length; line >= first; line--, ch = -1) {
                  var orig = doc.getLine(line)
                  if (ch > -1) orig = orig.slice(0, ch)
                  var string = fold(orig)
                  if (lines.length == 1) {
                    var found = string.lastIndexOf(lines[0])
                    if (found == -1) continue search
                    return {from: Pos(line, adjustPos(orig, string, found, fold)),
                            to: Pos(line, adjustPos(orig, string, found + lines[0].length, fold))}
                  } else {
                    var lastLine = lines[lines.length - 1]
                    if (string.slice(0, lastLine.length) != lastLine) continue search
                    for (var i = 1, start = line - lines.length + 1; i < lines.length - 1; i++)
                      if (fold(doc.getLine(start + i)) != lines[i]) continue search
                    var top = doc.getLine(line + 1 - lines.length), topString = fold(top)
                    if (topString.slice(topString.length - lines[0].length) != lines[0]) continue search
                    return {from: Pos(line + 1 - lines.length, adjustPos(top, topString, top.length - lines[0].length, fold)),
                            to: Pos(line, adjustPos(orig, string, lastLine.length, fold))}
                  }
                }
              }

              function SearchCursor(doc, query, pos, options) {
                this.atOccurrence = false
                this.doc = doc
                pos = pos ? doc.clipPos(pos) : Pos(0, 0)
                this.pos = {from: pos, to: pos}

                var caseFold
                if (typeof options == "object") {
                  caseFold = options.caseFold
                } else { // Backwards compat for when caseFold was the 4th argument
                  caseFold = options
                  options = null
                }

                if (typeof query == "string") {
                  if (caseFold == null) caseFold = false
                  this.matches = function(reverse, pos) {
                    return (reverse ? searchStringBackward : searchStringForward)(doc, query, pos, caseFold)
                  }
                } else {
                  query = ensureFlags(query, "gm")
                  if (!options || options.multiline !== false)
                    this.matches = function(reverse, pos) {
                      return (reverse ? searchRegexpBackwardMultiline : searchRegexpForwardMultiline)(doc, query, pos)
                    }
                  else
                    this.matches = function(reverse, pos) {
                      return (reverse ? searchRegexpBackward : searchRegexpForward)(doc, query, pos)
                    }
                }
              }

              SearchCursor.prototype = {
                findNext: function() {return this.find(false)},
                findPrevious: function() {return this.find(true)},

                find: function(reverse) {
                  var result = this.matches(reverse, this.doc.clipPos(reverse ? this.pos.from : this.pos.to))

                  // Implements weird auto-growing behavior on null-matches for
                  // backwards-compatiblity with the vim code (unfortunately)
                  while (result && CodeMirror.cmpPos(result.from, result.to) == 0) {
                    if (reverse) {
                      if (result.from.ch) result.from = Pos(result.from.line, result.from.ch - 1)
                      else if (result.from.line == this.doc.firstLine()) result = null
                      else result = this.matches(reverse, this.doc.clipPos(Pos(result.from.line - 1)))
                    } else {
                      if (result.to.ch < this.doc.getLine(result.to.line).length) result.to = Pos(result.to.line, result.to.ch + 1)
                      else if (result.to.line == this.doc.lastLine()) result = null
                      else result = this.matches(reverse, Pos(result.to.line + 1, 0))
                    }
                  }

                  if (result) {
                    this.pos = result
                    this.atOccurrence = true
                    return this.pos.match || true
                  } else {
                    var end = Pos(reverse ? this.doc.firstLine() : this.doc.lastLine() + 1, 0)
                    this.pos = {from: end, to: end}
                    return this.atOccurrence = false
                  }
                },

                from: function() {if (this.atOccurrence) return this.pos.from},
                to: function() {if (this.atOccurrence) return this.pos.to},

                replace: function(newText, origin) {
                  if (!this.atOccurrence) return
                  var lines = CodeMirror.splitLines(newText)
                  this.doc.replaceRange(lines, this.pos.from, this.pos.to, origin)
                  this.pos.to = Pos(this.pos.from.line + lines.length - 1,
                                    lines[lines.length - 1].length + (lines.length == 1 ? this.pos.from.ch : 0))
                }
              }

              CodeMirror.defineExtension("getSearchCursor", function(query, pos, caseFold) {
                return new SearchCursor(this.doc, query, pos, caseFold)
              })
              CodeMirror.defineDocExtension("getSearchCursor", function(query, pos, caseFold) {
                return new SearchCursor(this, query, pos, caseFold)
              })

              CodeMirror.defineExtension("selectMatches", function(query, caseFold) {
                var ranges = []
                var cur = this.getSearchCursor(query, this.getCursor("from"), caseFold)
                while (cur.findNext()) {
                  if (CodeMirror.cmpPos(cur.to(), this.getCursor("to")) > 0) break
                  ranges.push({anchor: cur.from(), head: cur.to()})
                }
                if (ranges.length)
                  this.setSelections(ranges, 0)
              })        
        
        })(this.oSimpleMDE.CodeMirror);        
    },
    
    created: function()
    {
        console.log('repositories tab created');
    }
};

</script>
