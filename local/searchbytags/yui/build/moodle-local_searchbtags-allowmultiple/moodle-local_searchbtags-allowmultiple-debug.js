YUI.add('moodle-local_searchbtags-allowmultiple', function (Y, NAME) {


debugger;

var SELECTORS = {
        OPTIONS: '.menutags'
    },
    NS;

M.local_searchbytags = M.local_searchbytags || {};
M.local_searchbytags.allowmultiple = {};
NS = M.local_searchbytags.allowmultiple;

NS.init = function () {
    debugger;
    Y.delegate('change', this.option_changed, Y.config.doc, SELECTORS.OPTIONS, this);
    Y.delegate('keyup', this.keyDown, Y.config.doc, SELECTORS.OPTIONS, this);
    Y.delegate('keydown', this.keyUp, Y.config.doc, SELECTORS.OPTIONS, this);
};

NS.option_changed = function (e) {
    if (e.ctrlKey || e.shiftKey || this.multiselect){
        e.preventDefault;
        return false;
    } else {
        return true;
    }
};

NS.keyDown = function (e) {
    debugger;
    if (e.ctrlKey || e.shiftKey) {
        this.multiselect = true;
    }
};

NS.keyUp = function (e) {
    debugger;
    if (e.ctrlKey || e.shiftKey) {
        this.multiselect = false;
    }
};




}, '@VERSION@');
