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
