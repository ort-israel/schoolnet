YUI.add('moodle-atto_image-button', function (Y, NAME) {

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/*
 * @package    atto_image
 * @copyright  2016 Joey Andres <jandres@ualberta.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Note: atto-image-helper-* classes are removed.

Y.namespace('M.atto_image');
// Copied from Y.Resize.
Y.M.atto_image.resizeHandles = {
    T: 't',
    TR: 'tr',
    R: 'r',
    BR: 'br',
    B: 'b',
    BL: 'bl',
    L: 'l',
    TL: 'tl'
};
Y.M.atto_image.imgWrapperTemplate = '<div class="atto-image-wrapper" contenteditable="false"></div>';
Y.M.atto_image.imageEditableClass = 'atto-image-helper-editable';
Y.M.atto_image.resizeOverlayNodeTemplate = '<div class="atto-image-resize-overlay atto_control {{classes}}"></div>';
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/*
 * @package    atto_image
 * @copyright  2015 Joey Andres <jandres@ualberta.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * This is where utility functions are placed so they can be modified easily.
 *
 * @module moodle-atto_image-utility
 */

Y.M.atto_image.utility = {
    /**
     * A helper function for parsing string to base 10 and avoiding jsling/shifter complains about having no radix.
     *
     * @param {String|Number} val
     * @returns {Number}
     */
    parseInt10: function(val) {
        return parseInt(val, 10);
    },

    /**
     * A helper function for getting the natural image size prior to any html attributes and css styling.
     *
     * @param {string} src Source of the image.
     * @return {{width: {Number}, height: {Number}}} The object containing width and height.
     */
    getNaturalImageSize: function(src) {
        var img = new Image();
        img.src = src;
        return {width: img.width, height: img.height};
    },

    /**
     * Acquires the DOM node size without the border and margin.
     *
     * @param {Y.Node} node The to acquire size.
     * @return {{width: {Number}, height: {Number}}} Width and height without the border and margin.
     */
    getNodeSize: function(node) {
        var newWidth =
            Y.M.atto_image.utility.parseInt10(node.getComputedStyle("width")) -
            Y.M.atto_image.utility.getHorizontalNonContentWidth(node);
        var newHeight =
            Y.M.atto_image.utility.parseInt10(node.getComputedStyle("height")) -
            Y.M.atto_image.utility.getVerticalNonContentWidth(node);
        return {width: newWidth, height: newHeight};
    },

    /**
     * A helper function for getting the approximate aspect ratio.
     *
     * @param {{width: {Number}, height: {Number}}} size of the image to acquire aspect ratio of.
     * @returns {number} aspect ratio approximation.
     */
    getNaturalImageAspectRatio: function(size) {
        // We need imageSizeMultiplier so that when we divide, we get more precision for our aspect ratio approximation.
        var imageSizeMultiplier = 1000;
        return (size.width * imageSizeMultiplier) / (size.height * imageSizeMultiplier);
    },

    /**
     * @param {Y.Node} node to acquire the total horizontal border.
     * @returns {Number} Total horizontal border in px.
     */
    getHorizontalBorderWidth: function(node) {
        var borderLeftWidth = Y.M.atto_image.utility.parseInt10(node.getComputedStyle("border-left-width"));
        var borderRightWidth = Y.M.atto_image.utility.parseInt10(node.getComputedStyle("border-right-width"));
        return borderLeftWidth + borderRightWidth;
    },

    /**
     * @param {Y.Node} node to acquire the total vertical border.
     * @returns {Number} Total vertical border in px.
     */
    getVerticalBorderWidth: function(node) {
        var borderTopWidth = Y.M.atto_image.utility.parseInt10(node.getComputedStyle("border-top-width"));
        var borderBottomWidth = Y.M.atto_image.utility.parseInt10(node.getComputedStyle("border-bottom-width"));
        return borderTopWidth + borderBottomWidth;

    },

    /**
     * @param {Y.Node} node to acquire the total horizontal padding.
     * @returns {Number} Total horizontal border in px.
     */
    getHorizontalPaddingWidth: function(node) {
        var paddingLeft = Y.M.atto_image.utility.parseInt10(node.getComputedStyle("padding-left"));
        var paddingRight = Y.M.atto_image.utility.parseInt10(node.getComputedStyle("padding-right"));
        return paddingLeft + paddingRight;
    },

    /**
     * @param {Y.Node} node to acquire the total vertical padding.
     * @returns {Number} Total vertical border in px.
     */
    getVerticalPaddingWidth: function(node) {
        var paddingBottom = Y.M.atto_image.utility.parseInt10(node.getComputedStyle("padding-bottom"));
        var paddingTop = Y.M.atto_image.utility.parseInt10(node.getComputedStyle("padding-top"));
        return paddingBottom + paddingTop;
    },

    /**
     * @param {Y.Node} node to acquire the total non-content (border+padding) width .
     * @returns {Number} Total horizontal non-content in px.
     *
     * Note: Margin is not part of this, since by def'n, margin is outside box-model.
     */
    getHorizontalNonContentWidth: function(node) {
        return this.getHorizontalBorderWidth(node) + this.getHorizontalPaddingWidth(node);
    },

    /**
     * @param {Y.Node} node to acquire the total non-content (border+padding) height.
     * @returns {Number} Total vertical non-content in px.
     *
     * Note: Margin is not part of this, since by def'n, margin is outside box-model.
     */
    getVerticalNonContentWidth: function(node) {
        return this.getVerticalBorderWidth(node) + this.getVerticalPaddingWidth(node);
    },

    /**
     * Compares two rangy object if their selection is the same.
     *
     * @param {rangy.Range} rangy1
     * @param {rangy.Range} rangy2
     * @return {boolean} True if the selection they range represents are equal.
     */
    rangyCompare: function(rangy1, rangy2) {
        return (
            (rangy1.startContainer == rangy2.startContainer) &&
            (rangy1.endContainer == rangy2.endContainer) &&
            (rangy1.startOffset == rangy2.startOffset) &&
            (rangy1.endOffset == rangy2.endOffset)
        );
    },

    /**
     * Disable custom contenteditable features on img.
     *
     * @param {Y.Node} editorNode
     */
    disableContentEditable: function(editorNode) {
        editorNode.all('.atto-image-wrapper').each(function(node) {
            node.getDOMNode().setAttribute('contenteditable', false);
        });

        // Disable IE's custom contenteditable features on img.
        editorNode.all('img').each(function(imgNode) {
            imgNode.getDOMNode().setAttribute('unselectable', 'on');
        });
    },

    /**
     * Gets the mapping of inline style and its value.
     * @param {Y.Node} node
     * @returns {{}} Mapping of all inline style and its value.
     */
    getInlineStyles: function(node) {
        var inlineStyleString = node.getDOMNode().getAttribute('style');

        if (Y.Lang.isString(inlineStyleString) === false || inlineStyleString === '') {
            return {};
        }

        var inlineStyleArray = inlineStyleString.split(';');
        inlineStyleArray = inlineStyleArray.filter(function(style) {
           return style.indexOf(':') !== -1;
        });

        var styleObj = {};
        inlineStyleArray.forEach(function(style) {
            var styleProperty = style.trim().split(':');
            styleObj[styleProperty[0]] = styleProperty[1];
        });

        return styleObj;
    },

    /**
     * Sets the inline style attribute of the given node.
     * @param {Y.Node} node The node to set the inline style of.
     * @param {{}} styleMap The styleMap to set as inline style of the given node.
     */
    setInlineStyles: function(node, styleMap) {
        var inlineStyleArray = [];

        for (var style in styleMap) {
            inlineStyleArray.push(style + ': ' + styleMap[style]);
        }

        node.getDOMNode().setAttribute('style', inlineStyleArray.join('; '));
    },

    /**
     * Remove all the invalid styles of the given node with respect to an array of "valid styles".
     * @param {Y.Node} node The node to set the inline style of.
     * @param {Array} validInlineStyles Valid styles to keep.
     */
    cleanupInlineStyles: function(node, validInlineStyles) {
        var oldInlineStyles = Y.M.atto_image.utility.getInlineStyles(node);
        var newInlineStyles = {};
        for (var style in oldInlineStyles) {
            if (validInlineStyles.indexOf(style) >= 0) {
                newInlineStyles[style] = oldInlineStyles[style];
            }
        }
        Y.M.atto_image.utility.setInlineStyles(node, newInlineStyles);
    },

    /**
     * A function to get the current selected nodes from a given rangy object. This is from Tim Down himself,
     * the guy who created the rangy API.
     * @see http://stackoverflow.com/a/7784176
     *
     * @param {window.rangy} range Rangy object, from the rangy API.
     * @returns {[DOM]} Not necessarily a DOMElement, so check if Y.one returns null.
     */
    getRangeSelectedNodes: function(range) {
        var nextNode = function(node) {
            if (node.hasChildNodes()) {
                return node.firstChild;
            } else {
                while (node && !node.nextSibling) {
                    node = node.parentNode;
                }
                if (!node) {
                    return null;
                }
                return node.nextSibling;
            }
        };

        var node = range.startContainer;
        var endNode = range.endContainer;

        // Special case for a range that is contained within a single node
        if (node == endNode) {
            return [node];
        }

        // Iterate nodes until we hit the end container
        var rangeNodes = [];
        while (node && node != endNode) {
            rangeNodes.push(node = nextNode(node));
        }

        // Add partially selected nodes at the start of the range
        node = range.startContainer;
        while (node && node != range.commonAncestorContainer) {
            rangeNodes.unshift(node);
            node = node.parentNode;
        }

        return rangeNodes;
    }
};// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/*
 * @package    atto_image
 * @copyright  2016 Joey Andres <jandres@ualberta.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * MutationObserver is cool and all, but there are limits. For instance, we can't
 * watch the Y.Node itself, but only its children. Since watching the node AND node's children
 * is a common pattern for resize/crop/rotate, this module is built.
 *
 *     var crazyNode = Y.one('#foo');
 *     var options = {
 *       node: crazyNode,
 *       deletionCallback: function(removedNodes) {
 *         console.log('crazyNode or it's children have been deleted.');
 *       },
 *
 *       // Usual MutationObserver arguments from down here.
 *       childList: true,  // True by default. Ovverride to false if you want.
 *       subtree: true  // True by default. Override to false if you want.
 *     };
 *     var resizable = new Y.M.atto_image.resizable(node);
 *
 * @class Y.M.atto_image.DeleteMutationObserver
 */
Y.M.atto_image.DeleteMutationObserver = function() {
    Y.M.atto_image.DeleteMutationObserver.superclass.constructor.apply(this, arguments);
};
Y.extend(Y.M.atto_image.DeleteMutationObserver, Y.Base, {
    /**
     * The node being watched.
     *
     * @property node
     * @type {null|Y.Node}
     * @required
     * @default null
     * @writeOnce
     * @public
     */
    node: null,

    /**
     * The mutation observer that observe's this.node's mutation.
     *
     * @property _mutationObserver
     * @type {null|MutationObserver}
     * @default null
     * @private
     */
    _mutationObserver: null,

    /**
     * The mutation observer that observe's this.node's children's mutation.
     *
     * @property _childrenMutationObserver
     * @type {null|MutationObserver}
     * @default null
     * @private
     */
    _childrenMutationObserver: null,

    /**
     * @property _mutationObserverConfig
     * @type {Object}
     * @default {childList: true, subtree: true}
     * @private
     */
    _mutationObserverConfig: {childList: true, subtree: true},

    /**
     * @property _deletionCallback
     * @type {Function|null}
     * @default null
     * @private
     */
    _deletionCallback: null,

    /**
     * When a div with many children is deleted, multiple calls to _deletionCallback might be called. What if
     * the developer calls Y.M.atto_image.DeletionMutationObserver.stop() in first callback?
     * The remaining _deletionCallback (for other deleted DOM elements) are still going to be called.
     * To avoid this, this.stop() will set this._start flag to false thus avoiding further calls to _deletionCallback.
     *
     * @property _start
     * @type {Boolean}
     * @default false
     * @private
     */
    _start: false,

    initializer: function(cfg) {
        this.node = cfg.node;
        this._deletionCallback = cfg.deletionCallback;

        delete cfg.node;
        delete cfg.deletionCallback;
        // @see http://yuilibrary.com/yui/docs/api/classes/YUI.html#method_merge
        // "The properties from later objects will overwrite those in earlier objects."
        this._mutationObserverConfig = Y.merge(this._mutationObserverConfig, cfg);

        Y.log(
            'initialized',
            'debug',
            Y.M.atto_image.DeleteMutationObserver.NAME
        );
    },

    destructor: function() {
        this.detachAll();
        this.stop();
    },

    /**
     * Starts the MutationObserver instances.
     */
    start: function() {
        this._start = true;

        this._childrenMutationObserver = new MutationObserver(function(mutations) {
            var nodeNotSet = !this.node;

            // For now, to reduce error, just throw a debug warning when node not set so we reduce error rate. Wait
            // until developer sets the Y.M.atto_image.DeleteMutationObserver.node
            if (nodeNotSet) {
                Y.log(
                    'this.node not set causing problem in start method',
                    'debug',
                    Y.M.atto_image.DeleteMutationObserver.NAME
                );
                return;
            }

            mutations.forEach(function(mutation) {
                var deletionOccurred = mutation.removedNodes.length > 0;
                if (deletionOccurred && this._start) {
                    Y.log(
                        "node deletion occurred in node's children",
                        'debug',
                        Y.M.atto_image.DeleteMutationObserver.NAME
                    );
                    if (this._deletionCallback) {
                        this._deletionCallback(mutation.removedNodes);
                    }
                }
            }.bind(this));
        }.bind(this));
        this._childrenMutationObserver.observe(this.node.getDOMNode(), this._mutationObserverConfig);

        this._mutationObserver = new MutationObserver(function(mutations) {
            var nodeNotSet = !this.node;

            // For now, to reduce error, just throw a debug warning when node not set so we reduce error rate. Wait
            // until developer sets the Y.M.atto_image.DeleteMutationObserver.node
            if (nodeNotSet) {
                Y.log(
                    'this.node not set causing problem in start method',
                    'debug',
                    Y.M.atto_image.DeleteMutationObserver.NAME
                );
                return;
            }

            mutations.forEach(function(mutation) {
                var deletionOccurred = mutation.removedNodes.length > 0;
                if (deletionOccurred && this._start) {
                    if ([].indexOf.call(mutation.removedNodes, this.node.getDOMNode()) >= 0) {
                        Y.log(
                            'delete-mutation-observer',
                            'debug',
                            Y.M.atto_image.DeleteMutationObserver.NAME
                        );
                        if (this._deletionCallback) {
                            this._deletionCallback(mutation.removedNodes);
                        }
                    }
                }
            }.bind(this));
        }.bind(this));
        this._mutationObserver.observe(this.node.ancestor().getDOMNode(), this._mutationObserverConfig);
    },

    /**
     * Stops MutationObserver instances.
     */
    stop: function() {
        this._start = false;

        if (this._mutationObserver) {
            this._mutationObserver.disconnect();
            this._mutationObserver = null;
        }

        if (this._childrenMutationObserver) {
            this._childrenMutationObserver.disconnect();
            this._childrenMutationObserver = null;
        }
    }
}, {
    NAME: 'atto_image_DeleteMutationObserver',

    /**
     * See if DeleteMutationObserver is supported.
     *
     * @return {Boolean} true if supported in browser, false otherwise.
     */
    isSupported: function() {
        var supported = Y.Lang.isObject(window.MutationObserver);
        Y.log(
            supported ? 'supported' : 'not supported',
            'debug',
            Y.M.atto_image.DeleteMutationObserver.NAME
        );
        return supported;
    }
});// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/*
 * @package    atto_image
 * @copyright  2016 Joey Andres <jandres@ualberta.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Encapsulate the editable image.
 * This is aggregated by editable classes, e.g. Y.M.atto_image.resizable.
 *
 * @class Y.M.atto_image.EditableImg
 */
Y.M.atto_image.EditableImg = function() {
    Y.M.atto_image.EditableImg.superclass.constructor.apply(this, arguments);
};
Y.extend(Y.M.atto_image.EditableImg, Y.Base, {
    /**
     * The DOM element to be edited, more specifically, img.
     *
     * @property node
     * @type {null|Y.Node}
     * @required
     * @default null
     * @writeOnce
     * @public
     */
    node: null,

    /**
     * The editor instance where the editable img is located.
     *
     * @property host
     * @type {null|Y.M.atto_editor}
     * @required
     * @default null
     * @writeOnce
     * @public
     */
    host: null,

    /**
     * Disabled by default.
     *
     * @property _enable
     * @type {Boolean}
     * @default null
     * @private
     */
    _enable: false,

    /**
     * The DeleteMutationObserver for self.node
     *
     * @property _nodeDeletionMutationObserver
     * @type {null|Y.M.atto_image.DeleteMutationObserver}
     * @default null
     * @private
     */
    _nodeDeletionMutationObserver: null,

    /**
     * Represents .atto-image-wrapper that wraps the node.
     *
     * @property nodeWrapper
     * @type {null|Y.Node}
     * @default null
     */
    nodeWrapper: null,

    initializer: function(cfg) {
        this.node = cfg.node;

        this.nodeWrapper = this.node.ancestor('.atto-image-wrapper');
        if (!this.nodeWrapper) {
            this.node.wrap(Y.Handlebars.compile(Y.M.atto_image.imgWrapperTemplate)({
                content: ''
            }));
            this.nodeWrapper = this.node.ancestor('.atto-image-wrapper');
        }

        this.host = cfg.host;

        this.enable();

        this._publishEvents();

        Y.log('initialized', 'debug', Y.M.atto_image.EditableImg.NAME);
    },

    destructor: function() {
        this.detachAll();
        this.disable();
    },

    /**
     * Create the EditableImg.
     */
    enable: function() {
        // If scaffolding is already establish, don't do a thing.
        if (this._enable) {
            return;
        }

        this._setupImgWrapper();
        this._setupImgNode();
        this.startDeleteMutationObserver();

        this.select();

        this._enable = true;
        Y.log('enabled', 'debug', Y.M.atto_image.EditableImg.NAME);
    },

    /**
     * Destroys the EditableImg.
     */
    disable: function() {
        // If scaffolding is not a yet establish, don't do a thing.
        if (!this._enable) {
            return;
        }

        this.node.detachAll();
        this.nodeWrapper.detachAll();

        this.stopDeleteMutationObserver();
        this._destroyImgNode();
        this._destroyImgWrapper();

        this._enable = false;

        Y.log('disabled', 'debug', Y.M.atto_image.EditableImg.NAME);
    },

    /**
     * Starts watching node deletion if not already. Note we don't watch nodeWrapper, since deleting
     * nodeWrapper, delete's everything.
     */
    startDeleteMutationObserver: function() {
        if (!this._nodeDeletionMutationObserver) {
            this._nodeDeletionMutationObserver = new Y.M.atto_image.DeleteMutationObserver({
                node: this.node,
                deletionCallback: this._onDelete.bind(this)
            });
            this._nodeDeletionMutationObserver.start();
        }
    },

    /**
     * Stops watching nodeWrapper (and its children) for deletion.
     */
    stopDeleteMutationObserver: function() {
        // Ensure to disable all MutationObservers first since we will be deleting those node.
        // We will be calling deletionCallback which we don't want. What if we just want to disable
        // the resizable and not call this.deleteNode().
        if (this._nodeDeletionMutationObserver) {
            this._nodeDeletionMutationObserver.stop();
            this._nodeDeletionMutationObserver = null;
        }
    },

    /**
     * The node being edited.
     * @return {Y.Node} this.node The node being edited.
     */
    getNode: function() {
        return this.node;
    },

    /**
     * The container of the node.
     * @return {Y.Node} Return the nodeWrapper.
     */
    getImgWrapper: function() {
        return this.nodeWrapper;
    },

    /**
     * Get image size.
     * @returns {{width: Number, height: Number}}
     */
    getImgSize: function() {
        return {
            width: parseFloat(this.node.getAttribute('width')),
            height: parseFloat(this.node.getAttribute('height'))
        };
    },

    /**
     * Sets the size of the img.
     * @param {{width: {Number}, height: {Number}}} size New size of the image.
     */
    setSize: function(size) {
        this.node.setAttrs({
            width: size.width,
            height: size.height
        });

        // Since nodeWrapper is div, we can only set its size via css styles not html attributes.
        this.nodeWrapper.setStyles({
            width: size.width,
            height: size.height
        });

        this.fire('transform');
    },

    /**
     * Select the editable image.
     */
    select: function() {
        // Keep selection when done recalculating. These way we can always delete or copy it.
        window.rangy.getSelection().removeAllRanges();  // Deselect all selection.
        var selection = this.getSelection();
        this.host.setSelection(selection);  // Set selection (window.rangy).
    },

    /**
     * Gets the editable image selection (including the wrapper).
     * @returns {[rangy.Range]}
     */
    getSelection: function() {
        var selection = null;
        var isFirefox = Y.UA.gecko > 0;
        if (isFirefox) {
            /*
             * A bug when selecting things in firefox,
             * @see http://stackoverflow.com/questions/11432933/how-to-select-a-node-in-a-range-with-webkit-browsers
             *
             * This is amended by adding dummy span before and after the object.
             *
             * Note: If this is done in chrome, the span won't be selected, thus we only do this for firefox. If
             *       span are selected, the paste handler can clean them up since they have atto_control class.
             */
            this.nodeWrapper.insert('<span class="atto_control">', 'before');
            this.nodeWrapper.insert('<span class="atto_control">', 'after');
            var newSelectionRange = window.rangy.createRange();
            newSelectionRange.setStartBefore(this.nodeWrapper.getDOMNode());
            newSelectionRange.setEndAfter(this.nodeWrapper.getDOMNode());
            newSelectionRange.setStart(newSelectionRange.startContainer, newSelectionRange.startOffset - 1);
            newSelectionRange.setEnd(newSelectionRange.endContainer, newSelectionRange.endOffset + 1);

            selection = [newSelectionRange];
        } else {
            selection = this.host.getSelectionFromNode(this.nodeWrapper);
        }

        return selection;
    },

    /**
     * Adds a control node that aids the editing of the imae. e.g. for Y.M.atto_image.resizable,
     * control node with the be the Y.M.atto_image.resizable._resizeOverlayNode.
     *
     * To make controls disappear during save/autosave or paste, atto_control class is attached to them.
     *
     * @param {Y.Node} controlNode The node that aids the editing of the image.
     */
    addControl: function(controlNode) {
        controlNode.addClass('atto_control');
        this.nodeWrapper.appendChild(controlNode);
    },

    /**
     * Aligns the image to the left.
     */
    alignLeft: function() {
        Y.M.atto_image.EditableImg.alignLeft(this.nodeWrapper);
    },

    /**
     * Remove image alignment.
     */
    alignCenter: function() {
        Y.M.atto_image.EditableImg.alignCenter(this.nodeWrapper);
    },

    /**
     * Align the image to the right.
     */
    alignRight: function() {
        Y.M.atto_image.EditableImg.alignRight(this.nodeWrapper);
    },

    /**
     * Publish events for Y.M.atto_image.resizable object.
     *
     * @private
     */
    _publishEvents: function() {
        /**
         * @event click Fired when at least one of the nodes inside image wrapper div is clicked.
         *              (Or resize obj is clicked).
         */
        this.publish('click', {
            prefix: Y.M.atto_image.EditableImg.NAME,
            emitFacade: true,
            broadcast: 2  // Global broadcast, just like button clicks.
        }, this);

        /**
         * @event dblclick Fired when at least one of the nodes inside image wrapper is double clicked.
         */
        this.publish('dblclick', {
            prefix: Y.M.atto_image.EditableImg.NAME,
            emitFacade: true,
            broadcast: 2  // Global broadcast, just like button clicks.
        }, this);

        /**
         * @event init Fired once at the beginning.
         */
        this.publish('init', {
            prefix: Y.M.atto_image.EditableImg.NAME,
            emitFacade: true,
            broadcast: 2, // Global broadcast, just like button clicks.
            context: this
        }, this);

        /**
         * @event delete Fired once the node (image) or any EditableImg items is deleted.
         */
        this.publish('delete', {
            prefix: Y.M.atto_image.EditableImg.NAME,
            emitFacade: true,
            broadcast: 2,  // Global broadcast, just like button clicks.
            context: this
        }, this);

        /**
         * @event transform Fired while transforming, e.g. translate, rotate, resize, crop, ...
         */
        this.publish('transform', {
            prefix: Y.M.atto_image.EditableImg.NAME,
            emitFacade: true,
            broadcast: 2,  // Global broadcast, just like button clicks.
            context: this
        }, this);
    },

    /**
     * Setup this.node for editing.
     * @private
     */
    _setupImgNode: function() {
        Y.log('_setupImgNode', 'debug', Y.M.atto_image.EditableImg.NAME);
    },

    /**
     * Destroys the stuff from @see this._setupImgNode
     * @private
     */
    _destroyImgNode: function() {
        this.node.detachAll();
        Y.log('_destroyImgNode', 'debug', Y.M.atto_image.EditableImg.NAME);
    },

    /**
     * Apply styling specific for editable images. These are gotten rid of after save.
     * @see clean.js of atto
     *
     * @param {Y.Node} node to enable hide until save feature.
     */
    _enableImageEditable: function(node) {
        node.addClass(Y.M.atto_image.imageEditableClass);
    },

    /**
     * @see enableImageEditable, this is simply the opposite.
     *
     * @param {Y.Node} node to enable hide until save feature.
     */
    _disableImageEditable: function(node) {
        node.removeClass(Y.M.atto_image.imageEditableClass);
    },

    /**
     * Setup the nodeWrapper styles/attribute if not yet setup.
     * @private
     */
    _setupImgWrapper: function() {
        if (!this.nodeWrapper) {
            return;
        }

        // Do this so we can delete the image.
        this.nodeWrapper.setAttribute('contenteditable', true);

        // Since nodeWrapper is div, we can only set its size via css styles not html attributes.
        this.nodeWrapper.setStyles(this.getImgSize());

        // Setup event handlers.
        // Bubble up the click event from container to this resizable object.
        this.nodeWrapper.on("click", this._onClick, this);
        this.nodeWrapper.on("dblclick", this._onDblClick, this);

        // Bubble up the click event from container's children to this resizable object.
        // Note: For some reason resizing does not call click event, thus no worries when those handles are selected
        //       for dragging.
        this.nodeWrapper.get("children").each(function(child) {
            child.on('click', this._onClick, this);
            child.on('dblclick', this._onDblClick, this);
        }, this);

        /**
         * Resize container contains many components. If we are dragging something, many things
         * to consider what we might be dragging around. A node that is dragged out of container also becomes
         * it's own node, thus when Y.M.atto_image.resizable.disable is called, those node are left lying
         * around. Solution is just disallow dragging.
         */
        this.nodeWrapper.before('dragstart', function(e) {
            Y.log('dragstart', 'debug', Y.M.atto_image.EditableImg.NAME);
            e.halt(true);
        }, this);

        this._enableImageEditable(this.nodeWrapper);
    },

    /**
     * Does not really "destroy" this.nodeWrapper, but rather disassemble the setup that should only exist while
     * editing.
     * @private
     */
    _destroyImgWrapper: function() {
        if (!this.nodeWrapper) {
            return;
        }

        this._disableImageEditable(this.nodeWrapper);

        this.nodeWrapper.detachAll();

        // Done editing the image at this point. To ensure user can't enter text, set this to contenteditable=false.
        this.nodeWrapper.setAttribute('contenteditable', false);
    },

    /**
     * Event handler for click event.
     *
     * @param {Y.EventFacade} e Event facade object.
     * @private
     */
    _onClick: function(e) {
        e.stopPropagation();
        this.fire('click', e);
        this.select();
        Y.log('click', 'debug', Y.M.atto_image.EditableImg.NAME);
    },

    /**
     * Event handler for dblclick event.
     *
     * @param {Y.EventFacade} e Event facade object.
     * @private
     */
    _onDblClick: function(e) {
        e.stopPropagation();
        this.fire('dblclick', e);
        this.select();
        Y.log('dblclick', 'debug', Y.M.atto_image.EditableImg.NAME);
    },

    /**
     * Event handler for delete event.
     *
     * @param {NodeList} nodes Node(s) that got deleted.
     * @private
     */
    _onDelete: function() {
        this.disable();

        // Delete everything.
        this.nodeWrapper.remove(true);

        this.fire('delete');
        Y.log('delete', 'debug', Y.M.atto_image.EditableImg.NAME);
    }
}, {
    NAME: 'atto_image_EditableImg',

    /**
     * Function to check if Y.M.atto_image.resizable is supported.
     *
     * @return {Boolean} true if Y.M.atto_image.resizable is supported, otherwise false.
     */
    isSupported: function() {
        var supported = Y.M.atto_image.DeleteMutationObserver.isSupported();
        Y.log(supported ? 'supported' : 'not supported', 'debug', Y.M.atto_image.EditableImg.NAME);
        return supported;
    },

    /**
     * Function cleans up the .atto-image-wrapper's styles attribute.
     *
     * @param {Y.Node} editableImgNode Node with .atto-image-wrapper classes.
     * @returns {Y.Node} modified node with cleaned up style attribute.
     */
    clean: function(editableImgNode) {
        Y.M.atto_image.utility.cleanupInlineStyles(
            editableImgNode,
            Y.M.atto_image.EditableImg.validInlineStyles.nodeWrapper
        );

        var img = editableImgNode.one('> img');
        Y.M.atto_image.utility.cleanupInlineStyles(
            img,
            Y.M.atto_image.EditableImg.validInlineStyles.node
        );

        return editableImgNode;
    },

    /**
     * Valid inline styles for permanent nodes (we ignore DOM for editing image, or nodes with .atto_control class).
     *
     * @attribute validInlineStyles
     */
    validInlineStyles: {
        nodeWrapper: [
            'width',
            'height',
            'float',
            'margin'
        ],

        node: [
            'width',
            'height',
            'left',
            'top'
        ]
    },

    /**
     * Aligns the image to the left.
     * @param {Y.Node} editableImgNode Node with .atto-image-wrapper classes.
     */
    alignLeft: function(editableImgNode) {
        editableImgNode.addClass('atto-image-align-left');
        editableImgNode.removeClass('atto-image-align-right');
        editableImgNode.removeClass('atto-image-align-center');
    },

    /**
     * Remove image alignment.
     * @param {Y.Node} editableImgNode Node with .atto-image-wrapper classes.
     */
    alignCenter: function(editableImgNode) {
        editableImgNode.removeClass('atto-image-align-left');
        editableImgNode.removeClass('atto-image-align-right');
        editableImgNode.addClass('atto-image-align-center');
    },

    /**
     * Align the image to the right.
     * @param {Y.Node} editableImgNode Node with .atto-image-wrapper classes.
     */
    alignRight: function(editableImgNode) {
        editableImgNode.removeClass('atto-image-align-left');
        editableImgNode.addClass('atto-image-align-right');
        editableImgNode.removeClass('atto-image-align-center');
    }
});// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/*
 * @package    atto_image
 * @copyright  2016 Joey Andres <jandres@ualberta.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Adds image resizing capability. Suppose you have an img DOM node with id='foo-img', to
 * resize the img:
 *
 *     var editableImg = Y.M.atto_image.EditableImg({
 *         node: Y.one('img#foo-img')
 *     });
 *     var options = {
 *       editableImg: editableImg
 *     };
 *     var resizable = new Y.M.atto_image.resizable(node);
 *
 * @class Y.M.atto_image.resizable
 */
Y.M.atto_image.resizable = function() {
    Y.M.atto_image.resizable.superclass.constructor.apply(this, arguments);
};
Y.extend(Y.M.atto_image.resizable, Y.Base, {
    /**
     * The DOM element to resize, more specifically, img.
     *
     * @property node
     * @type {null|Y.Node}
     * @required
     * @default null
     * @writeOnce
     * @public
     */
    node: null,

    /**
     * Represents the EditableImg, the thing where we suspend the resize handles and the thing
     * that user can see while in img editing mode.
     *
     * @property _editableImg
     * @type {null|Y.M.atto_image.EditableImg}
     * @private
     */
    _editableImg: null,

    /**
     * Keeps track of the Y.Overlay object during resizing. Null when not resizing (this._enable is false).
     *
     * @property _resizableOverlay
     * @type {null|Y.Overlay}
     * @default null
     * @private
     */
    _resizableOverlay: null,

    /**
     * _resizableOverlayNode as opposed to _resizableOverlay, is the DOM object _resizableOverlay will be dealing with.
     *
     * @property _resizableOverlayNode
     * @type {null|Y.Node}
     * @default null
     * @private
     */
    _resizableOverlayNode: null,

    /**
     * False by default.
     *
     * @property _enable
     * @type {Boolean}
     * @default null
     * @private
     */
    _enable: false,

    initializer: function(cfg) {
        this._editableImg = cfg.editableImg;
        this.node = this._editableImg.node;

        this.enable();
        this._publishEvents();

        Y.log('initialized', 'debug', Y.M.atto_image.resizable.NAME);
    },

    destructor: function() {
        this.disable();
    },

    /**
     * Call to build the resizing scaffolding.
     */
    enable: function() {
        // If scaffolding is already establish, don't do a thing.
        if (this._enable) {
            return;
        }

        // If this._editableImg is click/dblclick, we pass the event here.
        this._editableImg.on('click', this._onClick.bind(this));
        this._editableImg.on('dblclick', this._onDblClick.bind(this));

        this._resizableOverlayNode = this._createResizeOverlayNode();
        this._editableImg.addControl(this._resizableOverlayNode, false);

        this._resizableOverlay = this._createResizeOverlay(this._resizableOverlayNode, this._editableImg.node);

        // Align again since rotation might have misalign this._resizeOverlay to the image.
        this._resizableOverlay.align();

        this._enable = true;
        Y.log('enabled', 'debug', Y.M.atto_image.resizable.NAME);
    },

    /**
     * Call to take down the resizing scaffolding.
     */
    disable: function() {
        // If scaffolding is not a yet establish, don't do a thing.
        if (!this._enable) {
            return;
        }

        this.detachAll();

        // Garbage collection, in reverse order. Note some operations are redundant, but I want order.

        if (this._resizableOverlay) {
            this._resizableOverlay.destroy(true);
            this._resizableOverlay = null;
        }

        if (this._resizableOverlayNode) {
            this._resizableOverlayNode.remove(true);
            this._resizableOverlayNode = null;
        }

        this._enable = false;
        Y.log('disabled', 'debug', Y.M.atto_image.resizable.NAME);
    },

    /**
     * Get the node being resized.
     * @return {Y.Node} this.node The node being resized.
     */
    getNode: function() {
        return this.node;
    },

    /**
     * Get nodeWrapper.
     *
     * @returns {null|Y.Node} The container for auxiliary Y.Node, which contains resize handles.
     */
    getImgWrapper: function() {
        return this._editableImg.getImgWrapper();
    },

    /**
     * Publish events for Y.M.atto_image.resizable object.
     *
     * @private
     */
    _publishEvents: function() {
        /**
         * @event click Fired when at least one of the nodes inside resize div is clicked.
         *                                   (Or resize obj is clicked).
         */
        this.publish('click', {
            prefix: Y.M.atto_image.resizable.NAME,
            emitFacade: true,
            broadcast: 2  // Global broadcast, just like button clicks.
        }, this);

        /**
         * @event dblclick Fired when at least one of the nodes inside resize div is double clicked.
         */
        this.publish('dblclick', {
            prefix: Y.M.atto_image.resizable.NAME,
            emitFacade: true,
            broadcast: 2  // Global broadcast, just like button clicks.
        }, this);

        /**
         * @event resize:start Fired before resizing.
         */
        this.publish('resize:start', {
            prefix: Y.M.atto_image.resizable.NAME,
            emitFacade: true,
            broadcast: 2  // Global broadcast, just like button clicks.
        }, this);

        /**
         * @event resize:resize Fired during resizing.
         */
        this.publish('resize:resize', {
            prefix: Y.M.atto_image.resizable.NAME,
            emitFacade: true,
            broadcast: 2  // Global broadcast, just like button clicks.
        }, this);

        /**
         * @event resize:end Fired after resizing.
         */
        this.publish('resize:end', {
            prefix: Y.M.atto_image.resizable.NAME,
            emitFacade: true,
            broadcast: 2  // Global broadcast, just like button clicks.
        }, this);

        /**
         * @event init Fired once at the beginning. Due to some bug in YUI.
         */
        this.publish('init', {
            prefix: Y.M.atto_image.resizable.NAME,
            emitFacade: true,
            broadcast: 2, // Global broadcast, just like button clicks.
            context: this
        }, this);
    },

    /**
     * @see this._resizeOverlayNode
     *
     * @returns {Y.Node}
     * @private
     */
    _createResizeOverlayNode: function() {
        var resizableOverlayTemplate = Y.Handlebars.compile(Y.M.atto_image.resizeOverlayNodeTemplate);
        return Y.Node.create(resizableOverlayTemplate({classes: ''}));
    },

    /**
     * @see this._resizeOverlay
     *
     * @param {Y.Node} resizableOverlayNode A div that will be manipulated by Y.Overlay.
     * @param {Y.Node} nodeToOverlay The node (img in our case) that will be placed inside resizableOverlayNode.
     * @returns {Y.Overlay}
     * @private
     */
    _createResizeOverlay: function(resizableOverlayNode, nodeToOverlay) {
        var resizableOverlay = new Y.Overlay({
            srcNode: resizableOverlayNode,

            visible: true,
            render: true,

            // Place overlay on top of each other.
            align: {node: nodeToOverlay, points: ["tl", "tl"]}
        });
        this._setResizeOverlaySize(resizableOverlay);
        resizableOverlay.plug(Y.Plugin.Resize, {
            handles: ['t', 'r', 'b', 'l', 'tr', 'tl', 'br', 'bl']
        });
        resizableOverlay.resize.plug(Y.Plugin.ResizeConstrained, {}, this);

        // Setup resize event handlers.
        resizableOverlay.resize.on('resize:start', this._onResizeStart, this);
        resizableOverlay.resize.on('resize:resize', this._onResize, this);
        resizableOverlay.resize.on('drag:end', this._onResizeEnd, this);

        // So that the overlay is deleted when saving.
        resizableOverlay.get("boundingBox").addClass('atto_control');
        resizableOverlay.get("boundingBox").addClass('atto-image-editable-helper-wrapper');
        resizableOverlay.get("boundingBox").addClass('atto-image-resize-overlay-wrapper');
        resizableOverlay.get("boundingBox").setAttribute('contenteditable', false);

        return resizableOverlay;
    },

    /**
     * Event handler for resizing start.
     * @param {Y.EventFacade} e Event facade object.
     * @private
     */
    _onResizeStart: function(e) {
        this.fire('resize:start', e);

        Y.log('resize start', 'debug', Y.M.atto_image.resizable.NAME);
    },

    /**
     * Event handler for resizing.
     * @param {Y.EventFacade} e Event facade object.
     * @private
     */
    _onResize: function(e) {
        this._resizableOverlay.align();

        // Google doc like resizing. If tl, tr, bl, br resize handles are drag, preserve aspect ratio.
        switch (this._resizableOverlay.resize.handle) {
            case Y.M.atto_image.resizeHandles.TL:
            case Y.M.atto_image.resizeHandles.TR:
            case Y.M.atto_image.resizeHandles.BL:
            case Y.M.atto_image.resizeHandles.BR:
                this._resizableOverlay.resize.con.set('preserveRatio', true);
                break;
            default:
                this._resizableOverlay.resize.con.set('preserveRatio', false);
        }

        this.fire('resize:resize', e);
    },

    /**
     * Event handler for resizing end.
     * @param {Y.EventFacade} e Event facade object.
     * @private
     */
    _onResizeEnd: function(e) {
        this._resizableOverlay.align();

        this._editableImg.setSize(this._getResizeOverlaySize());

        this.fire('resize:end', e);
        Y.log('resize end', 'debug', Y.M.atto_image.resizable.NAME);
    },

    /**
     * Event handler for click event on resize auxiliary DOM elements.
     *
     * @param {Y.EventFacade} e Event facade object.
     * @private
     */
    _onClick: function(e) {
        e.stopPropagation();
        this.fire('click', e);
        Y.log('click', 'debug', Y.M.atto_image.resizable.NAME);
    },

    /**
     * Event handler for dblclick event on resize auxiliary DOM elements.
     *
     * @param {Y.EventFacade} e Event facade object.
     * @private
     */
    _onDblClick: function(e) {
        e.stopPropagation();
        this.fire('dblclick', e);
        Y.log('dblclick', 'debug', Y.M.atto_image.resizable.NAME);
    },

    /**
     * Gets the size of the resizeOverlay,j factoring orientation.
     * @returns {{width: Number, height: Number}}
     * @private
     */
    _getResizeOverlaySize: function() {
        var newWidth = this._resizableOverlay.resize.info.offsetWidth;
        var newHeight = this._resizableOverlay.resize.info.offsetHeight;
        return {width: newWidth, height: newHeight};
    },

    /**
     * Set the size of the overlay from node (image), factoring orientation.
     * @param {undefined|Y.Overlay} resizableOverlay
     * @private
     */
    _setResizeOverlaySize: function(resizableOverlay) {
        resizableOverlay = resizableOverlay || this._resizableOverlay;

        /*
         * Since overlay always point up (as much as possible), if top is left/right in which overlay is almost
         * perpendicular to img, swap width/height so they still over each other exactly.
         */
        var sizeAttrs = this._editableImg.getImgSize();
        resizableOverlay.get('boundingBox').setStyles(sizeAttrs);
    }
}, {
    NAME: 'atto_image_resizable'
});// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/*
 * @package    atto_image
 * @copyright  2013 Damyon Wiese  <damyon@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module moodle-atto_image_alignment-button
 */

/**
 * Atto image selection tool.
 *
 * @namespace M.atto_image
 * @class Button
 * @extends M.editor_atto.EditorPlugin
 */

var CSS = {
        RESPONSIVE: 'img-responsive',
        INPUTALIGNMENT: 'atto_image_alignment',
        INPUTALT: 'atto_image_altentry',
        INPUTHEIGHT: 'atto_image_heightentry',
        INPUTSUBMIT: 'atto_image_urlentrysubmit',
        INPUTURL: 'atto_image_urlentry',
        INPUTSIZE: 'atto_image_size',
        INPUTWIDTH: 'atto_image_widthentry',
        IMAGEALTWARNING: 'atto_image_altwarning',
        IMAGEBROWSER: 'openimagebrowser',
        IMAGEPRESENTATION: 'atto_image_presentation',
        INPUTCONSTRAIN: 'atto_image_constrain',
        INPUTCUSTOMSTYLE: 'atto_image_customstyle',
        IMAGEPREVIEW: 'atto_image_preview',
        IMAGEPREVIEWBOX: 'atto_image_preview_box',
        ALIGNSETTINGS: 'atto_image_button'
    },
    SELECTORS = {
        INPUTURL: '.' + CSS.INPUTURL
    },
    ALIGNMENTS = [
        // Vertical alignment.
        {
            name: 'verticalAlign',
            str: 'alignment_top',
            value: 'text-top',
            margin: '0 0.5em'
        }, {
            name: 'verticalAlign',
            str: 'alignment_middle',
            value: 'middle',
            margin: '0 0.5em'
        }, {
            name: 'verticalAlign',
            str: 'alignment_bottom',
            value: 'text-bottom',
            margin: '0 0.5em',
            isDefault: true
        },

        // Floats.
        {
            name: 'float',
            str: 'alignment_left',
            value: 'left',
            margin: '0 0.5em 0 0'
        }, {
            name: 'float',
            str: 'alignment_right',
            value: 'right',
            margin: '0 0 0 0.5em'
        }
    ],

    REGEX = {
        ISPERCENT: /\d+%/
    },

    COMPONENTNAME = 'atto_image',

    TEMPLATE = '' +
            '<form class="atto_form">' +
                '<label for="{{elementid}}_{{CSS.INPUTURL}}">{{get_string "enterurl" component}}</label>' +
                '<input class="fullwidth {{CSS.INPUTURL}}" type="url" id="{{elementid}}_{{CSS.INPUTURL}}" size="32"/>' +
                '<br/>' +

                // Add the repository browser button.
                '{{#if showFilepicker}}' +
                    '<button class="{{CSS.IMAGEBROWSER}}" type="button">{{get_string "browserepositories" component}}</button>' +
                '{{/if}}' +

                // Add the Alt box.
                '<div style="display:none" role="alert" class="warning {{CSS.IMAGEALTWARNING}}">' +
                    '{{get_string "presentationoraltrequired" component}}' +
                '</div>' +
                '<label for="{{elementid}}_{{CSS.INPUTALT}}">{{get_string "enteralt" component}}</label>' +
                '<input class="fullwidth {{CSS.INPUTALT}}" type="text" value="" id="{{elementid}}_{{CSS.INPUTALT}}" size="32"/>' +
                '<br/>' +

                // Add the presentation select box.
                '<input type="checkbox" class="{{CSS.IMAGEPRESENTATION}}" id="{{elementid}}_{{CSS.IMAGEPRESENTATION}}"/>' +
                '<label class="sameline" for="{{elementid}}_{{CSS.IMAGEPRESENTATION}}">' +
                    '{{get_string "presentation" component}}' +
                '</label>' +
                '<br/>' +

                // Add the size entry boxes.
                '<label class="sameline" for="{{elementid}}_{{CSS.INPUTSIZE}}">{{get_string "size" component}}</label>' +
                '<div id="{{elementid}}_{{CSS.INPUTSIZE}}" class="{{CSS.INPUTSIZE}}">' +
                '<label class="accesshide" for="{{elementid}}_{{CSS.INPUTWIDTH}}">{{get_string "width" component}}</label>' +
                '<input type="text" class="{{CSS.INPUTWIDTH}} input-mini" id="{{elementid}}_{{CSS.INPUTWIDTH}}" size="4"/> x ' +

                // Add the height entry box.
                '<label class="accesshide" for="{{elementid}}_{{CSS.INPUTHEIGHT}}">{{get_string "height" component}}</label>' +
                '<input type="text" class="{{CSS.INPUTHEIGHT}} input-mini" id="{{elementid}}_{{CSS.INPUTHEIGHT}}" size="4"/>' +

                // Add the constrain checkbox.
                '<input type="checkbox" class="{{CSS.INPUTCONSTRAIN}} sameline" id="{{elementid}}_{{CSS.INPUTCONSTRAIN}}"/>' +
                '<label for="{{elementid}}_{{CSS.INPUTCONSTRAIN}}">{{get_string "constrain" component}}</label>' +
                '</div>' +

                // Add the alignment selector.
                '<label class="sameline" for="{{elementid}}_{{CSS.INPUTALIGNMENT}}">{{get_string "alignment" component}}</label>' +
                '<select class="{{CSS.INPUTALIGNMENT}}" id="{{elementid}}_{{CSS.INPUTALIGNMENT}}">' +
                    '{{#each alignments}}' +
                        '<option value="{{value}}">{{get_string str ../component}}</option>' +
                    '{{/each}}' +
                '</select>' +
                // Hidden input to store custom styles.
                '<input type="hidden" class="{{CSS.INPUTCUSTOMSTYLE}}"/>' +
                '<br/>' +

                // Add the image preview.
                '<div class="mdl-align">' +
                '<div class="{{CSS.IMAGEPREVIEWBOX}}">' +
                    '<img src="#" class="{{CSS.IMAGEPREVIEW}}" alt="" style="display: none;"/>' +
                '</div>' +

                // Add the submit button and close the form.
                '<button class="{{CSS.INPUTSUBMIT}}" type="submit">{{get_string "saveimage" component}}</button>' +
                '</div>' +
            '</form>',

        IMAGETEMPLATE = '' +
            '<img src="{{url}}" alt="{{alt}}" ' +
                '{{#if width}}width="{{width}}" {{/if}}' +
                '{{#if height}}height="{{height}}" {{/if}}' +
                '{{#if presentation}}role="presentation" {{/if}}' +
                '{{#if customstyle}}style="{{customstyle}}" {{/if}}' +
                '{{#if classlist}}class="{{classlist}}" {{/if}}' +
                '{{#if id}}id="{{id}}" {{/if}}' +
                'unselectable="on" ' +
                '/>';

Y.namespace('M.atto_image').Button = Y.Base.create('button', Y.M.editor_atto.EditorPlugin, [], {
    /**
     * A reference to the current selection at the time that the dialogue
     * was opened.
     *
     * @property _currentSelection
     * @type Range
     * @private
     */
    _currentSelection: null,

    /**
     * The most recently selected image.
     *
     * @param _selectedImage
     * @type Node
     * @private
     */
    _selectedImage: null,

    /**
     * A reference to the currently open form.
     *
     * @param _form
     * @type Node
     * @private
     */
    _form: null,

    /**
     * The dimensions of the raw image before we manipulate it.
     *
     * @param _rawImageDimensions
     * @type Object
     * @private
     */
    _rawImageDimensions: null,

    /**
     * Resizable object.
     *
     * @param _resizable
     * @type Y.M.atto_image.resizable
     * @private
     */
    _resizable: null,

    /**
     * Represents the current editable image.
     *
     * @param _editableImg
     * @type Y.M.atto_image.resizable
     * @private
     */
    _editableImg: null,

    initializer: function() {
        var host = this.get('host');

        this.addButton({
            icon: 'e/insert_edit_image',
            callback: this._displayDialogue,
            tags: 'img',
            tagMatchRequiresAll: false
        });

        // To disable text being entered in content editable, set contenteditable=false in .atto-image-wrapper.
        Y.M.atto_image.utility.disableContentEditable(this.editor);

        this.editor.delegate('dblclick', this._displayDialogue, 'img', this);
        this.editor.delegate('click', this._handleClick, 'img', this);
        this.editor.on('drop', this._handleDragDrop, this);

        // Handle cases when something not an image is selected.
        this.editor.delegate('click', this._handleDeselect, ':not(img)', this);

        // Disable dragging for now. atto-image-wrapper is contenteditable=false so that text can't be written inside
        // it. The down side is that when dragging an image out of it, the image is cloned instead of moved.
        // MDL-55530
        this.editor.delegate('dragstart', function(e) {
            e.halt(true);
        }, '.atto-image-wrapper', this);

        // e.preventDefault needed to stop the default event from clobbering the desired behaviour in some browsers.
        this.editor.on('dragover', function(e) {
            e.preventDefault();
        }, this);
        this.editor.on('dragenter', function(e) {
            e.preventDefault();
        }, this);

        host.on('atto:htmlcleaned', this._handleHtmlClean, this);
        host.on(['atto:pastehtmlstylescleaned', 'atto:pastehtmlclasscleaned'], this._handleClassStylesClean, this);


        // Attach atto_align event listeners.
        host.on('pluginsloaded', function() {
            var alignPlugin = host.plugins.align;
            if (alignPlugin && alignPlugin.isEnabled()) {
                Y.log('atto_align enabled', 'debug', 'atto_image');
                alignPlugin.buttons.justifyLeft.on('click', this._alignLeft, this);
                alignPlugin.buttons.justifyCenter.on('click', this._alignCenter, this);
                alignPlugin.buttons.justifyRight.on('click', this._alignRight, this);
            } else {
                Y.log('atto_align disabled', 'debug', 'atto_image');
            }
        }, this);
    },

    destructor: function() {
        // Detach atto_align event listeners.
        var alignPlugin = this.get('host').plugins.align;
        if (alignPlugin && alignPlugin.isEnabled()) {
            alignPlugin.buttons.justifyLeft.detach('click', this._alignLeft, this);
            alignPlugin.buttons.justifyCenter.detach('click', this._alignCenter, this);
            alignPlugin.buttons.justifyRight.detach('click', this._alignRight, this);
        }
    },

    /**
     * Aligns the selected .atto-image-wrapper nodes to the left.
     * @private
     */
    _alignLeft: function() {
        // If there is an active Y.M.atto_image.EditableImg.
        if (this._editableImg) {
            this._editableImg.alignLeft();
        }

        // Handle the selected ones.
        this._getSelectedAttoImages().forEach(function(attoImg) {
            Y.M.atto_image.EditableImg.alignLeft(attoImg);
        });
    },

    /**
     * Aligns the selected .atto-image-wrapper nodes to the center.
     * @private
     */
    _alignCenter: function() {
        // If there is an active Y.M.atto_image.EditableImg.
        if (this._editableImg) {
            this._editableImg.alignCenter();
        }

        // Handle the selected ones.
        this._getSelectedAttoImages().forEach(function(attoImg) {
            Y.M.atto_image.EditableImg.alignCenter(attoImg);
        });
    },

    /**
     * Aligns the selected .atto-image-wrapper nodes to the right.
     * @private
     */
    _alignRight: function() {
        // If there is an active Y.M.atto_image.EditableImg.
        if (this._editableImg) {
            this._editableImg.alignRight();
        }

        // Handle the selected ones.
        this._getSelectedAttoImages().forEach(function(attoImg) {
            Y.M.atto_image.EditableImg.alignRight(attoImg);
        });
    },

    /**
     * Gets the selected atto-image-wrapper nodes.
     * @returns {[Y.Node]} Selected nodes with .atto-image-wrapper class.
     * @private
     */
    _getSelectedAttoImages: function() {
        var selectedAttoImages = [];
        this.get('host').getSelection().forEach(function(range) {
            var selectedNodes = Y.M.atto_image.utility.getRangeSelectedNodes(range);
            var selectedElements = selectedNodes.filter(function(domNode) {
                return Y.one(domNode);
            }).map(function(domNode) {
                return Y.one(domNode);
            });

            selectedElements.forEach(function(node) {
                // See if node is an element not a text or something else.
                if (node.hasClass('atto-image-wrapper')) {
                    selectedAttoImages.push(node);
                } else {
                    node.all('.atto-image-wrapper').each(function(attoImageWrapper) {
                        selectedAttoImages.push(attoImageWrapper);
                    });
                }
            });
        });

        return selectedAttoImages;
    },

    /**
     * Handles atto:htmlcleaned for atto_image package.
     *
     * @param {EventFacade} e EventFacade modified by atto_editor's clean.js
     */
    _handleHtmlClean: function(e) {
        var content = e.args.html;

        var invalidString = !content;
        if (invalidString) {
            e.args.html = '';
            return;
        }

        // Removes atto-image-helper* classes.
        content = content.replace(
                /(<[^>]*?class\s*?=\s*?")([^>"]*)(")/gi,
                function(match, group1, group2, group3) {
                    var group2WithoutHelperClass = group2.replace(/(?:^|[\s])[\s]*atto-image-helper[_a-zA-Z0-9\-]*/gi, "");
                    return group1 + group2WithoutHelperClass + group3;
                }
            );

        // This pattern is taken from Y.M.atto_editor._cleanStyles for handling content cleanup.
        var holder = document.createElement('div');
        holder.innerHTML = content;
        var contentNode = Y.one(holder);

        if (!contentNode) {
            e.args.html = content || '';
            return;
        }

        // Remove ".atto-image-wrapper .atto_control" elements.
        contentNode.all('.atto-image-wrapper .atto_control').remove(true);

        // Remove ".atto-image-wrapper > [elem]" elements, where [elem]
        // is any unwanted element(s) inserted during clean.js's operation.
        // - clean.js inserts <br> in the immediate child of .atto-image-wrapper, remove them.
        contentNode.all('.atto-image-wrapper > br').remove(true);

        // Set contenteditable=false for all atto-image-wrapper so other editors can't insert text in this image
        // wrappers.
        contentNode.all('.atto-image-wrapper[contenteditable=true]').setAttribute('contenteditable', false);

        // For each .atto-image-wrapper in editor, do clean up. Pasting collects all the styles from both inline and
        // non-inline styles (styles from classes or id) and merge them as an inline-style.
        // Since 'atto:htmlcleaned' is fired during copy/paste, this is the perfect time to clean those pesky inline
        // styles.
        contentNode.all('.atto-image-wrapper').each(function(attoImage) {
            Y.M.atto_image.EditableImg.clean(attoImage);
        });

        content = contentNode.getHTML();

        e.args.html = content;
    },

    /**
     * Callback passed to 'atto:pastehtmlclasscleaned' and 'atto:pastehtmlstylescleaned' events.
     *
     * @param {EventFacade} e EventFacade modified by 'atto:pastehtmlclasscleaned' and 'pastehtmlstylescleaned' event.
     * @private
     */
    _handleClassStylesClean: function(e) {
        // It exempts .atto-image-wrapper from class attribute removal.
        var nodeList = e.args;
        var exemptionNodeList = nodeList.filter(function(node) {
            return Y.one(node).ancestor('.atto-image-wrapper', true);
        });
        exemptionNodeList.forEach(function(node) {
            if (nodeList.indexOf(node) >= 0) {
                nodeList.splice(nodeList.indexOf(node), 1);
            }
        });
    },

    /**
     * Handle a drag and drop event with an image.
     *
     * @method _handleDragDrop
     * @param {EventFacade} e
     * @return mixed
     * @private
     */
    _handleDragDrop: function(e) {

        var self = this,
            host = this.get('host'),
            template = Y.Handlebars.compile(IMAGETEMPLATE);

        host.saveSelection();
        e = e._event;

        // Only handle the event if an image file was dropped in.
        var handlesDataTransfer = (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length);
        if (handlesDataTransfer && /^image\//.test(e.dataTransfer.files[0].type)) {

            var options = host.get('filepickeroptions').image,
                savepath = (options.savepath === undefined) ? '/' : options.savepath,
                formData = new FormData(),
                timestamp = 0,
                uploadid = "",
                xhr = new XMLHttpRequest(),
                imagehtml = "",
                keys = Object.keys(options.repositories);

            e.preventDefault();
            e.stopPropagation();
            formData.append('repo_upload_file', e.dataTransfer.files[0]);
            formData.append('itemid', options.itemid);

            // List of repositories is an object rather than an array.  This makes iteration more awkward.
            for (var i = 0; i < keys.length; i++) {
                if (options.repositories[keys[i]].type === 'upload') {
                    formData.append('repo_id', options.repositories[keys[i]].id);
                    break;
                }
            }
            formData.append('env', options.env);
            formData.append('sesskey', M.cfg.sesskey);
            formData.append('client_id', options.client_id);
            formData.append('savepath', savepath);
            formData.append('ctx_id', options.context.id);

            // Insert spinner as a placeholder.
            timestamp = new Date().getTime();
            uploadid = 'moodleimage_' + Math.round(Math.random() * 100000) + '-' + timestamp;
            host.focus();
            host.restoreSelection();
            imagehtml = template({
                url: M.util.image_url("i/loading_small", 'moodle'),
                alt: M.util.get_string('uploading', COMPONENTNAME),
                id: uploadid
            });
            host.insertContentAtFocusPoint(imagehtml);
            self.markUpdated();

            // Kick off a XMLHttpRequest.
            xhr.onreadystatechange = function() {
                var placeholder = self.editor.one('#' + uploadid),
                    result,
                    file,
                    newhtml,
                    newimage;

                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        result = JSON.parse(xhr.responseText);
                        if (result) {
                            if (result.error) {
                                if (placeholder) {
                                    placeholder.remove(true);
                                }
                                return new M.core.ajaxException(result);
                            }

                            file = result;
                            if (result.event && result.event === 'fileexists') {
                                // A file with this name is already in use here - rename to avoid conflict.
                                // Chances are, it's a different image (stored in a different folder on the user's computer).
                                // If the user wants to reuse an existing image, they can copy/paste it within the editor.
                                file = result.newfile;
                            }

                            // Replace placeholder with actual image.
                            newhtml = template({
                                url: file.url,
                                presentation: true
                            });
                            newimage = Y.Node.create(newhtml);
                            if (placeholder) {
                                placeholder.replace(newimage);
                            } else {
                                self.editor.appendChild(newimage);
                            }
                            self.markUpdated();
                        }
                    } else {
                        Y.use('moodle-core-notification-alert', function() {
                            new M.core.alert({message: M.util.get_string('servererror', 'moodle')});
                        });
                        if (placeholder) {
                            placeholder.remove(true);
                        }
                    }
                }
            };
            xhr.open("POST", M.cfg.wwwroot + '/repository/repository_ajax.php?action=upload', true);
            xhr.send(formData);
            return false;
        }

    },

    /**
     * Handle a click on an image.
     *
     * @method _handleClick
     * @param {EventFacade} e EventFacade with resizable field added.
     * @private
     */
    _handleClick: function(e) {
        var image = e.target;

        this._initEditableImg(image);

        // Prevent further bubbling the DOM tree.
        // @see http://yuilibrary.com/yui/docs/event/#facade-properties
        // Without this, this will propagate up (bubble) and will hit the textarea, thus calling _handleDeselect,
        // immediately deselecting anything.
        Y.log("_handleClick Killing propagation.", 'debug', 'atto_image:button');
        e.halt(true);
    },

    /**
     * Calls to initialize this._editableImg.
     * @param {DOMNode} image node to be edited.
     * @private
     */
    _initEditableImg: function(image) {
        if (!Y.M.atto_image.EditableImg.isSupported()) {
            return;
        }

        // There are 3 cases:
        // 1. this._editableImg exist and image is already this._editableImg.node
        // 2. this._editableImg exist and image is not this._editableImg.node
        // 3. this._editableImg does not exist
        var self = this;
        var imageAlreadyEditable = this._editableImg && this._editableImg.node.getDOMNode() === image.getDOMNode();
        if (imageAlreadyEditable) {
            return;
        }

        var imageIsNotEditable = this._editableImg && this._editableImg.node.getDOMNode() !== image.getDOMNode();
        if (imageIsNotEditable) {
            this._destroyEditableImg();
        }

        this._editableImg = new Y.M.atto_image.EditableImg({
            node: image,
            host: this.get('host'),
            after: {
                // A bug in YUI. @see https://github.com/yui/yui3/issues/1043 in which resizable.on('init', callback)
                // won't fire. Issue above suggest this solution.
                init: function() {
                    this.on('init', self._editableImgInitHandler, self);
                    this.fire('init');
                }
            }
        });

        this._editableImg.on('delete', this._destroyEditableImg, this);
        this._editableImg.on('dblclick', this._displayDialogueWhileEditing, this);
        Y.log("init", 'debug', 'atto_image:button');
    },

    /**
     * Destroys the this._editableImg and then this._resizable, this._rotatable.
     * @private
     */
    _destroyEditableImg: function() {
        Y.log("destroy", 'debug', 'atto_image:button');
        if (this._editableImg) {
            this._editableImg.destroy();
            this._editableImg = null;

            this._destroyResizable(this._resizable);
        }
    },

    /**
     * Handler when a new instance is created within the editor. And attaches this._resizable,
     * this._rotatable to it.
     * @param {EventFacade} e EventFacade for atto_image_EditableImg:init event.
     * @private
     */
    _editableImgInitHandler: function(e) {
        var editableImg = e.target;

        editableImg.node.removeClass(CSS.RESPONSIVE);

        this._initResizable(editableImg);
    },

    /**
     * Deselect event handler.
     *
     * @param {EventFacade} e
     * @private
     */
    _handleDeselect: function() {
        if (this._editableImg) {
            // If there is an image selected, such that this._resizable is set. Delete it.
            this._destroyEditableImg();
            this._editableImg = null;
        }
    },

    /**
     * Handler when the resizable is created in the editor. It deals with cases in which there are multiple images
     * in the editor.
     *
     * @param {EventFacade} e EventFacade with resizable field added.
     * @private
     */
    _resizableInitHandler: function(e) {
        // If something was selected, deselect it. (Both range and resizable selection).
        var newResizable = e.currentTarget;
        if (newResizable !== this._resizable) {
            this._resizable = newResizable;
            this._resizable.enable();
        }
    },

    /**
     * Handler when the resizable is deleted. Since Y.M.att_image.resizable basically self destructs, might aswell
     * clear our reference to it.
     *
     * @param {EventFacade} e EventFacade
     * @private
     */
    _resizableDeleteHandler: function(e) {
        var resizable = e.target;
        this._destroyResizable(resizable);
    },

    /**
     * Initialize this._resizable.
     *
     * @param {Y.M.atto_image.EditableImg} editableImg The object that represents the editable image.
     * @private
     */
    _initResizable: function(editableImg) {
        var self = this;
        var resizeCfg = {
            editableImg: editableImg,
            after: {
                // A bug in YUI. @see https://github.com/yui/yui3/issues/1043 in which resizable.on('init', callback)
                // won't fire. Issue above suggest this solution.
                init: function() {
                    this.on('init', self._resizableInitHandler, self);
                    this.fire('init');
                }
            }
        };
        this._resizable = new Y.M.atto_image.resizable(resizeCfg);
    },

    /**
     * Destroy _resizable
     * @param {Y.M.atto_image.resizable} resizable Resizable instance.
     * @private
     */
    _destroyResizable: function(resizable) {
        // This is probably usually the case in which the resizable = this._resizable. Either way,
        // we are deleting it.
        if (resizable == this._resizable) {
            this._resizable = null;
        }

        if (resizable) {
            resizable.destroy();
        }
    },

    /**
     * Display the image editing tool.
     *
     * @method _displayDialogue
     * @private
     */
    _displayDialogue: function() {
        // Store the current selection.
        this._currentSelection = this.get('host').getSelection();
        if (this._currentSelection === false) {
            return;
        }

        // Reset the image dimensions.
        this._rawImageDimensions = null;

        var dialogue = this.getDialogue({
            headerContent: M.util.get_string('imageproperties', COMPONENTNAME),
            width: '480px',
            focusAfterHide: true,
            focusOnShowSelector: SELECTORS.INPUTURL
        });

        // Set the dialogue content, and then show the dialogue.
        dialogue.set('bodyContent', this._getDialogueContent())
                .show();
    },

    /**
     * This is called instead of _displayDialogue if the dblclicked occured whilre resizing.
     *
     * @param {EventFacade} e Event object.
     * @private
     */
    _displayDialogueWhileEditing: function(e) {
        var resizable = e.resizable;

        // Resizable should exist, but check for sanity.
        // Currently, the selected element during the dblclick'd is/are the auxiliary DOM elements (we don't want).
        // So we want to destroy the current resizable to reveal the original image, which we change our selection to.
        if (resizable) {
            var imgNode = resizable.node;
            this._destroyResizable(resizable);
            var nodeSelection = this.get('host').getSelectionFromNode(imgNode);
            this.get('host').setSelection(nodeSelection);
            this._currentSelection = this.get('host').getSelection();
        }

        this._displayDialogue();

        // Don't let other dblclick handler handle this.
        e.stopImmediatePropagation();
    },

    /**
     * Set the inputs for width and height if they are not set, and calculate
     * if the constrain checkbox should be checked or not.
     *
     * @method _loadPreviewImage
     * @param {String} url
     * @private
     */
    _loadPreviewImage: function(url) {
        var image = new Image();
        var self = this;

        image.onerror = function() {
            var preview = self._form.one('.' + CSS.IMAGEPREVIEW);
            preview.setStyles({
                'display': 'none'
            });

            // Centre the dialogue when clearing the image preview.
            self.getDialogue().centerDialogue();
        };

        image.onload = function() {
            var input, currentwidth, currentheight, widthRatio, heightRatio;

            self._rawImageDimensions = {
                width: this.width,
                height: this.height
            };

            input = self._form.one('.' + CSS.INPUTWIDTH);
            currentwidth = input.get('value');
            if (currentwidth === '') {
                input.set('value', this.width);
                currentwidth = "" + this.width;
            }
            input = self._form.one('.' + CSS.INPUTHEIGHT);
            currentheight = input.get('value');
            if (currentheight === '') {
                input.set('value', this.height);
                currentheight = "" + this.height;
            }
            input = self._form.one('.' + CSS.IMAGEPREVIEW);
            input.setAttribute('src', this.src);
            input.setStyles({
                'display': 'inline'
            });

            input = self._form.one('.' + CSS.INPUTCONSTRAIN);
            if (currentwidth.match(REGEX.ISPERCENT) && currentheight.match(REGEX.ISPERCENT)) {
                input.set('checked', currentwidth === currentheight);
            } else {
                if (this.width === 0) {
                    this.width = 1;
                }
                if (this.height === 0) {
                    this.height = 1;
                }
                // This is the same as comparing to 3 decimal places.
                widthRatio = Math.round(1000 * parseInt(currentwidth, 10) / this.width);
                heightRatio = Math.round(1000 * parseInt(currentheight, 10) / this.height);
                input.set('checked', widthRatio === heightRatio);
            }

            // Apply the image sizing.
            self._autoAdjustSize(self);

            // Centre the dialogue once the preview image has loaded.
            self.getDialogue().centerDialogue();
        };

        image.src = url;
    },

    /**
     * Return the dialogue content for the tool, attaching any required
     * events.
     *
     * @method _getDialogueContent
     * @return {Node} The content to place in the dialogue.
     * @private
     */
    _getDialogueContent: function() {
        var template = Y.Handlebars.compile(TEMPLATE),
            canShowFilepicker = this.get('host').canShowFilepicker('image'),
            content = Y.Node.create(template({
                elementid: this.get('host').get('elementid'),
                CSS: CSS,
                component: COMPONENTNAME,
                showFilepicker: canShowFilepicker,
                alignments: ALIGNMENTS
            }));

        this._form = content;

        // Configure the view of the current image.
        this._applyImageProperties(this._form);

        this._form.one('.' + CSS.INPUTURL).on('blur', this._urlChanged, this);
        this._form.one('.' + CSS.IMAGEPRESENTATION).on('change', this._updateWarning, this);
        this._form.one('.' + CSS.INPUTALT).on('change', this._updateWarning, this);
        this._form.one('.' + CSS.INPUTWIDTH).on('blur', this._autoAdjustSize, this);
        this._form.one('.' + CSS.INPUTHEIGHT).on('blur', this._autoAdjustSize, this, true);
        this._form.one('.' + CSS.INPUTCONSTRAIN).on('change', function(event) {
            if (event.target.get('checked')) {
                this._autoAdjustSize(event);
            }
        }, this);
        this._form.one('.' + CSS.INPUTURL).on('blur', this._urlChanged, this);
        this._form.one('.' + CSS.INPUTSUBMIT).on('click', this._setImage, this);

        if (canShowFilepicker) {
            this._form.one('.' + CSS.IMAGEBROWSER).on('click', function() {
                    this.get('host').showFilepicker('image', this._filepickerCallback, this);
            }, this);
        }

        return content;
    },

    _autoAdjustSize: function(e, forceHeight) {
        forceHeight = forceHeight || false;

        var keyField = this._form.one('.' + CSS.INPUTWIDTH),
            keyFieldType = 'width',
            subField = this._form.one('.' + CSS.INPUTHEIGHT),
            subFieldType = 'height',
            constrainField = this._form.one('.' + CSS.INPUTCONSTRAIN),
            keyFieldValue = keyField.get('value'),
            subFieldValue = subField.get('value'),
            imagePreview = this._form.one('.' + CSS.IMAGEPREVIEW),
            rawPercentage,
            rawSize;

        // If we do not know the image size, do not do anything.
        if (!this._rawImageDimensions) {
            return;
        }

        // Set the width back to default if it is empty.
        if (keyFieldValue === '') {
            keyFieldValue = this._rawImageDimensions[keyFieldType];
            keyField.set('value', keyFieldValue);
            keyFieldValue = keyField.get('value');
        }

        // Clear the existing preview sizes.
        imagePreview.setStyles({
            width: null,
            height: null
        });

        // Now update with the new values.
        if (!constrainField.get('checked')) {
            // We are not keeping the image proportion - update the preview accordingly.

            // Width.
            if (keyFieldValue.match(REGEX.ISPERCENT)) {
                rawPercentage = parseInt(keyFieldValue, 10);
                rawSize = this._rawImageDimensions.width / 100 * rawPercentage;
                imagePreview.setStyle('width', rawSize + 'px');
            } else {
                imagePreview.setStyle('width', keyFieldValue + 'px');
            }

            // Height.
            if (subFieldValue.match(REGEX.ISPERCENT)) {
                rawPercentage = parseInt(subFieldValue, 10);
                rawSize = this._rawImageDimensions.height / 100 * rawPercentage;
                imagePreview.setStyle('height', rawSize + 'px');
            } else {
                imagePreview.setStyle('height', subFieldValue + 'px');
            }
        } else {
            // We are keeping the image in proportion.
            if (forceHeight) {
                // By default we update based on width. Swap the key and sub fields around to achieve a height-based scale.
                var _temporaryValue;
                _temporaryValue = keyField;
                keyField = subField;
                subField = _temporaryValue;

                _temporaryValue = keyFieldType;
                keyFieldType = subFieldType;
                subFieldType = _temporaryValue;

                _temporaryValue = keyFieldValue;
                keyFieldValue = subFieldValue;
                subFieldValue = _temporaryValue;
            }

            if (keyFieldValue.match(REGEX.ISPERCENT)) {
                // This is a percentage based change. Copy it verbatim.
                subFieldValue = keyFieldValue;

                // Set the width to the calculated pixel width.
                rawPercentage = parseInt(keyFieldValue, 10);
                rawSize = this._rawImageDimensions.width / 100 * rawPercentage;

                // And apply the width/height to the container.
                imagePreview.setStyle('width', rawSize);
                rawSize = this._rawImageDimensions.height / 100 * rawPercentage;
                imagePreview.setStyle('height', rawSize);
            } else {
                // Calculate the scaled subFieldValue from the keyFieldValue.
                subFieldValue = Math.round((keyFieldValue / this._rawImageDimensions[keyFieldType]) *
                        this._rawImageDimensions[subFieldType]);

                if (forceHeight) {
                    imagePreview.setStyles({
                        'width': subFieldValue,
                        'height': keyFieldValue
                    });
                } else {
                    imagePreview.setStyles({
                        'width': keyFieldValue,
                        'height': subFieldValue
                    });
                }
            }

            // Update the subField's value within the form to reflect the changes.
            subField.set('value', subFieldValue);
        }
    },

    /**
     * Update the dialogue after an image was selected in the File Picker.
     *
     * @method _filepickerCallback
     * @param {object} params The parameters provided by the filepicker
     * containing information about the image.
     * @private
     */
    _filepickerCallback: function(params) {
        if (params.url !== '') {
            var input = this._form.one('.' + CSS.INPUTURL);
            input.set('value', params.url);

            // Auto set the width and height.
            this._form.one('.' + CSS.INPUTWIDTH).set('value', '');
            this._form.one('.' + CSS.INPUTHEIGHT).set('value', '');

            // Load the preview image.
            this._loadPreviewImage(params.url);
        }
    },

    /**
     * Applies properties of an existing image to the image dialogue for editing.
     *
     * @method _applyImageProperties
     * @param {Node} form
     * @private
     */
    _applyImageProperties: function(form) {
        var properties = this._getSelectedImageProperties(),
            img = form.one('.' + CSS.IMAGEPREVIEW);

        if (properties === false) {
            img.setStyle('display', 'none');
            // Set the default alignment.
            ALIGNMENTS.some(function(alignment) {
                if (alignment.isDefault) {
                    form.one('.' + CSS.INPUTALIGNMENT).set('value', alignment.value);
                    return true;
                }

                return false;
            }, this);

            return;
        }

        if (properties.align) {
            form.one('.' + CSS.INPUTALIGNMENT).set('value', properties.align);
        }
        if (properties.customstyle) {
            form.one('.' + CSS.INPUTCUSTOMSTYLE).set('value', properties.customstyle);
        }
        if (properties.width) {
            form.one('.' + CSS.INPUTWIDTH).set('value', properties.width);
        }
        if (properties.height) {
            form.one('.' + CSS.INPUTHEIGHT).set('value', properties.height);
        }
        if (properties.alt) {
            form.one('.' + CSS.INPUTALT).set('value', properties.alt);
        }
        if (properties.src) {
            form.one('.' + CSS.INPUTURL).set('value', properties.src);
            this._loadPreviewImage(properties.src);
        }
        if (properties.presentation) {
            form.one('.' + CSS.IMAGEPRESENTATION).set('checked', 'checked');
        }

        // Update the image preview based on the form properties.
        this._autoAdjustSize();
    },

    /**
     * Gets the properties of the currently selected image.
     *
     * The first image only if multiple images are selected.
     *
     * @method _getSelectedImageProperties
     * @return {object}
     * @private
     */
    _getSelectedImageProperties: function() {
        var properties = {
                src: null,
                alt: null,
                width: null,
                height: null,
                align: '',
                presentation: false
            },

            // Get the current selection.
            images = this.get('host').getSelectedNodes(),
            width,
            height,
            style,
            image;

        if (images) {
            images = images.filter('img');
        }

        if (images && images.size()) {
            image = this._removeLegacyAlignment(images.item(0));
            this._selectedImage = image;

            style = image.getAttribute('style');
            properties.customstyle = style;

            width = image.getAttribute('width');
            if (!width.match(REGEX.ISPERCENT)) {
                width = parseInt(width, 10);
            }
            height = image.getAttribute('height');
            if (!height.match(REGEX.ISPERCENT)) {
                height = parseInt(height, 10);
            }

            if (width !== 0) {
                properties.width = width;
            }
            if (height !== 0) {
                properties.height = height;
            }
            this._getAlignmentPropeties(image, properties);
            properties.src = image.getAttribute('src');
            properties.alt = image.getAttribute('alt') || '';
            properties.presentation = (image.get('role') === 'presentation');
            return properties;
        }

        // No image selected - clean up.
        this._selectedImage = null;
        return false;
    },

    /**
     * Sets the alignment of a properties object.
     *
     * @method _getAlignmentPropeties
     * @param {Node} image The image that the alignment properties should be found for
     * @param {Object} properties The properties object that is created in _getSelectedImageProperties()
     * @private
     */
    _getAlignmentPropeties: function(image, properties) {
        var complete = false,
            defaultAlignment;

        // Check for an alignment value.
        complete = ALIGNMENTS.some(function(alignment) {
            var classname = this._getAlignmentClass(alignment.value);
            if (image.hasClass(classname)) {
                properties.align = alignment.value;
                Y.log('Found alignment ' + alignment.value, 'debug', 'atto_image-button');

                return true;
            }

            if (alignment.isDefault) {
                defaultAlignment = alignment.value;
            }

            return false;
        }, this);

        if (!complete && defaultAlignment) {
            properties.align = defaultAlignment;
        }
    },

    /**
     * Update the form when the URL was changed. This includes updating the
     * height, width, and image preview.
     *
     * @method _urlChanged
     * @private
     */
    _urlChanged: function() {
        var input = this._form.one('.' + CSS.INPUTURL);

        if (input.get('value') !== '') {
            // Load the preview image.
            this._loadPreviewImage(input.get('value'));
        }
    },

    /**
     * Update the image in the contenteditable.
     *
     * @method _setImage
     * @param {EventFacade} e
     * @private
     */
    _setImage: function(e) {
        var form = this._form,
            url = form.one('.' + CSS.INPUTURL).get('value'),
            alt = form.one('.' + CSS.INPUTALT).get('value'),
            width = form.one('.' + CSS.INPUTWIDTH).get('value'),
            height = form.one('.' + CSS.INPUTHEIGHT).get('value'),
            alignment = this._getAlignmentClass(form.one('.' + CSS.INPUTALIGNMENT).get('value')),
            presentation = form.one('.' + CSS.IMAGEPRESENTATION).get('checked'),
            constrain = form.one('.' + CSS.INPUTCONSTRAIN).get('checked'),
            imagehtml,
            customstyle = form.one('.' + CSS.INPUTCUSTOMSTYLE).get('value'),
            classlist = [],
            host = this.get('host');

        e.preventDefault();

        // Check if there are any accessibility issues.
        if (this._updateWarning()) {
            return;
        }

        // Focus on the editor in preparation for inserting the image.
        host.focus();
        if (url !== '') {
            if (this._selectedImage) {
                host.setSelection(host.getSelectionFromNode(this._selectedImage));
            } else {
                host.setSelection(this._currentSelection);
            }

            if (constrain) {
                classlist.push(CSS.RESPONSIVE);
            }

            // Add the alignment class for the image.
            classlist.push(alignment);

            if (!width.match(REGEX.ISPERCENT) && isNaN(parseInt(width, 10))) {
                form.one('.' + CSS.INPUTWIDTH).focus();
                return;
            }
            if (!height.match(REGEX.ISPERCENT) && isNaN(parseInt(height, 10))) {
                form.one('.' + CSS.INPUTHEIGHT).focus();
                return;
            }

            var template = Y.Handlebars.compile(IMAGETEMPLATE);
            imagehtml = template({
                url: url,
                alt: alt,
                width: width,
                height: height,
                presentation: presentation,
                customstyle: customstyle,
                classlist: classlist.join(' ')
            });

            // Destroy the image editable now so that DeletionMutationObserver won't delete the image
            // once the new one is inserted.
            this._destroyEditableImg();
            this.get('host').insertContentAtFocusPoint(imagehtml);

            this.markUpdated();
        }

        this.getDialogue({
            focusAfterHide: null
        }).hide();

    },

    /**
     * Removes any legacy styles added by previous versions of the atto image button.
     *
     * @method _removeLegacyAlignment
     * @param {Y.Node} imageNode
     * @return {Y.Node}
     * @private
     */
    _removeLegacyAlignment: function(imageNode) {
        if (!imageNode.getStyle('margin')) {
            // There is no margin therefore this cannot match any known alignments.
            return imageNode;
        }

        ALIGNMENTS.some(function(alignment) {
            if (imageNode.getStyle(alignment.name) !== alignment.value) {
                // The name/value do not match. Skip.
                return false;
            }

            var normalisedNode = Y.Node.create('<div>');
            normalisedNode.setStyle('margin', alignment.margin);
            if (imageNode.getStyle('margin') !== normalisedNode.getStyle('margin')) {
                // The margin does not match.
                return false;
            }

            Y.log('Legacy alignment found and removed.', 'info', 'atto_image-button');
            imageNode.addClass(this._getAlignmentClass(alignment.value));
            imageNode.setStyle(alignment.name, null);
            imageNode.setStyle('margin', null);

            return true;
        }, this);

        return imageNode;
    },

    _getAlignmentClass: function(alignment) {
        return CSS.ALIGNSETTINGS + '_' + alignment;
    },

    /**
     * Update the alt text warning live.
     *
     * @method _updateWarning
     * @return {boolean} whether a warning should be displayed.
     * @private
     */
    _updateWarning: function() {
        var form = this._form,
            state = true,
            alt = form.one('.' + CSS.INPUTALT).get('value'),
            presentation = form.one('.' + CSS.IMAGEPRESENTATION).get('checked');
        if (alt === '' && !presentation) {
            form.one('.' + CSS.IMAGEALTWARNING).setStyle('display', 'block');
            form.one('.' + CSS.INPUTALT).setAttribute('aria-invalid', true);
            form.one('.' + CSS.IMAGEPRESENTATION).setAttribute('aria-invalid', true);
            state = true;
        } else {
            form.one('.' + CSS.IMAGEALTWARNING).setStyle('display', 'none');
            form.one('.' + CSS.INPUTALT).setAttribute('aria-invalid', false);
            form.one('.' + CSS.IMAGEPRESENTATION).setAttribute('aria-invalid', false);
            state = false;
        }
        this.getDialogue().centerDialogue();
        return state;
    }
});


}, '@VERSION@', {"requires": ["moodle-editor_atto-rangy", "moodle-editor_atto-plugin", "resize", "resize-plugin"]});
