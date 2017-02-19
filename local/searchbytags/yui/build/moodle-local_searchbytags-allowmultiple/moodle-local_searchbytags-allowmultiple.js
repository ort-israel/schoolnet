YUI.add('moodle-local_searchbytags-allowmultiple', function (Y, NAME) {


// this must hook .searchoptions in order to override the onChange event handler for searchoptions.
var SELECTORS = {
        OPTIONS: '.searchoptions',
        OURFIELDS: '.searchbytags'
    };

M.local_searchbytags = M.local_searchbytags || {};
M.local_searchbytags.allowmultiple = {
    init: function () {
        Y.delegate('change', this.option_changed, Y.config.doc, SELECTORS.OPTIONS, this);
        Y.delegate('keyup', this.keyUp, Y.config.doc, SELECTORS.OURFIELDS, this);
        Y.delegate('keydown', this.keyDown, Y.config.doc, SELECTORS.OURFIELDS, this);
        Y.delegate('mouseup', this.mouseUp, Y.config.doc, SELECTORS.OURFIELDS, this);
        Y.delegate('mousedown', this.mouseDown, Y.config.doc, SELECTORS.OURFIELDS, this);
        Y.delegate('click', this.click, Y.config.doc, SELECTORS.OURFIELDS, this);
    },

    option_changed: function (e) {
        if (!e.target.hasClass('searchbytags')) {
            // console.log('target is not .searchbytags');
            return true;
        }
        // console.log('option_changed, multiselect is ' + this.multiselect);
        if (e.ctrlKey || this.multiselect) {
            e.preventDefault();
            return false;
        } else {
            return true;
        }
    },

    keyDown: function (e) {
        // Shift, ctrl or command key means they may select more than one, firing more than one change event.
        if (this.isModifierKey(e)) {
            this.multiselect = true;
        }
        // console.log('keyDown, multiselect is ' + this.multiselect);
    },

    // When the shift or ctrl key is released, trigger the change event.
    keyUp: function (e) {
       if (this.isModifierKey(e)) {
            this.multiselect = false;
            Y.one('#menutags').simulate('change');
        }
        // console.log('keyUp, multiselect is ' + this.multiselect);
        // console.log('keyUp, which: ' + e.which);
    },

    mouseDown: function (e) {
        if (e.ctrlKey || e.shiftKey || e.metaKey) {
            this.multiselect = true;
        } else {
            this.multiselect = false;
        }
        // console.log('mouseDown, multiselect is ' + this.multiselect);
    },

    mouseUp: function (e) {
        if (e.ctrlKey || e.shiftKey || e.metaKey) {
            this.multiselect = true;
        } else {
            this.multiselect = false;
        }
        // console.log('mouseUp, multiselect is ' + this.multiselect);
    },

    click: function (e) {
        // debugger;
        if (e.ctrlKey || e.shiftKey) {
            this.multiselect = true;
        } else {
            this.multiselect = false;
        }
        // console.log('click, multiselect is ' + this.multiselect);
    },

    isModifierKey: function(e) {
        // The Command key, used like ctrl on Mac, can have three different key codes in different browsers, plus Windows has ctrl.
        var shiftCtrlCmd = [16,17,91,93,224];
        return shiftCtrlCmd.indexOf(e.which) + 1;
    }
};



}, '@VERSION@', {"requires": ["base", "node", "node-event-simulate"]});
