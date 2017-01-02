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
});