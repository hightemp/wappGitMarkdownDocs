// Use strict mode (https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Strict_mode)
"use strict";


// Requires
var Typo = require("typo-js");


// Create function
function CodeMirrorSpellChecker(options) {
	// Initialize
	options = options || {};


	// Verify
	if(typeof options.codeMirrorInstance !== "function" || typeof options.codeMirrorInstance.defineMode !== "function") {
		console.log("CodeMirror Spell Checker: You must provide an instance of CodeMirror via the option `codeMirrorInstance`");
		return;
	}


	// Because some browsers don't support this functionality yet
	if(!String.prototype.includes) {
		String.prototype.includes = function() {
			"use strict";
			return String.prototype.indexOf.apply(this, arguments) !== -1;
		};
	}


	// Define the new mode
	options.codeMirrorInstance.defineMode("spell-checker", function(config) {
                
                console.log("spell-checker init");
                
                if (!window.CodeMirrorSpellChecker) {
                    window.CodeMirrorSpellChecker = CodeMirrorSpellChecker;
                }
                
                if (options.oData) {
                    for (var sLang in options.oData) {
                        if (!window.CodeMirrorSpellChecker.typo[sLang]) {
                            CodeMirrorSpellChecker.typo[sLang] =  new Typo(
                                sLang, 
                                options.oData[sLang].aff_data, 
                                options.oData[sLang].dic_data, 
                                {
                                    platform: "any"
                                }
                            );
                        } else {
                            CodeMirrorSpellChecker.typo[sLang] = window.CodeMirrorSpellChecker.typo[sLang];
                        }
                    }
                } else {
                    for (var sLang in CodeMirrorSpellChecker.aff_data) {
                        if (!window.CodeMirrorSpellChecker.typo[sLang]) {
                            CodeMirrorSpellChecker.typo[sLang] =  new Typo(
                                sLang, 
                                CodeMirrorSpellChecker.aff_data[sLang], 
                                CodeMirrorSpellChecker.dic_data[sLang], 
                                {
                                    platform: "any"
                                }
                            );
                        } else {
                            CodeMirrorSpellChecker.typo[sLang] = window.CodeMirrorSpellChecker.typo[sLang];
                        }
                    }
                }
                
                /*
		// Load AFF/DIC data
		if(!CodeMirrorSpellChecker.aff_loading) {
			CodeMirrorSpellChecker.aff_loading = true;
			var xhr_aff = new XMLHttpRequest();
			xhr_aff.open("GET", "https://cdn.jsdelivr.net/codemirror.spell-checker/latest/en_US.aff", true);
			xhr_aff.onload = function() {
				if(xhr_aff.readyState === 4 && xhr_aff.status === 200) {
					CodeMirrorSpellChecker.aff_data = xhr_aff.responseText;
					CodeMirrorSpellChecker.num_loaded++;

					if(CodeMirrorSpellChecker.num_loaded == 2) {
						CodeMirrorSpellChecker.typo = new Typo("en_US", CodeMirrorSpellChecker.aff_data, CodeMirrorSpellChecker.dic_data, {
							platform: "any"
						});
					}
				}
			};
			xhr_aff.send(null);
		}

		if(!CodeMirrorSpellChecker.dic_loading) {
			CodeMirrorSpellChecker.dic_loading = true;
			var xhr_dic = new XMLHttpRequest();
			xhr_dic.open("GET", "https://cdn.jsdelivr.net/codemirror.spell-checker/latest/en_US.dic", true);
			xhr_dic.onload = function() {
				if(xhr_dic.readyState === 4 && xhr_dic.status === 200) {
					CodeMirrorSpellChecker.dic_data = xhr_dic.responseText;
					CodeMirrorSpellChecker.num_loaded++;

					if(CodeMirrorSpellChecker.num_loaded == 2) {
						CodeMirrorSpellChecker.typo = new Typo("en_US", CodeMirrorSpellChecker.aff_data, CodeMirrorSpellChecker.dic_data, {
							platform: "any"
						});
					}
				}
			};
			xhr_dic.send(null);
		}
                */

		// Define what separates a word
		var rx_word = "!\"#$%&()*+,-./:;<=>?@[\\]^_`{|}~ ";


		// Create the overlay and such
		var overlay = {
			token: function(stream) {
				var ch = stream.peek();
				var word = "";

				if(rx_word.includes(ch)) {
					stream.next();
					return null;
				}

				while((ch = stream.peek()) != null && !rx_word.includes(ch)) {
					word += ch;
					stream.next();
				}

                                for (var sLang in CodeMirrorSpellChecker.typo) {
                                    if (CodeMirrorSpellChecker.typo[sLang] && CodeMirrorSpellChecker.typo[sLang].check(word)) {
                                        return null;
                                    }
                                    /*
                                    if(CodeMirrorSpellChecker.typo && !CodeMirrorSpellChecker.typo.check(word))
					return "spell-error"; // CSS class: cm-spell-error
                                    */
                                }
                                return "spell-error";
				//return null;
			}
		};

		var mode = options.codeMirrorInstance.getMode(
			config, config.backdrop || "text/plain"
		);

		return options.codeMirrorInstance.overlayMode(mode, overlay, true);
	});
}


// Initialize data globally to reduce memory consumption
CodeMirrorSpellChecker.num_loaded = 0;
CodeMirrorSpellChecker.aff_loading = {
    en_US: false,
    ru_RU: false
};
CodeMirrorSpellChecker.dic_loading = {
    en_US: false,
    ru_RU: false
};
CodeMirrorSpellChecker.aff_data = {
    en_US: require("!raw-loader!../data/en_US.aff").default,
    ru_RU: require("!raw-loader!../data/ru_RU.aff").default
};
CodeMirrorSpellChecker.dic_data = {
    en_US: require("!raw-loader!../data/en_US.dic").default,
    ru_RU: require("!raw-loader!../data/ru_RU.dic").default
};
CodeMirrorSpellChecker.typo = {
    en_US: null,
    ru_RU: null
};


// Export
module.exports = CodeMirrorSpellChecker;